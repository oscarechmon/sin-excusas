<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tienda online.
 *
 * - `client_users`: cuentas de la web. Separadas de `users` (personal del ERP)
 *   y vinculadas a la ficha `clients`, para que el centro vea en el ERP a
 *   quien compra por la web.
 * - `online_orders` y sus ítems: el pedido congela nombre y precio de cada
 *   artículo, igual que las ventas del ERP, para conservar el histórico.
 * - `online_order_status_histories`: el seguimiento que ve el cliente.
 * - `online_payments`: cada respuesta de la pasarela, aprobada o no.
 * - `store_settings`: configuración clave/valor (costo de delivery).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 30)->nullable();
            $table->string('document_number', 20)->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('store_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('online_orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('client_user_id')->constrained('client_users')->restrictOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('status', 30)->index();
            $table->string('fulfillment', 20);
            $table->string('recipient_name');
            $table->string('phone', 30);
            $table->string('address')->nullable();
            $table->string('district', 100)->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamps();

            $table->index(['client_user_id', 'created_at']);
        });

        Schema::create('online_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('online_order_id')->constrained('online_orders')->cascadeOnDelete();
            // Servicio o producto de origen; puede borrarse sin perder el pedido.
            $table->nullableMorphs('itemable');
            $table->string('item_type', 20);
            $table->string('name');
            $table->decimal('unit_price', 10, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });

        Schema::create('online_order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('online_order_id')->constrained('online_orders')->cascadeOnDelete();
            $table->string('status', 30);
            $table->text('note')->nullable();
            // Las notas internas (p. ej. "revisar stock") no las ve el cliente.
            $table->boolean('internal')->default(false);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('online_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('online_order_id')->constrained('online_orders')->cascadeOnDelete();
            $table->string('gateway', 30);
            $table->string('status', 20);
            $table->decimal('amount', 10, 2);
            $table->string('transaction_id')->nullable()->index();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_payments');
        Schema::dropIfExists('online_order_status_histories');
        Schema::dropIfExists('online_order_items');
        Schema::dropIfExists('online_orders');
        Schema::dropIfExists('store_settings');
        Schema::dropIfExists('client_users');
    }
};
