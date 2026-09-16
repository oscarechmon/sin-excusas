<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Verificación de correo y acceso con Google para las cuentas web.
 *
 * - El código de verificación se guarda hasheado, como una contraseña: quien
 *   lea la base de datos no puede usarlo.
 * - La contraseña pasa a ser opcional porque quien entra con Google no la tiene.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('avatar_url')->nullable()->after('google_id');
            $table->timestamp('email_verified_at')->nullable()->after('avatar_url');
            $table->string('verification_code')->nullable()->after('email_verified_at');
            $table->timestamp('verification_code_expires_at')->nullable()->after('verification_code');
            $table->timestamp('verification_sent_at')->nullable()->after('verification_code_expires_at');
            $table->unsignedTinyInteger('verification_attempts')->default(0)->after('verification_sent_at');
        });

        // Las cuentas creadas antes de exigir verificación no quedan bloqueadas.
        DB::table('client_users')->whereNull('email_verified_at')->update(['email_verified_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('client_users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropColumn([
                'google_id',
                'avatar_url',
                'email_verified_at',
                'verification_code',
                'verification_code_expires_at',
                'verification_sent_at',
                'verification_attempts',
            ]);
        });
    }
};
