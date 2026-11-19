<?php

namespace App\Filament\Direccion\Widgets;

use App\Models\Aviso;
use App\Models\Pregunta;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

/** Hoy necesita tu decisión: como máximo 5 avisos, ordenados por urgencia. Cada uno se resuelve aquí (D2). */
class HoyNecesitaTuDecision extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public const MAXIMO = 5;

    protected static string $view = 'filament.direccion.widgets.hoy-necesita-tu-decision';

    protected int|string|array $columnSpan = ['md' => 2];

    protected static ?int $sort = 1;

    public bool $verTodos = false;

    public function getAvisos()
    {
        $q = Aviso::abiertos()->with('residente.planta');

        return $this->verTodos ? $q->get() : $q->limit(self::MAXIMO)->get();
    }

    public function getTotal(): int
    {
        return Aviso::abiertos()->count();
    }

    public function asignarAction(): Action
    {
        return Action::make('asignar')
            ->label('Asignar')
            ->modalHeading('Asignar responsable')
            ->form([
                Select::make('responsable_id')->label('¿Quién hace el seguimiento?')
                    ->options(User::auxiliares()->where('activo', true)->pluck('name', 'id'))->required(),
            ])
            ->action(function (array $data, array $arguments) {
                Aviso::findOrFail($arguments['aviso'])->update(['estado' => 'asignado', 'responsable_id' => $data['responsable_id']]);
                Notification::make()->title('Asignado. Recibirá un aviso en su relevo.')->success()->send();
            });
    }

    public function preguntarAction(): Action
    {
        return Action::make('preguntar')
            ->label('Preguntar al turno')
            ->color('gray')
            ->form([Textarea::make('texto')->label('Pregunta')->required()->rows(2)])
            ->action(function (array $data, array $arguments) {
                $aviso = Aviso::findOrFail($arguments['aviso']);
                Pregunta::create(['residente_id' => $aviso->residente_id, 'aviso_id' => $aviso->id, 'texto' => $data['texto']]);
                $aviso->update(['estado' => 'pregunta']);
                Notification::make()->title('La pregunta aparece en el relevo del turno.')->success()->send();
            });
    }

    public function revisadoAction(): Action
    {
        return Action::make('revisado')
            ->label('Marcar revisado')
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription('Queda como evidencia para la UNE 158101, con tu nombre y la fecha.')
            ->action(function (array $arguments) {
                Aviso::findOrFail($arguments['aviso'])->update(['estado' => 'revisado', 'revisado_por' => auth()->id(), 'revisado_at' => now()]);
                Notification::make()->title('Revisado con fecha y nombre.')->success()->send();
            });
    }

    public function cerrarAction(): Action
    {
        return Action::make('cerrar')
            ->label('Cerrar')
            ->link()
            ->color('gray')
            ->action(function (array $arguments) {
                Aviso::findOrFail($arguments['aviso'])->update(['estado' => 'cerrado']);
                Notification::make()->title('Aviso cerrado.')->send();
            });
    }
}
