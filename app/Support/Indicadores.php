<?php

namespace App\Support;

use App\Models\Registro;
use App\Models\Residente;

/**
 * Indicadores de calidad del servicio (26/11/2026, acordado con Ana en CAL-01): una sola fuente para el Inicio y Calidad,
 * para que el mismo dato tenga el mismo nombre y la misma cifra en los dos sitios. En el Inicio, el resumen; en Calidad, el detalle.
 * Lo que aún no se calcula de verdad sale como «Ejemplo» o «—» (regla 11, hipótesis H2).
 */
class Indicadores
{
    /** @return array<string, array{nombre: string, cifra: string, de: ?string, nota: string, detalle: string, estado: string, pct: ?int}> */
    public static function lista(): array
    {
        $s = Semana::resumen();
        $activos = Residente::activos()->count();
        $planesAlDia = Residente::activos()->whereNotNull('plan_proxima_revision')->whereDate('plan_proxima_revision', '>=', today())->count();
        $piel = Registro::where('incidencia_destacada', true)->where('tipo_incidencia', 'like', 'Piel%')->where('created_at', '>=', now()->subDays(30))->count();

        return [
            'turnos' => ['nombre' => 'Turnos completos', 'cifra' => (string) $s['completos'], 'de' => '/'.$s['turnos'], 'nota' => 'últimos 7 días',
                'detalle' => 'Turnos en los que todos los residentes tienen registro o «sin novedad». Es la evidencia del apartado 5.4 de la UNE.',
                'estado' => 'real', 'pct' => $s['turnos'] ? (int) round($s['completos'] / $s['turnos'] * 100) : 0],
            'incidencias' => ['nombre' => 'Incidencias', 'cifra' => (string) $s['incidencias'], 'de' => null,
                'nota' => ($s['diferencia'] > 0 ? '↑ '.$s['diferencia'] : ($s['diferencia'] < 0 ? '↓ '.abs($s['diferencia']) : '=')).' frente a la semana anterior',
                'detalle' => 'Registros marcados como incidencia en los últimos 7 días (caídas, piel, alimentación, conducta).', 'estado' => 'real', 'pct' => null],
            'planes' => ['nombre' => 'Planes al día', 'cifra' => (string) $planesAlDia, 'de' => '/'.$activos, 'nota' => 'revisión no vencida',
                'detalle' => 'Residentes con la próxima revisión del plan personal en fecha (la norma pide revisarlo cada 6 meses).', 'estado' => 'real',
                'pct' => $activos ? (int) round($planesAlDia / $activos * 100) : 0],
            'piel' => ['nombre' => 'Incidencias de piel', 'cifra' => (string) $piel, 'de' => null, 'nota' => 'últimos 30 días',
                'detalle' => 'Registros de piel y úlceras. Apartado 5.5 (atención sanitaria).', 'estado' => 'real', 'pct' => null],
            'caidas' => ['nombre' => 'Caídas por 1.000 estancias', 'cifra' => '—', 'de' => null, 'nota' => 'falta confirmar cómo se cuenta',
                'detalle' => 'Se calculará con tus registros cuando confirmemos con la norma la fórmula.', 'estado' => 'ejemplo', 'pct' => null],
            'quejas' => ['nombre' => 'Quejas resueltas', 'cifra' => '—', 'de' => null, 'nota' => 'Velia aún no lo registra',
                'detalle' => 'Apartado 10 de la norma. Hoy lo lleva tu programa de gestión o tu consultora.', 'estado' => 'no_mide', 'pct' => null],
        ];
    }
}
