<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Envío a provincia por agencia (Shalom): quien recoge en la agencia se
 * identifica con su DNI o CE, y el pedido lleva su departamento, provincia y
 * la agencia de destino. El costo (distinto del delivery en Lima) se cobra
 * en `delivery_fee` como cualquier envío.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_orders', function (Blueprint $table) {
            $table->string('document_type', 10)->nullable()->after('recipient_name');
            $table->string('document_number', 20)->nullable()->after('document_type');
            $table->string('department', 100)->nullable()->after('reference');
            $table->string('province', 100)->nullable()->after('department');
            $table->string('agency')->nullable()->after('province');
        });
    }

    public function down(): void
    {
        Schema::table('online_orders', function (Blueprint $table) {
            $table->dropColumn(['document_type', 'document_number', 'department', 'province', 'agency']);
        });
    }
};
