<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * §29: la autorización debe validarse en backend, no ocultando botones.
 * Estos tests golpean la API directamente para comprobarlo.
 */
class PermissionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function actingAsRole(RoleName $role): User
    {
        $user = User::factory()->create(['active' => true]);
        $user->assignRole($role->value);
        Sanctum::actingAs($user);

        return $user;
    }

    #[Test]
    public function un_invitado_no_accede_a_la_api(): void
    {
        $this->getJson('/api/clients')->assertUnauthorized();
    }

    #[Test]
    public function recepcion_puede_listar_clientes(): void
    {
        $this->actingAsRole(RoleName::RECEPCION);

        $this->getJson('/api/clients')->assertOk();
    }

    #[Test]
    public function recepcion_no_puede_administrar_el_inventario(): void
    {
        $this->actingAsRole(RoleName::RECEPCION);

        // Puede consultarlo, porque necesita saber si hay stock...
        $this->getJson('/api/inventory-items')->assertOk();

        // ...pero no crear productos ni ajustar el stock.
        $this->postJson('/api/inventory-items', [
            'name' => 'Producto nuevo',
            'unit' => 'unidad',
            'min_stock' => 1,
            'cost' => 10,
        ])->assertForbidden();
    }

    #[Test]
    public function especialista_no_puede_ver_ventas_ni_comisiones(): void
    {
        $this->actingAsRole(RoleName::ESPECIALISTA);

        $this->getJson('/api/sales')->assertForbidden();
        $this->getJson('/api/commissions')->assertForbidden();
        $this->getJson('/api/reports/sales')->assertForbidden();
    }

    #[Test]
    public function especialista_si_puede_registrar_atenciones(): void
    {
        $this->actingAsRole(RoleName::ESPECIALISTA);

        // Sin cuerpo válido responde 422 de validación, no 403: eso confirma
        // que el permiso pasó y solo falló la validación.
        $this->postJson('/api/attendances', [])->assertStatus(422);
    }

    #[Test]
    public function solo_el_administrador_gestiona_personal(): void
    {
        $this->actingAsRole(RoleName::RECEPCION);
        $this->postJson('/api/employees', ['name' => 'Nuevo'])->assertForbidden();

        $this->actingAsRole(RoleName::ADMINISTRADOR);
        $this->postJson('/api/employees', ['name' => 'Nuevo'])->assertCreated();

        $this->assertDatabaseHas('employees', ['name' => 'Nuevo']);
    }

    #[Test]
    public function el_administrador_tiene_todos_los_permisos(): void
    {
        $this->actingAsRole(RoleName::ADMINISTRADOR);
        Employee::factory()->create();

        $this->getJson('/api/employees')->assertOk();
        $this->getJson('/api/inventory-items')->assertOk();
        $this->getJson('/api/sales')->assertOk();
        $this->getJson('/api/commissions')->assertOk();
        $this->getJson('/api/reports/stock')->assertOk();
        $this->getJson('/api/cash-sessions/current')->assertOk();
    }

    #[Test]
    public function el_ajuste_de_stock_exige_su_propio_permiso(): void
    {
        // Recepción puede ver inventario pero no ajustar saldos: un ajuste
        // puede encubrir un faltante.
        $this->actingAsRole(RoleName::RECEPCION);
        $item = \App\Models\InventoryItem::factory()->create();

        $this->postJson("/api/inventory-items/{$item->id}/adjust", [
            'type' => 'adjustment',
            'quantity' => 5,
        ])->assertForbidden();
    }
}
