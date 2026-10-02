<?php

namespace App\Console\Commands;

use App\Exceptions\ErpException;
use App\Models\Appointment;
use App\Models\Attendance;
use App\Models\CashSession;
use App\Models\Client;
use App\Models\ClientPackage;
use App\Models\Commission;
use App\Models\CommissionRule;
use App\Models\Employee;
use App\Models\InventoryItem;
use App\Models\OnlineOrder;
use App\Models\Package;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Service;
use App\Models\User;
use App\Services\Erp\ErpClient;
use App\Services\Erp\OnlineOrderRegistrar;
use BackedEnum;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Lleva al sistema todo el historial de este panel, para que la operación
 * diaria pase allá: usuarios, personal, clientes, reglas de comisión,
 * paquetes, ventas (con sus pagos y saldos), paquetes de clientes, citas,
 * atenciones, comisiones, caja y pedidos online.
 *
 * Se ejecuta después de `erp:vincular` (el catálogo ya enlazado). Se puede
 * repetir: el sistema reconoce lo que ya recibió y no lo duplica, y lo que
 * falló en una corrida entra en la siguiente. Nada de esto mueve stock allá.
 */
class ErpMigrarCommand extends Command
{
    protected $signature = 'erp:migrar {--solo= : Solo un tipo (users, customers, sales, orders…)}';

    protected $description = 'Lleva al sistema el historial del panel (clientes, ventas, agenda, atenciones, caja, pedidos…)';

    private const CHUNK = 100;

    public function handle(ErpClient $erp, OnlineOrderRegistrar $orders): int
    {
        if (! $erp->enabled()) {
            $this->error('Configura ERP_URL y ERP_TOKEN en el .env (y ejecuta php artisan optimize).');

            return self::FAILURE;
        }

        $unlinked = Service::whereNull('erp_id')->count() + InventoryItem::whereNull('erp_id')->count();
        if ($unlinked > 0) {
            $this->error("Hay {$unlinked} productos o servicios sin enlazar con el sistema. Ejecuta antes: php artisan erp:vincular");

            return self::FAILURE;
        }

        $steps = [
            'users' => fn () => $this->users(),
            'employees' => fn () => $this->employees(),
            'customers' => fn () => $this->customers(),
            'commission_rules' => fn () => $this->commissionRules(),
            'packages' => fn () => $this->packages(),
            'sales' => fn () => $this->sales(),
            'customer_packages' => fn () => $this->customerPackages(),
            'appointments' => fn () => $this->appointments(),
            'attendances' => fn () => $this->attendances(),
            'commissions' => fn () => $this->commissions(),
            'cash_sessions' => fn () => $this->cashSessions(),
        ];
        $only = $this->option('solo');
        $failed = false;

        foreach ($steps as $kind => $records) {
            if ($only && $only !== $kind) {
                continue;
            }

            try {
                $this->send($erp, $kind, $records());
            } catch (ErpException $e) {
                $this->error("  {$kind}: {$e->getMessage()}");
                $this->line('  Corrige y vuelve a ejecutar el comando: lo ya importado no se repite.');
                $failed = true;
                break;
            }
        }

        if (! $failed && (! $only || $only === 'orders')) {
            $failed = ! $this->orders($erp, $orders);
        }

        if ($failed) {
            return self::FAILURE;
        }

        $this->info('Listo: el historial ya está en el sistema. Desde ahora la operación se hace allá.');

        return self::SUCCESS;
    }

    /** @param  Collection<int, array<string, mixed>>  $records */
    private function send(ErpClient $erp, string $kind, Collection $records): void
    {
        $created = 0;
        $existing = 0;

        foreach ($records->chunk(self::CHUNK) as $chunk) {
            $result = $erp->import($kind, $chunk->values()->all());
            $created += $result['created'] ?? 0;
            $existing += $result['existing'] ?? 0;
            $this->link($kind, (array) ($result['links'] ?? []));
        }

        $this->line(sprintf('  %-18s %d nuevos, %d ya estaban', $kind, $created, $existing));
    }

    /**
     * Guarda el enlace de lo que la web sigue usando: el cliente (cuentas y
     * pedidos de la tienda) y el paquete (se publica aquí).
     *
     * @param  array<int|string, int>  $links
     */
    private function link(string $kind, array $links): void
    {
        $model = match ($kind) {
            'customers' => Client::class,
            'packages' => Package::class,
            default => null,
        };

        if (! $model) {
            return;
        }

        foreach ($links as $webId => $erpId) {
            $model::whereKey((int) $webId)->whereNull('erp_id')->update(['erp_id' => (int) $erpId]);
        }
    }

