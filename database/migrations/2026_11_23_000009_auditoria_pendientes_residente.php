<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pendientes de la UNE en la ficha (23/11/2026): «Sujeción: sin anotar» se distingue de «No usa» (5.5), y el profesional
 * de referencia del plan personal (5.3) cuenta como pendiente si falta. Demo: la mayoría completos, algunos pendientes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('residentes', function (Blueprint $table) {
            $table->boolean('sujecion_anotada')->default(false)->after('sujecion');
        });

        $sanitario = DB::table('users')->where('rol', 'sanitario')->where('puesto', 'enfermeria')->orderBy('id')->value('id');
        $aux = DB::table('users')->where('rol', 'auxiliar')->orderBy('id')->pluck('id')->all();
        foreach (DB::table('residentes')->orderBy('id')->get() as $i => $r) {
            $datos = [];
            if ($i < 9) {
                $datos['sujecion_anotada'] = true;
            }
            if ($i < 8 && ! $r->plan_responsable_id) {
                $datos['plan_responsable_id'] = $i % 2 === 0 && $sanitario ? $sanitario : ($aux[$i % max(1, count($aux))] ?? null);
            }
            if ($datos) {
                DB::table('residentes')->where('id', $r->id)->update($datos);
            }
        }
    }

    public function down(): void
    {
        Schema::table('residentes', function (Blueprint $table) {
            $table->dropColumn('sujecion_anotada');
        });
    }
};
