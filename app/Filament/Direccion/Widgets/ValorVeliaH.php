<?php

namespace App\Filament\Direccion\Widgets;

use App\Models\Registro;
use App\Models\Residente;
use App\Models\Resumen;

/**
 * Flujo nuevo (versión H, 25/11/2026, comentario de Pablo en «Últimos 7 días»): además de cómo va el servicio,
 * lo que Velia ha hecho por la residencia. Solo cifras reales de los registros; lo que aún no se puede medir
 * se enseña como «Ejemplo» o «Sin datos aún» (regla 11 e hipótesis H2). Hereda los «Ver cuáles» de ComoVaLaSemana.
 */
class ValorVeliaH extends ComoVaLaSemana
{
    protected string $view = 'filament.direccion.widgets.valor-velia-h';

    /** Minutos que se supone que ahorra cada registro frente al papel. [SUPOSICIÓN] hasta medirlo en el piloto. */
    public const MINUTOS_POR_REGISTRO = 3;

    protected function getViewData(): array
    {
        $registros = Registro::whereIn('estado', ['confirmado', 'sin_novedad'])->where('created_at', '>=', now()->subDays(30))->count();
        $residentes = max(1, Residente::activos()->count());

        return [
            'registros' => $registros,
            'porResidente' => round($registros / $residentes / 30, 1),
            'enviados' => Resumen::where('estado', 'enviado')->count(),
            'horas' => round($registros * self::MINUTOS_POR_REGISTRO / 60),
        ];
    }
}
