<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Demo: el aviso de turno cerrado con pendientes pasa a decir planta, turno, fecha, cuántos, motivo y quién lo cerró. */
return new class extends Migration
{
    public function up(): void
    {
        $quien = DB::table('users')->where('rol', 'auxiliar')->where('turno_habitual', 'noche')->orderBy('id')->first();
        $planta = $quien ? DB::table('plantas')->where('id', $quien->planta_id)->value('nombre') : null;
        DB::table('avisos')->where('titulo', 'Turno de noche cerrado con 2 pendientes · urgencia')->update([
            'titulo' => ($planta ?? 'Planta 2').' · noche del '.now()->subDay()->format('d/m').' · 2 sin registrar · urgencia en planta · cerrado por '.($quien->name ?? 'Rosa Delgado'),
        ]);
    }

    public function down(): void {}
};
