<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Todo lo operativo pasa al sistema (ERP); aquí quedan copias enlazadas:
 *
 * - `clients.erp_id`: el cliente del sistema (las cuentas de la tienda siguen
 *   aquí y sus pedidos llegan allá a su nombre).
 * - `packages.erp_id`: el producto del paquete en el sistema, como ya hacen
 *   productos y servicios. Sesiones, precio y servicios llegan de allá.
 * - El seguimiento de un pedido lo mueve el sistema: esos pasos se marcan
 *   (`from_erp`) con el nombre de quien lo hizo allá, y no se le devuelven.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedBigInteger('erp_id')->nullable()->unique()->after('id');
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->unsignedBigInteger('erp_id')->nullable()->unique()->after('id');
        });

        Schema::table('online_order_status_histories', function (Blueprint $table) {
            $table->boolean('from_erp')->default(false)->after('internal');
            $table->string('actor_name')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('online_order_status_histories', function (Blueprint $table) {
            $table->dropColumn(['from_erp', 'actor_name']);
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->dropUnique(['erp_id']);
            $table->dropColumn('erp_id');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique(['erp_id']);
            $table->dropColumn('erp_id');
        });
    }
};