    private function orders(ErpClient $erp, OnlineOrderRegistrar $registrar): bool
    {
        $sent = 0;

        foreach (OnlineOrder::with('items.itemable', 'clientUser', 'client', 'histories.user')->orderBy('id')->lazy() as $order) {
            try {
                $result = $erp->pushOrder($registrar->snapshot($order, historical: true));
            } catch (ErpException $e) {
                $this->error("  orders: {$order->code}: {$e->getMessage()}");

                return false;
            }

            if (! empty($result['sale']) && $order->status->isPaid()) {
                $order->forceFill(['erp_sale_status' => OnlineOrder::ERP_REGISTERED])->save();
            }
            $sent++;
        }

        $this->line(sprintf('  %-18s %d enviados', 'orders', $sent));

        return true;
    }

    // ---------------------------------------------------------------- Tipos

    private function users(): Collection
    {
        return User::with('roles')->orderBy('id')->get()->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'password_hash' => $u->getAuthPassword(),
            'roles' => $u->getRoleNames()->all(),
            'active' => (bool) $u->active,
        ]);
    }

    private function employees(): Collection
    {
        return Employee::with('services')->orderBy('id')->get()->map(fn (Employee $e) => [
            'id' => $e->id,
            'name' => $e->name,
            'position' => $e->position,
            'phone' => $e->phone,
            'document_number' => $e->document_number,
            'user_id' => $e->user_id,
            'active' => (bool) $e->active,
            'service_ids' => $e->services->pluck('erp_id')->filter()->values()->all(),
        ]);
    }

    private function customers(): Collection
    {
        return Client::orderBy('id')->get()->map(fn (Client $c) => [
            'id' => $c->id,
            'code' => $c->code,
            'full_name' => $c->full_name,
            'document_number' => $c->document_number,
            'birth_date' => $c->birth_date?->toDateString(),
            'gender' => $this->value($c->gender),
            'phone' => $c->phone,
            'whatsapp' => $c->whatsapp,
            'email' => $c->email,
            'district' => $c->district,
            'address' => $c->address,
            'how_knew' => $c->how_knew,
            'observations' => $c->observations,
            'allergies' => $c->allergies,
            'restrictions' => $c->restrictions,
            'contraindications' => $c->contraindications,
            'medications' => $c->medications,
            'relevant_info' => $c->relevant_info,
            'active' => (bool) $c->active,
        ]);
    }

    private function commissionRules(): Collection
    {
        return CommissionRule::with('service')->orderBy('id')->get()->map(fn (CommissionRule $r) => [
            'id' => $r->id,
            'employee_id' => $r->employee_id,
            'service_id' => $r->service?->erp_id,
            'type' => $this->value($r->type),
            'value' => (float) $r->value,
            'active' => (bool) $r->active,
        ]);
    }

    /** Solo los paquetes que nacieron aquí: los que ya vienen del sistema están allá. */
    private function packages(): Collection
    {
        return Package::with('services')->whereNull('erp_id')->orderBy('id')->get()->map(fn (Package $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'description' => $p->description,
            'price' => (float) $p->price,
            'total_sessions' => $p->total_sessions,
            'validity_days' => $p->validity_days,
            'active' => (bool) $p->active,
            'service_ids' => $p->services->pluck('erp_id')->filter()->values()->all(),
        ]);
    }

    private function sales(): Collection
    {
        return Sale::with(['items.itemable', 'payments.paymentMethod'])->orderBy('id')->get()->map(fn (Sale $s) => [
            'id' => $s->id,
            'code' => $s->code,
            'client_id' => $s->client_id,
            'discount' => (float) $s->discount,
            'status' => $this->value($s->status),
            'notes' => $s->notes,
            'created_by' => $s->created_by,
            'created_at' => $s->created_at?->toIso8601String(),
            'items' => $s->items->map(fn (SaleItem $i) => [
                // Un paquete nacido aquí se busca por su enlace; uno que vino del
                // sistema ya trae el id de su producto allá.
                'type' => match (true) {
                    $i->itemable instanceof Package => $i->itemable->erp_id ? 'product' : 'package',
                    $i->itemable instanceof InventoryItem => 'product',
                    default => 'service',
                },
                'product_id' => $i->itemable?->erp_id,
                'package_id' => $i->itemable instanceof Package ? $i->itemable_id : null,
                'employee_id' => $i->employee_id,
                'description' => $i->description,
                'unit_price' => (float) $i->unit_price,
                'quantity' => (float) $i->quantity,
                'discount' => (float) $i->discount,
                'subtotal' => (float) $i->subtotal,
            ])->values()->all(),
            'payments' => $s->payments->map(fn ($p) => [
                'method' => $p->paymentMethod?->code ?? 'cash',
                'amount' => (float) $p->amount,
                'reference' => $p->reference,
                'paid_at' => $p->paid_at?->toIso8601String(),
                'created_by' => $p->created_by,
            ])->values()->all(),
        ]);
    }

    private function customerPackages(): Collection
    {
        return ClientPackage::with('package')->orderBy('id')->get()->map(fn (ClientPackage $p) => [
            'id' => $p->id,
            'client_id' => $p->client_id,
            'package_id' => $p->package_id,
            'package_product_id' => $p->package?->erp_id,
            'sale_id' => null,
            'package_name' => $p->package_name,
            'price' => (float) $p->price,
            'total_sessions' => $p->total_sessions,
            'used_sessions' => $p->used_sessions,
            'purchased_at' => $p->purchased_at?->toDateString(),
            'expires_at' => $p->expires_at?->toDateString(),
            'status' => $this->value($p->status),
        ]);
    }

    private function appointments(): Collection
    {
        return Appointment::with('service')->orderBy('id')->get()->map(fn (Appointment $a) => [
            'id' => $a->id,
            'client_id' => $a->client_id,
            'service_id' => $a->service?->erp_id,
            'employee_id' => $a->employee_id,
            'appointment_date' => $a->appointment_date instanceof \DateTimeInterface ? $a->appointment_date->format('Y-m-d') : (string) $a->appointment_date,
            'start_time' => substr((string) $a->start_time, 0, 5),
            'end_time' => substr((string) $a->end_time, 0, 5),
            'status' => $this->value($a->status),
            'notes' => $a->notes,
        ]);
    }

    private function attendances(): Collection
    {
        return Attendance::with(['service', 'supplies.item'])->orderBy('id')->get()->map(fn (Attendance $a) => [
            'id' => $a->id,
            'client_id' => $a->client_id,
            'service_id' => $a->service?->erp_id,
            'employee_id' => $a->employee_id,
            'appointment_id' => $a->appointment_id,
            'client_package_id' => $a->client_package_id,
            'session_number' => $a->session_number,
            'attended_at' => $a->attended_at instanceof \DateTimeInterface ? $a->attended_at->format('Y-m-d') : (string) $a->attended_at,
            'observations' => $a->observations,
            'measurements' => $a->measurements,
            'created_by' => $a->created_by,
            'created_at' => $a->created_at?->toIso8601String(),
            'supplies' => $a->supplies->map(fn ($s) => [
                'product_id' => $s->item?->erp_id,
                'quantity' => (float) $s->quantity,
            ])->values()->all(),
        ]);
    }

    private function commissions(): Collection
    {
        return Commission::with('service')->orderBy('id')->get()->map(fn (Commission $c) => [
            'id' => $c->id,
            'employee_id' => $c->employee_id,
            'attendance_id' => $c->attendance_id,
            'sale_id' => $c->sale_id,
            'service_id' => $c->service?->erp_id,
            'base_amount' => (float) $c->base_amount,
            'type' => $this->value($c->type),
            'value' => (float) $c->value,
            'amount' => (float) $c->amount,
            'status' => $this->value($c->status),
            'generated_at' => $c->generated_at instanceof \DateTimeInterface ? $c->generated_at->format('Y-m-d') : (string) $c->generated_at,
            'paid_at' => $c->paid_at?->toIso8601String(),
            'paid_by' => $c->paid_by,
        ]);
    }

    private function cashSessions(): Collection
    {
        return CashSession::with('movements.paymentMethod')->orderBy('id')->get()->map(fn (CashSession $s) => [
            'id' => $s->id,
            'opening_amount' => (float) $s->opening_amount,
            'expected_amount' => $s->expected_amount !== null ? (float) $s->expected_amount : null,
            'counted_amount' => $s->counted_amount !== null ? (float) $s->counted_amount : null,
            'difference' => $s->difference !== null ? (float) $s->difference : null,
            'status' => $this->value($s->status),
            'opened_at' => $s->opened_at?->toIso8601String(),
            'closed_at' => $s->closed_at?->toIso8601String(),
            'opened_by' => $s->opened_by,
            'notes' => $s->notes,
            'movements' => $s->movements->map(fn ($m) => [
                'type' => $this->value($m->type),
                'payment_method' => $m->paymentMethod?->code,
                'amount' => (float) $m->amount,
                'description' => $m->description,
                'created_by' => $m->created_by,
                'created_at' => $m->created_at?->toIso8601String(),
            ])->values()->all(),
        ]);
    }

    private function value(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }
}
