<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->foreignId('category_id')->constrained('service_categories')->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->integer('duration_minutes')->default(60);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('employee_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['employee_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_service');
        Schema::dropIfExists('services');
        Schema::dropIfExists('service_categories');
    }
};
