<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de comunicaciones con la familia (26/11/2026, decisión de Ana tras el análisis de competencia).
 * La guía de autoevaluación 2026 de la Comunidad de Madrid pide registrar cada comunicación con la familia:
 * fecha, qué se comunicó y a quién (como mínimo revisiones del plan, caídas, derivaciones y seguimiento).
 * El resumen semanal enviado es una comunicación más.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comunicaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residente_id')->constrained('residentes')->cascadeOnDelete();
            $table->foreignId('contacto_id')->nullable()->constrained('contactos')->nullOnDelete();
            $table->string('a_quien');                 // nombre y relación, tal como se informó
            $table->string('motivo');                  // clave de Comunicacion::MOTIVOS
            $table->string('canal');                   // clave de Comunicacion::CANALES
            $table->text('que');                       // qué se comunicó
            $table->dateTime('fecha');
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('aviso_id')->nullable()->constrained('avisos')->nullOnDelete();
            $table->foreignId('resumen_id')->nullable()->constrained('resumenes')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comunicaciones');
    }
};
