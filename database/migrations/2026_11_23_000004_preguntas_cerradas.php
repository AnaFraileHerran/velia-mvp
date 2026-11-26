<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** La dirección da por resuelta (o retira) una pregunta al turno desde Inicio. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preguntas', function (Blueprint $table) {
            $table->timestamp('cerrada_at')->nullable()->after('respondida_por');
        });
    }

    public function down(): void
    {
        Schema::table('preguntas', function (Blueprint $table) {
            $table->dropColumn('cerrada_at');
        });
    }
};
