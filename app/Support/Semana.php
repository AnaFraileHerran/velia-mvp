<?php

namespace App\Support;

use App\Models\Registro;
use App\Models\Residente;
use App\Models\Turno;

/** Cómo va la semana: cobertura de registro, turnos cerrados con pendientes e incidencias. */
class Semana
{
    public static function resumen(): array
    {
        $desde = now()->subDays(7);
        $turnos = Turno::where('fin', '>=', $desde)->get();
        $conPendientes = $turnos->whereNotNull('motivo_cierre');
        $motivo = $conPendientes->countBy('motivo_cierre')->sortDesc()->keys()->first();
        $inc = Registro::where('incidencia_destacada', true)->where('created_at', '>=', $desde)->count();
        $incAnterior = Registro::where('incidencia_destacada', true)->whereBetween('created_at', [now()->subDays(14), $desde])->count();
        $residentes = Residente::activos()->where('estado', 'en_centro')->count();
        $registrados = Registro::where('created_at', '>=', now()->startOfDay())->distinct('residente_id')->count('residente_id');

        return [
            'cobertura' => $residentes ? round($registrados / $residentes * 100) : 0,
            'turnos' => $turnos->count(),
            'con_pendientes' => $conPendientes->count(),
            'motivo' => $motivo ? (Turno::MOTIVOS_CIERRE[$motivo] ?? $motivo) : null,
            'incidencias' => $inc,
            'diferencia' => $inc - $incAnterior,
        ];
    }
}
