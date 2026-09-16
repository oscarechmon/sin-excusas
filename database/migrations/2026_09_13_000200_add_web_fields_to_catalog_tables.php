<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos que solo usa la web pública: la foto de cada servicio y producto, y
 * una descripción corta para los productos (los servicios ya la tenían).
 * La imagen se guarda como ruta relativa al disco `public`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('description');
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('description', 500)->nullable()->after('name');
            $table->string('image_path')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn(['description', 'image_path']);
        });
    }
};
