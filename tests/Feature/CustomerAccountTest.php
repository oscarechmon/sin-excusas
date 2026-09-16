<?php

namespace Tests\Feature;

use App\Mail\ClientVerificationCodeMail;
use App\Models\Client;
use App\Models\ClientUser;
use App\Services\Shop\EmailVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Cuentas web: código de verificación al registrarse con correo y acceso con Google. */
class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    private function register(string $email = 'ana@example.com'): void
    {
        $this->post('/cuenta/registro', [
            'name' => 'Ana Pérez',
            'email' => $email,
            'phone' => '987654321',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ])->assertRedirect(route('shop.verification.notice'));
    }

    /** El código solo viaja en el correo: se lee del mensaje enviado. */
    private function sentCode(): string
    {
        $code = null;

        \Mail::assertSent(ClientVerificationCodeMail::class, function (ClientVerificationCodeMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        return $code;
    }

    private function fakeGoogleUser(string $id = 'google-123', string $email = 'ana@gmail.com'): void
    {
        config(['services.google.client_id' => 'test-client']);

        $user = (new GoogleUser)->map([
            'id' => $id,
            'name' => 'Ana Google',
            'email' => $email,
            'avatar' => 'https://example.com/avatar.png',
        ]);

        Socialite::shouldReceive('driver->user')->andReturn($user);
    }

    #[Test]
    public function registrarse_con_correo_envia_un_codigo_y_bloquea_compras_hasta_verificar(): void
    {
        \Mail::fake();

        $this->register();

        \Mail::assertSent(ClientVerificationCodeMail::class, fn ($mail) => $mail->hasTo('ana@example.com'));
        $this->assertFalse(ClientUser::query()->where('email', 'ana@example.com')->firstOrFail()->hasVerifiedEmail());

        $this->get('/checkout')->assertRedirect(route('shop.verification.notice'));
        $this->get('/cuenta/pedidos')->assertRedirect(route('shop.verification.notice'));
    }

    #[Test]
    public function el_codigo_no_se_guarda_en_texto_plano(): void
    {
        \Mail::fake();
        $this->register();

        $stored = ClientUser::query()->where('email', 'ana@example.com')->value('verification_code');

        $this->assertNotSame($this->sentCode(), $stored);
    }

    #[Test]
    public function el_codigo_correcto_verifica_la_cuenta_una_sola_vez(): void
    {
        \Mail::fake();
        $this->register();

        $this->post('/cuenta/verificar', ['code' => $this->sentCode()])
            ->assertRedirect(route('shop.account.orders'));

        $user = ClientUser::query()->where('email', 'ana@example.com')->firstOrFail();
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertNull($user->verification_code);

        // Ya verificada: no vuelve a pedir código ni al entrar de nuevo.
        $this->get('/cuenta/verificar')->assertRedirect(route('shop.account.orders'));
        $this->post('/cuenta/salir');
        $this->post('/cuenta/ingresar', ['email' => 'ana@example.com', 'password' => 'secreto123'])
            ->assertRedirect(route('shop.account.orders'));
        \Mail::assertSentCount(1);
    }

    #[Test]
    public function el_codigo_incorrecto_se_bloquea_tras_varios_intentos(): void
    {
        \Mail::fake();
        $this->register();
        $code = $this->sentCode();
        $wrong = $code === '000000' ? '111111' : '000000';

        for ($i = 0; $i < EmailVerificationService::MAX_ATTEMPTS; $i++) {
            $this->post('/cuenta/verificar', ['code' => $wrong])->assertSessionHasErrors('code');
        }

        // Ni el código correcto sirve después del bloqueo.
        $this->post('/cuenta/verificar', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertFalse(ClientUser::query()->where('email', 'ana@example.com')->firstOrFail()->hasVerifiedEmail());
    }

    #[Test]
    public function el_codigo_vence(): void
    {
        \Mail::fake();
        $this->register();
        $code = $this->sentCode();

        $this->travel(EmailVerificationService::CODE_TTL_MINUTES + 1)->minutes();

        $this->post('/cuenta/verificar', ['code' => $code])->assertSessionHasErrors('code');
    }

    #[Test]
    public function reenviar_el_codigo_respeta_la_espera_e_invalida_el_anterior(): void
    {
        \Mail::fake();
        $this->register();
        $first = $this->sentCode();

        $this->post('/cuenta/verificar/reenviar')->assertSessionHas('error');
        \Mail::assertSentCount(1);

        $this->travel(EmailVerificationService::RESEND_COOLDOWN_SECONDS + 1)->seconds();
        $this->post('/cuenta/verificar/reenviar')->assertSessionHas('success');
        \Mail::assertSentCount(2);

        $user = ClientUser::query()->where('email', 'ana@example.com')->firstOrFail();
        $this->assertFalse(\Hash::check($first, $user->verification_code) && $first !== '');
    }

    #[Test]
    public function ingresar_con_una_cuenta_sin_verificar_lleva_a_verificar(): void
    {
        ClientUser::factory()->unverified()->create(['email' => 'luis@example.com', 'password' => 'secreto123']);

        $this->post('/cuenta/ingresar', ['email' => 'luis@example.com', 'password' => 'secreto123'])
            ->assertRedirect(route('shop.verification.notice'));
    }

    #[Test]
    public function google_crea_una_cuenta_verificada_con_su_ficha_de_cliente(): void
    {
        \Mail::fake();
        $this->fakeGoogleUser();

        $this->get('/cuenta/google/callback')->assertRedirect(route('shop.account.orders'));

        $user = ClientUser::query()->where('email', 'ana@gmail.com')->firstOrFail();
        $this->assertAuthenticatedAs($user, 'customer');
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertSame('google-123', $user->google_id);
        $this->assertNotNull($user->client_id);
        \Mail::assertNothingSent();
    }

    #[Test]
    public function google_vincula_la_cuenta_existente_con_el_mismo_correo(): void
    {
        $existing = ClientUser::factory()->unverified()->create(['email' => 'ana@gmail.com']);
        $this->fakeGoogleUser();

        $this->get('/cuenta/google/callback')->assertRedirect();

        $this->assertSame(1, ClientUser::query()->where('email', 'ana@gmail.com')->count());
        $existing->refresh();
        $this->assertSame('google-123', $existing->google_id);
        $this->assertTrue($existing->hasVerifiedEmail());
        $this->assertSame(1, Client::query()->count());
    }

    #[Test]
    public function una_cuenta_creada_con_google_no_ingresa_con_contrasena(): void
    {
        $this->fakeGoogleUser();
        $this->get('/cuenta/google/callback');
        $this->post('/cuenta/salir');

        $this->post('/cuenta/ingresar', ['email' => 'ana@gmail.com', 'password' => 'cualquiera'])
            ->assertSessionHasErrors(['email' => 'Esta cuenta se creó con Google. Usa el botón "Continuar con Google".']);
    }
}
