<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 5 — Inventario (§24).
 *
 * El stock nunca se edita a mano: `inventory_items.stock` es el saldo
 * consolidado y cada cambio deja un registro en `inventory_movements`, de modo
 * que el saldo siempre puede reconstruirse desde los movimientos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->foreignId('category_id')->nullable()
                ->constrained('inventory_categories')->nullOnDelete();
            $table->string('unit', 20)->default('unidad');
            // Decimal y no float: el stock puede llevar fracciones (ml, gr) y
            // los montos monetarios nunca deben usar coma flotante (§34).
            $table->decimal('stock', 12, 2)->default(0)->index();
            $table->decimal('min_stock', 12, 2)->default(0);
            $table->decimal('cost', 10, 2)->default(0);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->string('supplier')->nullable();
            // Distingue un producto vendible de un insumo de uso interno.
            $table->boolean('is_sellable')->default(false)->index();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('type', 20)->index();
            // Positiva en entradas, negativa en salidas: el saldo es la suma.
            $table->decimal('quantity', 12, 2);
            $table->decimal('stock_after', 12, 2);
            $table->decimal('unit_cost', 10, 2)->nullable();
            // Origen del movimiento (atención, venta, ajuste...) sin acoplar
            // el inventario a cada módulo que lo consume.
            $table->nullableMorphs('source');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['inventory_item_id', 'created_at']);
        });

        Schema::create('service_supplies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->decimal('default_quantity', 12, 2)->default(1);
            $table->timestamps();

            $table->unique(['service_id', 'inventory_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_supplies');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventory_categories');
    }
};
