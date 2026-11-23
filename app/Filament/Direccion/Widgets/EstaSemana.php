<?php

namespace App\Filament\Direccion\Widgets;

use App\Models\Ausencia;
use App\Models\Pregunta;
use App\Models\Residente;
use App\Models\Resumen;
use App\Models\Turno;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

/** Esta semana: lo que vence y lo que está listo, más un resumen corto de equipo y ausencias. */
class EstaSemana extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static string $view = 'filament.direccion.widgets.esta-semana';

    protected int|string|array $columnSpan = ['xl' => 1];

    protected static ?int $sort = 2;

    /** Preguntas al turno: se leen las respuestas y se dan por resueltas, o se retira la que ya no hace falta. */
    public function preguntasAction(): Action
    {
        return Action::make('preguntas')
            ->label(fn () => self::etiquetaPreguntas())
            ->link()
            ->modalHeading('Preguntas al turno')
            ->modalDescription('Las respuestas llegan desde el relevo de las auxiliares.')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(fn () => view('filament.direccion.widgets.preguntas-turno', [
                'preguntas' => Pregunta::abiertas()->with(['residente', 'autora'])->orderByRaw('respuesta is null')->latest()->get(),
            ]));
    }

    public function resolverPregunta(int $id): void
    {
        Pregunta::whereKey($id)->update(['cerrada_at' => now()]);
        Notification::make()->title('Pregunta resuelta.')->success()->send();
    }

    public function retirarPregunta(int $id): void
    {
        Pregunta::whereKey($id)->whereNull('respuesta')->update(['cerrada_at' => now()]);
        Notification::make()->title('Pregunta retirada del relevo.')->send();
    }

    public static function etiquetaPreguntas(): string
    {
        $r = Pregunta::abiertas()->whereNotNull('respuesta')->count();
        $e = Pregunta::abiertas()->whereNull('respuesta')->count();

        return $r ? 'Leer '.($r === 1 ? 'la respuesta' : 'las respuestas') : 'Ver '.($e === 1 ? 'la pregunta' : 'las preguntas');
    }

    /** Ahora en el centro: por planta, quién está de turno y cuántos residentes llevan registro en este turno. */
    public static function ahoraPorPlanta(): array
    {
        $f = \App\Support\Franja::actual();

        return \App\Models\Planta::orderBy('nombre')->get()->map(function ($p) use ($f) {
            $abiertos = Turno::whereNull('fin')->with('user')->where(fn ($q) => $q->where('planta_id', $p->id)->orWhereNull('planta_id'))->get();
            $ids = Residente::activos()->where('estado', 'en_centro')->where('planta_id', $p->id)->pluck('id');
            $registrados = \App\Models\Registro::whereIn('residente_id', $ids)->whereIn('estado', ['confirmado', 'sin_novedad'])
                ->where('created_at', '>=', $f['inicio'])->distinct('residente_id')->count('residente_id');

            return ['id' => $p->id, 'planta' => $p->nombre, 'franja' => $f['nombre'], 'personas' => $abiertos->map(fn ($t) => $t->user)->filter()->unique('id')->values(),
                'registrados' => $registrados, 'total' => $ids->count()];
        })->all();
    }

    /** «Nadie ha abierto turno» se puede pulsar: quién debería estar y cómo localizarla. */
    public function quienFaltaAction(): Action
    {
        return Action::make('quienFalta')
            ->label('Nadie ha abierto turno · ver quién falta')
            ->link()
            ->color('danger')
            ->modalHeading('¿Quién debería estar?')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(function (array $arguments) {
                $p = \App\Models\Planta::find($arguments['planta'] ?? null);

                return view('filament.direccion.widgets.quien-falta', [
                    'planta' => $p,
                    'personas' => $p ? \App\Support\TurnosSinAbrir::quienDeberia($p) : collect(),
                    'franja' => \App\Support\Franja::actual(),
                ]);
            });
    }

    protected function getViewData(): array
    {
        return [
            'sinEmergencia' => Residente::activos()->whereDoesntHave('contactos', fn ($q) => $q->where('emergencia', true))->count(),
            'resumenesPorRevisar' => Resumen::where('estado', 'por_revisar')->count(),
            'revisiones' => Residente::activos()->whereBetween('plan_proxima_revision', [now(), now()->addDays(7)])->get(),
            'esperando' => Pregunta::abiertas()->whereNull('respuesta')->count(),
            'respondidas' => Pregunta::abiertas()->whereNotNull('respuesta')->count(),
            'plantas' => self::ahoraPorPlanta(),
            'sanitarios' => \App\Support\Sanitarios::ahora(),
            'fuera' => Ausencia::where('ha_vuelto', false)->with('residente')->orderBy('vuelve')->get(),
        ];
    }
}
