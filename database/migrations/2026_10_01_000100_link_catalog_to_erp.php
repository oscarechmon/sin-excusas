<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enlace con el sistema (ERP), que pasa a ser el dueño del catálogo y del stock.
 *
 * - `erp_id`: el producto o servicio del sistema del que esta fila es copia.
 * - `erp_sale_status`: si el pedido pagado ya es una venta del sistema
 *   (registered) o falta registrarla porque el sistema no contestó (pending);
 *   la sincronización reintenta los pendientes. Vacío en los pedidos anteriores
 *   a la integración: esos ya descontaron su stock aquí y no se envían.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->unsignedBigInteger('erp_id')->nullable()->unique()->after('id');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->unsignedBigInteger('erp_id')->nullable()->unique()->after('id');
        });

        Schema::table('online_orders', function (Blueprint $table) {
            $table->string('erp_sale_status', 20)->nullable()->after('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::table('online_orders', function (Blueprint $table) {
            $table->dropColumn('erp_sale_status');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropUnique(['erp_id']);
            $table->dropColumn('erp_id');
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropUnique(['erp_id']);
            $table->dropColumn('erp_id');
        });
    }
};
