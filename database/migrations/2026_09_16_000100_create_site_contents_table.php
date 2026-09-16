<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Textos e imágenes editables de la web pública (portada, líneas de
 * tratamiento, foto de Nosotros...).
 *
 * La lista de espacios vive en config/site_contents.php: la tabla solo guarda
 * lo que el administrador cambió, y lo que falte usa el valor por defecto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_contents', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('title')->nullable();
            $table->string('text', 500)->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_contents');
    }
};
