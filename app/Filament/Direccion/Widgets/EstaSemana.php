<?php

namespace App\Filament\Direccion\Widgets;

use App\Models\Ausencia;
use App\Models\Pregunta;
use App\Models\Residente;
use App\Models\Resumen;
use App\Models\Turno;
use Filament\Widgets\Widget;

/** Esta semana: lo que vence y lo que está listo, más un resumen corto de equipo y ausencias. */
class EstaSemana extends Widget
{
    protected static string $view = 'filament.direccion.widgets.esta-semana';

    protected int|string|array $columnSpan = ['md' => 1];

    protected static ?int $sort = 2;

    protected function getViewData(): array
    {
        return [
            'resumenesPorRevisar' => Resumen::where('estado', 'por_revisar')->count(),
            'revisiones' => Residente::activos()->whereBetween('plan_proxima_revision', [now(), now()->addDays(7)])->get(),
            'preguntas' => Pregunta::whereNull('respuesta')->count(),
            'enTurno' => Turno::whereNull('fin')->with('user')->get(),
            'fuera' => Ausencia::where('ha_vuelto', false)->count(),
            'vuelvenHoy' => Ausencia::where('ha_vuelto', false)->whereDate('vuelve', today())->with('residente')->get(),
        ];
    }
}
