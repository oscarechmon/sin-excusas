<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 6 — Paquetes (§18).
 *
 * `packages` es el catálogo configurable por el administrador.
 * `client_packages` es la compra concreta de un cliente, con su propio saldo
 * de sesiones; cada consumo queda registrado en `client_package_sessions` para
 * conservar trazabilidad en lugar de solo restar un contador.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('total_sessions');
            $table->unsignedInteger('validity_days')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('package_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['package_id', 'service_id']);
        });

        Schema::create('client_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            // El paquete de catálogo puede cambiar o borrarse; el nombre y el
            // precio se congelan en la compra para conservar el histórico.
            $table->foreignId('package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->string('package_name');
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('total_sessions');
            $table->unsignedInteger('used_sessions')->default(0);
            $table->date('purchased_at');
            $table->date('expires_at')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();

            $table->index(['client_id', 'status']);
        });

        Schema::create('client_package_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_package_id')->constrained('client_packages')->cascadeOnDelete();
            $table->foreignId('attendance_id')->nullable();
            $table->unsignedInteger('session_number');
            $table->timestamp('consumed_at');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Impide consumir dos veces la misma sesión de un paquete, incluso
            // ante un doble clic o una petición repetida (§47).
            $table->unique(['client_package_id', 'session_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_package_sessions');
        Schema::dropIfExists('client_packages');
        Schema::dropIfExists('package_services');
        Schema::dropIfExists('packages');
    }
};
