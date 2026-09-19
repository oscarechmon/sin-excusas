<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Inicio de sesión del panel. */
class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * El panel arma el menú y los accesos rápidos a partir de los permisos.
     * Si el login no los devuelve, el dashboard queda vacío hasta recargar.
     */
    #[Test]
    public function el_login_devuelve_los_permisos_igual_que_me(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['active' => true, 'password' => 'secreto123']);
        $user->assignRole(RoleName::RECEPCION->value);

        $login = $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'secreto123'])
            ->assertOk()
            ->assertJsonPath('data.roles', [RoleName::RECEPCION->value]);

        $permissions = $login->json('data.permissions');
        $this->assertNotEmpty($permissions);
        $this->assertContains('clients.view', $permissions);

        $me = $this->withToken($login->json('data.token'))->getJson('/api/auth/me')->assertOk();
        $this->assertEqualsCanonicalizing($me->json('data.permissions'), $permissions);
    }
}
