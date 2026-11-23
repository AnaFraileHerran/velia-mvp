<?php

namespace App\Filament\Direccion\Widgets;

use App\Support\Semana;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;

/** Inicio · Cómo va la semana: tendencia de 7 días. Cada cifra se abre para ver de qué está hecha (decisión 23/11/2026). */
class ComoVaLaSemana extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static string $view = 'filament.direccion.widgets.como-va-la-semana';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function verIncidenciasAction(): Action
    {
        return Action::make('verIncidencias')->label('Ver cuáles')->link()
            ->modalHeading('Incidencias de los últimos 7 días')
            ->modalSubmitAction(false)->modalCancelActionLabel('Cerrar')
            ->modalContent(fn () => view('filament.direccion.widgets.lista-incidencias', ['incidencias' => Semana::incidencias()]));
    }

    public function verTurnosAction(): Action
    {
        return Action::make('verTurnos')->label('Ver cuáles')->link()
            ->modalHeading('Turnos cerrados con pendientes · últimos 7 días')
            ->modalSubmitAction(false)->modalCancelActionLabel('Cerrar')
            ->modalContent(fn () => view('filament.direccion.widgets.lista-turnos', ['turnos' => Semana::turnosConPendientes()]));
    }
}
