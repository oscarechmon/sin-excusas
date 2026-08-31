<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 8 — Ventas y pagos (§21, §22).
 *
 * Cabecera (`sales`) y detalle (`sale_items`). El detalle usa una relación
 * polimórfica porque un ítem puede ser un servicio, un producto de inventario
 * o un paquete, y congela descripción y precio para conservar el histórico
 * aunque el catálogo cambie después.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 30)->unique();
            // Yape, Plin o transferencia necesitan número de operación.
            $table->boolean('requires_reference')->default(false);
            // Solo los métodos en efectivo afectan el arqueo físico de caja.
            $table->boolean('is_cash')->default(false);
            $table->boolean('active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            // Saldo consolidado; el detalle vive en `payments`.
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('status', 20)->default('pending')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['client_id', 'created_at']);
            $table->index('created_at');
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->morphs('itemable');
            $table->string('description');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            // Comisiona el especialista que ejecutó el servicio, no quien vendió.
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained('payment_methods');
            $table->decimal('amount', 12, 2);
            $table->string('reference')->nullable();
            $table->timestamp('paid_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('paid_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('payment_methods');
    }
};
