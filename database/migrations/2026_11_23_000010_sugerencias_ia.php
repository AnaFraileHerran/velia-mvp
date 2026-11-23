<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Rastro de las sugerencias de la IA: qué propuso, en qué flujo y qué decidió la persona (aceptada, cambiada o descartada). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sugerencias_ia', function (Blueprint $t) {
            $t->id();
            $t->string('flujo');
            $t->nullableMorphs('objeto');
            $t->text('sugerencia');
            $t->string('decision');
            $t->text('detalle')->nullable();
            $t->foreignId('user_id')->nullable();
            $t->string('proveedor')->default('simulado');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sugerencias_ia');
    }
};
