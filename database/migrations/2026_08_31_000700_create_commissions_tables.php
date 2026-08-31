<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 10 — Comisiones (§26).
 *
 * `commission_rules` es configurable por el administrador. `commissions`
 * guarda además del porcentaje el monto ya calculado: si la regla cambia
 * después, las comisiones históricas no se alteran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            // Ambos nulos = regla por defecto para todo el centro. Cuanto más
            // específica sea la regla, mayor prioridad al resolverla.
            $table->foreignId('employee_id')->nullable()->constrained('employees')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->cascadeOnDelete();
            $table->string('type', 20);
            $table->decimal('value', 10, 2);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->unique(['employee_id', 'service_id']);
        });

        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained('attendances')->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->decimal('base_amount', 12, 2);
            // Tipo y valor de la regla aplicada, copiados al generar.
            $table->string('type', 20);
            $table->decimal('value', 10, 2);
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('pending')->index();
            $table->date('generated_at')->index();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            // Una atención genera como máximo una comisión (§47).
            $table->unique('attendance_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
        Schema::dropIfExists('commission_rules');
    }
};
