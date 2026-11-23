<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** «No procede» sustituye a «Cerrar»: también deja rastro (quién, cuándo y por qué). Decisión 23/11/2026, opción C. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avisos', function (Blueprint $table) {
            $table->string('motivo_descarte')->nullable()->after('revisado_at');
        });
    }

    public function down(): void
    {
        Schema::table('avisos', function (Blueprint $table) {
            $table->dropColumn('motivo_descarte');
        });
    }
};
