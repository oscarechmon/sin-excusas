<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Separa el personal de los usuarios del sistema (§25 de la especificación).
 *
 * Hasta ahora `appointments.employee_id` y `employee_service.employee_id`
 * apuntaban a `users`, lo que obligaba a crear una cuenta para cada trabajador.
 * Esta migración crea `employees`, genera un registro por cada usuario ya
 * referenciado y repunta ambas claves foráneas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('position')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('document_number', 20)->nullable()->index();
            // Un trabajador puede no tener acceso al sistema; si lo tiene,
            // es una relación uno a uno con users.
            $table->foreignId('user_id')->nullable()->unique()
                ->constrained('users')->nullOnDelete();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        $map = $this->backfillEmployeesFromUsers();

        $this->repointForeignKey('appointments', $map);
        $this->repointForeignKey('employee_service', $map);
    }

    public function down(): void
    {
        foreach (['appointments', 'employee_service'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['employee_id']);
            });
        }

        // Se devuelven las FK a users usando el user_id guardado en employees.
        DB::statement('UPDATE appointments a JOIN employees e ON a.employee_id = e.id SET a.employee_id = e.user_id WHERE e.user_id IS NOT NULL');
        DB::statement('UPDATE employee_service es JOIN employees e ON es.employee_id = e.id SET es.employee_id = e.user_id WHERE e.user_id IS NOT NULL');

        foreach (['appointments', 'employee_service'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreign('employee_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        Schema::dropIfExists('employees');
    }

    /** @return array<int,int> user_id => employee_id */
    private function backfillEmployeesFromUsers(): array
    {
        $userIds = DB::table('appointments')->distinct()->pluck('employee_id')
            ->merge(DB::table('employee_service')->distinct()->pluck('employee_id'))
            ->filter()
            ->unique();

        $map = [];

        foreach ($userIds as $userId) {
            $user = DB::table('users')->find($userId);
            if (! $user) {
                continue;
            }

            $map[$userId] = DB::table('employees')->insertGetId([
                'name' => $user->name,
                'user_id' => $user->id,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $map;
    }

    /** @param array<int,int> $map */
    private function repointForeignKey(string $table, array $map): void
    {
        Schema::table($table, function (Blueprint $t) {
            $t->dropForeign(['employee_id']);
        });

        foreach ($map as $userId => $employeeId) {
            DB::table($table)->where('employee_id', $userId)->update(['employee_id' => $employeeId]);
        }

        Schema::table($table, function (Blueprint $t) {
            $t->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });
    }
};
