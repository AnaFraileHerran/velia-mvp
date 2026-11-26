<?php

namespace App\Support;

use App\Models\Registro;
use App\Models\Turno;

/**
 * Cómo va la semana (últimos 7 días frente a los 7 anteriores). Solo datos de semana: lo de hoy va en «Ahora en el centro».
 * Turnos cerrados completos, motivo más frecuente de los que no, e incidencias por tipo (lo que importa para el cuidado y la UNE).
 */
class Semana
{
    public static function resumen(): array
    {
        $desde = now()->subDays(7);
        $antes = now()->subDays(14);
        $turnos = Turno::whereNotNull('fin')->where('fin', '>=', $desde)->get();
        $conPendientes = $turnos->whereNotNull('motivo_cierre');
        $motivo = $conPendientes->countBy('motivo_cierre')->sortDesc()->keys()->first();

        $porTipo = fn ($de, $a) => Registro::where('incidencia_destacada', true)->whereBetween('created_at', [$de, $a])
            ->get()->countBy(fn ($r) => $r->tipo_incidencia ?: 'Otra');
        $ahora = $porTipo($desde, now());
        $previa = $porTipo($antes, $desde);
        $tipos = $ahora->keys()->merge($previa->keys())->unique()
            ->map(fn ($t) => ['tipo' => $t, 'n' => $ahora->get($t, 0), 'antes' => $previa->get($t, 0)])
            ->sortByDesc('n')->values();

        return [
            'turnos' => $turnos->count(),
            'completos' => $turnos->count() - $conPendientes->count(),
            'con_pendientes' => $conPendientes->count(),
            'motivo' => $motivo ? (Turno::MOTIVOS_CIERRE[$motivo] ?? $motivo) : null,
            'incidencias' => $ahora->sum(),
            'diferencia' => $ahora->sum() - $previa->sum(),
            'tipos' => $tipos,
        ];
    }

    public static function incidencias()
    {
        return Registro::with(['residente', 'user'])->where('incidencia_destacada', true)
            ->where('created_at', '>=', now()->subDays(7))->latest()->get();
    }

    public static function turnosConPendientes()
    {
        return Turno::with(['user', 'planta'])->whereNotNull('fin')->whereNotNull('motivo_cierre')
            ->where('fin', '>=', now()->subDays(7))->latest('inicio')->get();
    }
}
