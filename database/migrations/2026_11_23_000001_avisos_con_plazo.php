<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Avisos asignados con plazo: si vencen sin hacerse, vuelven a «Hoy necesita tu decisión» (decisión UX 23/11/2026). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avisos', function (Blueprint $table) {
            $table->date('vence_el')->nullable()->after('responsable_id');
            $table->timestamp('hecho_at')->nullable()->after('vence_el');
        });

        // «¿Tienes otra residencia?»: se recoge la solicitud y ICARUS la da de alta a mano (opción B del piloto).
        Schema::create('solicitudes_residencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residencia_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('nombre');
            $table->unsignedSmallInteger('plazas')->nullable();
            $table->string('municipio')->nullable();
            $table->string('estado')->default('recibida');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_residencia');
        Schema::table('avisos', function (Blueprint $table) {
            $table->dropColumn(['vence_el', 'hecho_at']);
        });
    }
};
