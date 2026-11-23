<?php

namespace App\Filament\Direccion\Widgets;

use App\Support\TurnosSinAbrir;
use App\Models\Planta;
use App\Support\Franja;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Facades\Filament;
use App\Models\Aviso;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

/**
 * Alerta bajo la fecha (23/11/2026): planta sin turno abierto a los 30 min. Único sitio donde se actúa sobre ello
 * (la banda solo dice el estado y «Hoy» ya no lo repite). No aparece si todo está cubierto.
 */
class AlertaTurno extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected string $view = 'filament.direccion.widgets.alerta-turno';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -1;

    public function getAvisos()
    {
        TurnosSinAbrir::comprobar();

        return Aviso::where('tipo', 'turno_sin_abrir')->where('estado', 'pendiente')->get();
    }

    public function quienFaltaAction(): Action
    {
        return Action::make('quienFalta')
            ->label('Ver quién falta')
            ->icon('heroicon-o-user-group')
            ->button()
            ->color('danger')
            ->modalHeading('¿Quién debería estar?')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(function (array $arguments) {
                $p = Planta::find($arguments['planta'] ?? null);

                return view('filament.direccion.widgets.quien-falta', [
                    'planta' => $p,
                    'personas' => $p ? TurnosSinAbrir::quienDeberia($p) : collect(),
                    'franja' => Franja::actual(),
                ]);
            });
    }

    /** El turno sí está cubierto pero no se abrió en Velia: se anota con nombre, fecha y motivo. */
    public function noProcedeAction(): Action
    {
        return Action::make('noProcede')
            ->label('No procede')
            ->link()
            ->color('danger')
            ->modalHeading('¿Por qué no procede?')
            ->modalDescription('La alerta se quita y queda anotado con tu nombre, la fecha y el motivo.')
            ->modalSubmitActionLabel('Guardar')
            ->schema([
                Radio::make('motivo')->label('Motivo')->required()->options([
                    'cubierto' => 'El turno está cubierto, pero no se ha abierto en Velia',
                    'sin_residentes' => 'Hoy no hay residentes en esa planta',
                    'otro' => 'Otro motivo',
                ])->live(),
                TextInput::make('texto')->label('Cuéntalo en pocas palabras')
                    ->visible(fn (Get $get) => $get('motivo') === 'otro')->required(fn (Get $get) => $get('motivo') === 'otro'),
            ])
            ->action(function (array $data, array $arguments) {
                $textos = ['cubierto' => 'Turno cubierto sin abrir en Velia', 'sin_residentes' => 'Sin residentes en la planta', 'otro' => 'Otro motivo'];
                Aviso::whereKey($arguments['aviso'])->update([
                    'estado' => 'descartado', 'revisado_por' => Filament::auth()->id(), 'revisado_at' => now(),
                    'motivo_descarte' => $textos[$data['motivo']].(($data['texto'] ?? '') !== '' ? ': '.$data['texto'] : ''),
                ]);
                Notification::make()->title('Anotado como «no procede».')->send();
            });
    }
}
