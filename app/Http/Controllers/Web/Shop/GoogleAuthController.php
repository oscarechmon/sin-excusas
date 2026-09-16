<?php

namespace App\Http\Controllers\Web\Shop;

use App\Actions\Shop\RegisterClientUserAction;
use App\Http\Controllers\Controller;
use App\Models\ClientUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * Registro e inicio de sesión con Google.
 *
 * Google ya comprobó que el correo pertenece a la persona, así que estas
 * cuentas no reciben código de verificación.
 */
class GoogleAuthController extends Controller
{
    public function redirect(): SymfonyRedirect|RedirectResponse
    {
        if (! config('services.google.client_id')) {
            return redirect()->route('shop.login')->with('error', 'El acceso con Google no está disponible por el momento.');
        }

        // select_account: tras cerrar sesión, Google deja elegir otra cuenta en
        // lugar de volver a entrar en silencio con la última usada.
        return Socialite::driver('google')->with(['prompt' => 'select_account'])->redirect();
    }

    public function callback(Request $request, RegisterClientUserAction $register): RedirectResponse
    {
        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('shop.login')->with('error', 'No pudimos iniciar sesión con Google. Inténtalo nuevamente.');
        }

        if (! $google->getEmail()) {
            return redirect()->route('shop.login')->with('error', 'Tu cuenta de Google no compartió un correo.');
        }

        $user = ClientUser::query()->where('google_id', $google->getId())->first()
            ?? ClientUser::query()->where('email', $google->getEmail())->first();

        if ($user) {
            // Si ya se había registrado con ese correo, se vincula a Google en
            // lugar de crear una segunda cuenta.
            $user->forceFill([
                'google_id' => $google->getId(),
                'avatar_url' => $google->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        } else {
            $user = $register->execute(
                ['name' => $google->getName() ?: $google->getEmail(), 'email' => $google->getEmail()],
                emailVerified: true,
                googleId: $google->getId(),
                avatarUrl: $google->getAvatar(),
            );
        }

        Auth::guard('customer')->login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('shop.account.orders'))
            ->with('success', "Hola, {$user->name}. Ingresaste con Google.");
    }
}
