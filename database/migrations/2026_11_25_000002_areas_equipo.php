<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Otros roles del equipo (25/11/2026, comentario de Ana en EQ-01): la dirección crea un área (Ocio, Fisioterapia, Cocina…)
 * y añade la ficha de cada persona en ella. Como enfermería y medicina: no entran en la app (rol «otro»).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas_equipo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('area_equipo_id')->nullable()->constrained('areas_equipo')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('area_equipo_id'));
        Schema::dropIfExists('areas_equipo');
    }
};
