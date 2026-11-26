<?php

use App\Support\Demo\AvisosTransversales;
use Illuminate\Database\Migrations\Migration;

/** Añade los avisos transversales de ejemplo a una base ya sembrada (en una nueva los pone el seeder). */
return new class extends Migration
{
    public function up(): void
    {
        if (\App\Models\Residente::exists()) {
            AvisosTransversales::sembrar();
        }
    }

    public function down(): void
    {
        \App\Models\Aviso::whereIn('tipo', ['equipo', 'familia', 'organizacion'])->delete();
        \App\Models\Aviso::where('titulo', 'like', 'Caídas de noche en Planta 2%')->delete();
    }
};
