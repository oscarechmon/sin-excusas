<?php

namespace Tests\Feature;

use App\Actions\Attendances\ConfirmAttendanceAction;
use App\DTOs\AttendanceData;
use App\Enums\AppointmentStatus;
use App\Enums\CommissionType;
use App\Exceptions\InsufficientStockException;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\ClientPackage;
use App\Models\CommissionRule;
use App\Models\Employee;
use App\Models\InventoryItem;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El proceso central del sistema (§19): confirmar una atención debe encadenar
 * historial, sesión de paquete, inventario y comisión de forma atómica.
 */
class ConfirmAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private ConfirmAttendanceAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = app(ConfirmAttendanceAction::class);
    }

    #[Test]
    public function registra_la_atencion_y_descuenta_los_insumos(): void
    {
        $client = Client::factory()->create();
        $service = Service::factory()->create(['price' => 200]);
        $employee = Employee::factory()->create();
        $needle = InventoryItem::factory()->withStock(100)->create();
        $gauze = InventoryItem::factory()->withStock(50)->create();

        $attendance = $this->action->execute(new AttendanceData(
            clientId: $client->id,
            serviceId: $service->id,
            employeeId: $employee->id,
            attendedAt: now()->toDateString(),
            supplies: [
                ['inventory_item_id' => $needle->id, 'quantity' => 2],
                ['inventory_item_id' => $gauze->id, 'quantity' => 3],
            ],
        ));

        $this->assertDatabaseHas('attendances', ['id' => $attendance->id, 'client_id' => $client->id]);
        $this->assertEquals(98, $needle->fresh()->stock);
        $this->assertEquals(47, $gauze->fresh()->stock);
        $this->assertDatabaseCount('attendance_supplies', 2);
        $this->assertDatabaseCount('inventory_movements', 2);
    }

    #[Test]
    public function consume_una_sesion_del_paquete_indicado(): void
    {
        $client = Client::factory()->create();
        $package = ClientPackage::factory()->for($client)->withSessions(10)->create();
        $service = Service::factory()->create();
        $employee = Employee::factory()->create();

        $attendance = $this->action->execute(new AttendanceData(
            clientId: $client->id,
            serviceId: $service->id,
            employeeId: $employee->id,
            attendedAt: now()->toDateString(),
            clientPackageId: $package->id,
        ));

        $this->assertSame(9, $package->fresh()->remainingSessions());
        $this->assertSame(1, $attendance->fresh()->session_number);
    }

    #[Test]
    public function genera_la_comision_segun_la_regla_activa(): void
    {
        $service = Service::factory()->create(['price' => 300]);
        $employee = Employee::factory()->create();
        CommissionRule::factory()->create([
            'employee_id' => $employee->id,
            'type' => CommissionType::PERCENTAGE,
            'value' => 20,
        ]);

        $attendance = $this->action->execute(new AttendanceData(
            clientId: Client::factory()->create()->id,
            serviceId: $service->id,
            employeeId: $employee->id,
            attendedAt: now()->toDateString(),
        ));

        $commission = $attendance->fresh()->commission;

        $this->assertNotNull($commission);
        $this->assertEquals(60, $commission->amount);
        $this->assertEquals(300, $commission->base_amount);
    }

    #[Test]
    public function la_comision_de_una_sesion_de_paquete_usa_el_valor_prorrateado(): void
    {
        $client = Client::factory()->create();
        // Paquete de S/ 1000 en 10 sesiones -> base de S/ 100 por sesión.
        $package = ClientPackage::factory()->for($client)->create([
            'price' => 1000,
            'total_sessions' => 10,
        ]);
        $service = Service::factory()->create(['price' => 300]);
        $employee = Employee::factory()->create();
        CommissionRule::factory()->create(['type' => CommissionType::PERCENTAGE, 'value' => 10]);

        $attendance = $this->action->execute(new AttendanceData(
            clientId: $client->id,
            serviceId: $service->id,
            employeeId: $employee->id,
            attendedAt: now()->toDateString(),
            clientPackageId: $package->id,
        ));

        $this->assertEquals(100, $attendance->fresh()->commission->base_amount);
        $this->assertEquals(10, $attendance->fresh()->commission->amount);
    }

    #[Test]
    public function marca_la_cita_como_atendida(): void
    {
        $client = Client::factory()->create();
        $service = Service::factory()->create();
        $employee = Employee::factory()->create();
        $appointment = Appointment::create([
            'client_id' => $client->id,
            'service_id' => $service->id,
            'employee_id' => $employee->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => AppointmentStatus::CONFIRMED->value,
        ]);

        $this->action->execute(new AttendanceData(
            clientId: $client->id,
            serviceId: $service->id,
            employeeId: $employee->id,
            attendedAt: now()->toDateString(),
            appointmentId: $appointment->id,
        ));

        $this->assertSame(AppointmentStatus::ATTENDED, $appointment->fresh()->status);
    }

    /**
     * Caso crítico §11 y §47: si falla un insumo, no puede quedar la atención
     * registrada ni la sesión del paquete consumida.
     */
    #[Test]
    public function revierte_todo_si_un_insumo_no_tiene_stock(): void
    {
        $client = Client::factory()->create();
        $package = ClientPackage::factory()->for($client)->withSessions(10)->create();
        $service = Service::factory()->create();
        $employee = Employee::factory()->create();
        $ok = InventoryItem::factory()->withStock(100)->create();
        $short = InventoryItem::factory()->withStock(1)->create();

        try {
            $this->action->execute(new AttendanceData(
                clientId: $client->id,
                serviceId: $service->id,
                employeeId: $employee->id,
                attendedAt: now()->toDateString(),
                clientPackageId: $package->id,
                supplies: [
                    ['inventory_item_id' => $ok->id, 'quantity' => 2],
                    ['inventory_item_id' => $short->id, 'quantity' => 10],
                ],
            ));
            $this->fail('Debió fallar por stock insuficiente.');
        } catch (InsufficientStockException) {
            // esperado
        }

        $this->assertDatabaseCount('attendances', 0);
        $this->assertDatabaseCount('attendance_supplies', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseCount('commissions', 0);
        // El insumo que sí alcanzaba tampoco debe haberse descontado.
        $this->assertEquals(100, $ok->fresh()->stock);
        $this->assertSame(10, $package->fresh()->remainingSessions());
    }

    /** Caso crítico §47: "no generar doble comisión". */
    #[Test]
    public function una_atencion_genera_como_maximo_una_comision(): void
    {
        $service = Service::factory()->create(['price' => 200]);
        $employee = Employee::factory()->create();
        CommissionRule::factory()->create(['type' => CommissionType::PERCENTAGE, 'value' => 10]);

        $attendance = $this->action->execute(new AttendanceData(
            clientId: Client::factory()->create()->id,
            serviceId: $service->id,
            employeeId: $employee->id,
            attendedAt: now()->toDateString(),
        ));

        // Un reintento del servicio de comisiones no debe duplicar el registro.
        app(\App\Services\CommissionService::class)->generateForAttendance($attendance, 200);

        $this->assertDatabaseCount('commissions', 1);
    }
}
