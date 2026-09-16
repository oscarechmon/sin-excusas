<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Publicación en la web pública.
 *
 * `active` indica si el registro se usa en el ERP; `is_published` decide si
 * además se muestra en el catálogo del sitio. Son independientes: un servicio
 * puede venderse en el centro sin anunciarse todavía en la web. Por defecto
 * nada se publica, para que la web no exponga precios sin revisión.
 */
return new class extends Migration
{
    private const TABLES = ['services', 'packages', 'inventory_items'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->boolean('is_published')->default(false)->after('active')->index();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropIndex(['is_published']);
                $table->dropColumn('is_published');
            });
        }
    }
};
