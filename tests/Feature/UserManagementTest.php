<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create(['active' => true]);
        $user->assignRole(RoleName::ADMINISTRADOR->value);
        Sanctum::actingAs($user);

        return $user;
    }

    private function userWithRole(RoleName $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes + ['active' => true]);
        $user->assignRole($role->value);

        return $user;
    }

    #[Test]
    public function el_administrador_crea_un_usuario_con_rol(): void
    {
        $this->admin();

        $response = $this->postJson('/api/users', [
            'name' => 'Recepcionista',
            'email' => 'recepcion@sinexcusas.test',
            'password' => 'claveSegura1',
            'password_confirmation' => 'claveSegura1',
            'roles' => [RoleName::RECEPCION->value],
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('users', ['email' => 'recepcion@sinexcusas.test']);

        $created = User::where('email', 'recepcion@sinexcusas.test')->first();
        $this->assertTrue($created->hasRole(RoleName::RECEPCION->value));
    }

    #[Test]
    public function la_contrasena_se_guarda_hasheada(): void
    {
        $this->admin();

        $this->postJson('/api/users', [
            'name' => 'Nuevo',
            'email' => 'nuevo@sinexcusas.test',
            'password' => 'claveSegura1',
            'password_confirmation' => 'claveSegura1',
            'roles' => [RoleName::ESPECIALISTA->value],
        ])->assertCreated();

        $created = User::where('email', 'nuevo@sinexcusas.test')->first();

        $this->assertNotSame('claveSegura1', $created->password);
        $this->assertTrue(Hash::check('claveSegura1', $created->password));
    }

    #[Test]
    public function exige_confirmacion_de_contrasena(): void
    {
        $this->admin();

        $this->postJson('/api/users', [
            'name' => 'Nuevo',
            'email' => 'otro@sinexcusas.test',
            'password' => 'claveSegura1',
            'password_confirmation' => 'distinta123',
            'roles' => [RoleName::RECEPCION->value],
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    #[Test]
    public function al_editar_sin_contrasena_conserva_la_anterior(): void
    {
        $this->admin();
        $target = $this->userWithRole(RoleName::RECEPCION);
        $originalHash = $target->password;

        $this->patchJson("/api/users/{$target->id}", [
            'name' => 'Nombre cambiado',
            'email' => $target->email,
            'roles' => [RoleName::RECEPCION->value],
            'active' => true,
        ])->assertOk();

        $this->assertSame($originalHash, $target->fresh()->password);
        $this->assertSame('Nombre cambiado', $target->fresh()->name);
    }

    // ------------------------------------------------------------------
    // Invariantes que evitan dejar el sistema sin acceso (§30)
    // ------------------------------------------------------------------

    #[Test]
    public function nadie_puede_desactivar_su_propia_cuenta(): void
    {
        $admin = $this->admin();
        // Otro administrador, para que la regla que se dispare sea la de
        // "cuenta propia" y no la de "último administrador".
        $this->userWithRole(RoleName::ADMINISTRADOR);

        $this->patchJson("/api/users/{$admin->id}", [
            'name' => $admin->name,
            'email' => $admin->email,
            'roles' => [RoleName::ADMINISTRADOR->value],
            'active' => false,
        ])->assertStatus(422);

        $this->assertTrue($admin->fresh()->active);
    }

    #[Test]
    public function nadie_puede_eliminar_su_propia_cuenta(): void
    {
        $admin = $this->admin();
        $this->userWithRole(RoleName::ADMINISTRADOR);

        $this->deleteJson("/api/users/{$admin->id}")->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    #[Test]
    public function no_puede_quitarse_a_si_mismo_el_rol_de_administrador(): void
    {
        $admin = $this->admin();
        $this->userWithRole(RoleName::ADMINISTRADOR);

        $this->patchJson("/api/users/{$admin->id}", [
            'name' => $admin->name,
            'email' => $admin->email,
            'roles' => [RoleName::RECEPCION->value],
            'active' => true,
        ])->assertStatus(422);

        $this->assertTrue($admin->fresh()->hasRole(RoleName::ADMINISTRADOR->value));
    }

    #[Test]
    public function no_permite_desactivar_al_ultimo_administrador(): void
    {
        // El actor es administrador pero elimina a OTRO administrador que es
        // el único activo restante... construimos el escenario al revés:
        // un admin desactivado por otro admin cuando solo queda uno activo.
        $actor = $this->admin();
        $other = $this->userWithRole(RoleName::ADMINISTRADOR);

        // Primero se desactiva al actor desde la cuenta del otro.
        Sanctum::actingAs($other);
        $this->patchJson("/api/users/{$actor->id}", [
            'name' => $actor->name,
            'email' => $actor->email,
            'roles' => [RoleName::ADMINISTRADOR->value],
            'active' => false,
        ])->assertOk();

        // Ahora `other` es el único administrador activo: nadie puede tumbarlo.
        $third = $this->userWithRole(RoleName::ADMINISTRADOR, ['active' => false]);
        Sanctum::actingAs($other);

        $this->patchJson("/api/users/{$other->id}", [
            'name' => $other->name,
            'email' => $other->email,
            'roles' => [RoleName::ADMINISTRADOR->value],
            'active' => false,
        ])->assertStatus(422);

        $this->assertTrue($other->fresh()->active);
        $this->assertFalse($third->fresh()->active);
    }

    #[Test]
    public function desactivar_un_usuario_cierra_sus_sesiones(): void
    {
        $this->admin();
        $target = $this->userWithRole(RoleName::RECEPCION);
        $target->createToken('sesion-abierta');

        $this->assertSame(1, $target->tokens()->count());

        $this->patchJson("/api/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'roles' => [RoleName::RECEPCION->value],
            'active' => false,
        ])->assertOk();

        $this->assertSame(0, $target->fresh()->tokens()->count());
    }

    #[Test]
    public function un_usuario_con_historial_se_desactiva_en_lugar_de_eliminarse(): void
    {
        $this->admin();
        $target = $this->userWithRole(RoleName::ESPECIALISTA);
        Employee::factory()->create(['user_id' => $target->id]);

        $this->deleteJson("/api/users/{$target->id}")->assertOk();

        $this->assertDatabaseHas('users', ['id' => $target->id, 'active' => false]);
    }

    #[Test]
    public function un_usuario_sin_historial_si_se_elimina(): void
    {
        $this->admin();
        $target = $this->userWithRole(RoleName::RECEPCION);

        $this->deleteJson("/api/users/{$target->id}")->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    // ------------------------------------------------------------------
    // Roles y permisos
    // ------------------------------------------------------------------

    #[Test]
    public function lista_los_roles_con_el_catalogo_de_permisos(): void
    {
        $this->admin();

        $response = $this->getJson('/api/roles')->assertOk();

        $response->assertJsonPath('data.locked_role', RoleName::ADMINISTRADOR->value);
        $this->assertNotEmpty($response->json('data.roles'));
        $this->assertNotEmpty($response->json('data.permissions'));
    }

    #[Test]
    public function permite_reconfigurar_los_permisos_de_un_rol(): void
    {
        $this->admin();
        $role = \Spatie\Permission\Models\Role::where('name', RoleName::ESPECIALISTA->value)->firstOrFail();

        $this->putJson("/api/roles/{$role->id}/permissions", [
            'permissions' => ['clients.view', 'attendances.view'],
        ])->assertOk();

        $this->assertEqualsCanonicalizing(
            ['clients.view', 'attendances.view'],
            $role->fresh()->permissions->pluck('name')->all()
        );
    }

    #[Test]
    public function el_rol_administrador_no_puede_perder_permisos(): void
    {
        $this->admin();
        $role = \Spatie\Permission\Models\Role::where('name', RoleName::ADMINISTRADOR->value)->firstOrFail();
        $before = $role->permissions->count();

        $this->putJson("/api/roles/{$role->id}/permissions", ['permissions' => ['clients.view']])
            ->assertStatus(422);

        $this->assertSame($before, $role->fresh()->permissions->count());
    }

    #[Test]
    public function rechaza_permisos_que_no_existen_en_el_sistema(): void
    {
        $this->admin();
        $role = \Spatie\Permission\Models\Role::where('name', RoleName::RECEPCION->value)->firstOrFail();

        $this->putJson("/api/roles/{$role->id}/permissions", [
            'permissions' => ['modulo.inventado'],
        ])->assertStatus(422)->assertJsonValidationErrors('permissions.0');
    }

    #[Test]
    public function recepcion_no_puede_gestionar_usuarios(): void
    {
        $user = $this->userWithRole(RoleName::RECEPCION);
        Sanctum::actingAs($user);

        $this->getJson('/api/users')->assertForbidden();
        $this->postJson('/api/users', [])->assertForbidden();
    }
}
