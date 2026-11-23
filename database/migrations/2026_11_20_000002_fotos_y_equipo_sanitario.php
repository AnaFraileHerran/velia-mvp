<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fotos (avatar) de residentes y equipo. Enfermería y medicina pasan a ser personas del equipo
 * (rol «sanitario», sin acceso a la app) con su horario, para verlas en Equipo y en «Mi turno».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('residentes', function (Blueprint $table) {
            $table->string('foto')->nullable();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('foto')->nullable();
            $table->string('puesto')->nullable();   // enfermeria | medicina | fisioterapia…
            $table->json('dias')->nullable();       // 1 = lunes … 7 = domingo
            $table->string('desde', 5)->nullable();
            $table->string('hasta', 5)->nullable();
            $table->string('contacto')->nullable(); // extensión o teléfono
        });
    }

    public function down(): void
    {
        Schema::table('residentes', fn (Blueprint $table) => $table->dropColumn('foto'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['foto', 'puesto', 'dias', 'desde', 'hasta', 'contacto']));
    }
};
