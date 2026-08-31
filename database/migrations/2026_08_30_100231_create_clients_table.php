<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->index();
            $table->string('full_name')->index();
            $table->string('document_number')->nullable()->unique()->index();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['M', 'F', 'O'])->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('district')->nullable();
            $table->text('address')->nullable();
            $table->string('how_knew')->nullable();
            $table->text('observations')->nullable();
            $table->text('allergies')->nullable();
            $table->text('restrictions')->nullable();
            $table->text('contraindications')->nullable();
            $table->text('medications')->nullable();
            $table->text('relevant_info')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
