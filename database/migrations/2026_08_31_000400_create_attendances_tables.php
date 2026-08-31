<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 7 — Registro de atenciones (§15, §19).
 *
 * `attendance_supplies` guarda la cantidad realmente utilizada, que puede
 * diferir de la cantidad referencial configurada en `service_supplies`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('client_package_id')->nullable()->constrained('client_packages')->nullOnDelete();
            $table->unsignedInteger('session_number')->nullable();
            $table->date('attended_at')->index();
            $table->text('observations')->nullable();
            $table->text('measurements')->nullable();
            // Auditoría básica (§31): quién registró la atención.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['client_id', 'attended_at']);
            $table->index(['employee_id', 'attended_at']);
        });

        Schema::create('attendance_supplies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('attendances')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->timestamps();

            $table->unique(['attendance_id', 'inventory_item_id']);
        });

        // La sesión de paquete apunta a la atención que la consumió. Se añade
        // aquí porque `attendances` aún no existía al crear esa tabla.
        Schema::table('client_package_sessions', function (Blueprint $table) {
            $table->foreign('attendance_id')->references('id')->on('attendances')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('client_package_sessions', function (Blueprint $table) {
            $table->dropForeign(['attendance_id']);
        });

        Schema::dropIfExists('attendance_supplies');
        Schema::dropIfExists('attendances');
    }
};
