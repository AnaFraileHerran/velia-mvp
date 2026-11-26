<?php

namespace App\Filament\Direccion\Resources\AuxiliarResource\Pages;

use App\Filament\Direccion\Concerns\MigasDePan;
use Illuminate\Support\HtmlString;
use Filament\Support\Enums\IconPosition;
use App\Filament\Direccion\Resources\AuxiliarResource;
use App\Models\User;
use App\Models\Planta;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Str;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

/** Ficha de una persona del equipo (auxiliar, enfermería o medicina). Se abre al pulsar su nombre en Equipo. */
class ViewAuxiliar extends ViewRecord
{
    use MigasDePan;

    protected static string $resource = AuxiliarResource::class;

    /** Nueva versión: el teléfono sale tapado hasta pulsar «Mostrar» (queda anotado quién lo ha visto). */
    public bool $telefonoVisible = false;

    protected function migas(): array
    {
        return [AuxiliarResource::getUrl() => 'Equipo'];
    }

    protected function migaActual(): string
    {
        return $this->getRecord()->name;
    }

    public function getTitle(): string
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): ?string
    {
        return AuxiliarResource::lineaEstado($this->getRecord());
    }

    public function accesoGeneradoAction(): Action
    {
        return Action::make('accesoGenerado')
            ->modalHeading(fn (array $arguments) => $arguments['titulo'] ?? '')
            ->modalContent(fn (array $arguments) => new HtmlString(
                '<p style="font-family:\'Atkinson Hyperlegible Next\',\'Albert Sans\',sans-serif;font-size:40px;font-weight:700;letter-spacing:.15em;text-align:center;margin:8px 0">'
                .e($arguments['valor'] ?? '').'</p><p style="text-align:center;font-size:14px;color:rgb(94 101 114)">'.e($arguments['instruccion'] ?? '').' Solo se muestra esta vez.</p>'))
            ->modalSubmitActionLabel('Ya se lo he dicho')
            ->modalCancelAction(false)
            ->modalWidth('md')
            ->action(fn () => null);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('noPuedeEntrar')
                ->label('No puede entrar')
                ->icon('heroicon-o-lock-open')->color('gray')
                // Nueva versión: pasa al bloque «Acceso a Velia» de la ficha.
                ->visible(fn () => ! \App\Support\VersionH::activa() && $this->record->activo && $this->record->rol === 'auxiliar')
                ->modalHeading(fn () => strtok($this->record->name, ' ').' no puede entrar')
                ->modalDescription('Elige la situación. No cambia nada hasta que confirmes.')
                ->modalSubmitActionLabel('Confirmar y generar')
                ->schema(AuxiliarResource::formNoPuedeEntrar())
                ->action(function (array $data) {
                    [$titulo, $valor, $instruccion] = AuxiliarResource::resolverAcceso($this->record, $data['que']);
                    // Se muestra dentro de una ventana (no en un aviso que se queda en pantalla) y se cierra con «Ya se lo he dicho».
                    $this->replaceMountedAction('accesoGenerado', ['titulo' => $titulo, 'valor' => $valor, 'instruccion' => $instruccion]);
                }),
            EditAction::make()->label('Editar ficha'),
            ActionGroup::make([
                Action::make('responsable')
                    ->label(fn () => $this->record->responsable_turno ? 'Quitar como responsable de turno' : 'Hacer responsable de turno')
                    ->icon('heroicon-o-star')
                    ->visible(fn () => $this->record->activo && $this->record->rol === 'auxiliar')
                    ->action(fn () => $this->record->update(['responsable_turno' => ! $this->record->responsable_turno])),
                Action::make('cambiarPlantaTurno')
                    ->label('Cambiar planta o turno')
                    ->icon('heroicon-o-arrows-right-left')
                    ->visible(fn () => $this->record->activo && $this->record->rol === 'auxiliar')
                    ->fillForm(fn () => ['planta_id' => $this->record->planta_id, 'turno_habitual' => $this->record->turno_habitual])
                    ->schema([
                        Select::make('planta_id')->label('Planta')->options(fn () => Planta::pluck('nombre', 'id'))->placeholder('Todas las plantas'),
                        Select::make('turno_habitual')->label('Turno habitual')->options(['manana' => 'Mañana', 'tarde' => 'Tarde', 'noche' => 'Noche'])->required(),
                    ])
                    ->action(fn (array $data) => $this->record->update($data)),
                Action::make('darDeBaja')
                    ->label('Dar de baja…')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->visible(fn () => $this->record->activo)
                    ->requiresConfirmation()
                    ->modalDescription(fn () => $this->record->rol === 'auxiliar' ? 'Pierde el acceso a la tablet y al móvil. Sus registros se conservan.' : 'Deja de aparecer en «Mi turno» de las auxiliares.')
                    ->schema([
                        Select::make('motivo')->label('Motivo')->required()
                            ->options(['fin_contrato' => 'Fin de contrato', 'cambio' => 'Cambio de centro', 'otros' => 'Otros']),
                        Textarea::make('nota')->label('Nota (opcional)')->rows(2),
                    ])
                    ->action(fn (array $data) => $this->record->update(['activo' => false, 'baja_motivo' => trim($data['motivo'].' '.($data['nota'] ?? ''))])),
                Action::make('reactivar')->label('Reactivar')->icon('heroicon-o-arrow-path')
                    ->visible(fn () => ! $this->record->activo)
                    ->action(fn () => $this->record->update(['activo' => true, 'baja_motivo' => null])),
            ])->label('Más acciones')->icon('heroicon-m-chevron-down')->iconPosition(IconPosition::After)->button()->color('gray'),
        ];
    }
}
