<?php

namespace App\Filament\Direccion\Resources\ResidenteResource\Pages;

use App\Filament\Direccion\Resources\ResidenteResource;
use App\Models\Residente;
use App\Support\Correcciones;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

/** Ficha del residente. «Dar de baja» está solo en «… Más», con motivo y confirmación (regla 6). */
class ViewResidente extends ViewRecord
{
    use \App\Filament\Direccion\Concerns\MigasDePan;

    protected function migas(): array
    {
        return [\App\Filament\Direccion\Resources\ResidenteResource::getUrl() => 'Residentes'];
    }

    protected function migaActual(): string
    {
        return $this->getRecord()->nombre;
    }

    protected static string $resource = ResidenteResource::class;

    /**
     * Pestañas arriba (decisión UX 23/11/2026): «Ficha · Historial de registros · Correcciones · Autorizados de la familia».
     * Abajo quedaban bajo una ficha larga y no se veían; la dirección suele llegar aquí desde un aviso para mirar el historial.
     */
    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return 'Ficha';
    }

    public function getContentTabIcon(): ?string
    {
        return 'heroicon-o-identification';
    }

    public function getSubheading(): ?string
    {
        /** @var Residente $r */
        $r = $this->record;

        return ($r->planta?->nombre ?? '—').' · Hab. '.$r->habitacion.' · '.$r->tipo_habitacion.' · '.$r->etiquetaEstado();
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('fuera')
                    ->label(fn () => $this->record->estado === 'fuera' ? 'Marcar que ha vuelto' : 'Marcar salida (fuera)')
                    ->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->visible(fn () => $this->record->estado !== 'baja')
                    ->action(function () {
                        $nuevo = $this->record->estado === 'fuera' ? 'en_centro' : 'fuera';
                        $this->record->update(['estado' => $nuevo]);
                        Notification::make()->title($nuevo === 'fuera' ? 'Marcado fuera. Las auxiliares lo ven en su relevo.' : 'Ha vuelto.')->success()->send();
                    }),
                Action::make('darDeBaja')
                    ->label('Dar de baja…')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->visible(fn () => $this->record->estado !== 'baja')
                    ->requiresConfirmation()
                    ->modalHeading(fn () => 'Dar de baja a '.$this->record->nombre)
                    ->modalDescription('Deja de aparecer en los turnos, los avisos y los resúmenes. Su historial se conserva para la auditoría y para consultarlo con el filtro «Bajas».')
                    ->form([
                        Radio::make('motivo')->label('Motivo (obligatorio)')->required()->inline()
                            ->options(['Fallecimiento' => 'Fallecimiento', 'Cambio de residencia' => 'Cambio de residencia', 'Otros' => 'Otros']),
                        DatePicker::make('fecha')->label('Fecha de la baja')->default(now())->required(),
                        Textarea::make('nota')->label('Nota (opcional)')->rows(2),
                    ])
                    ->action(function (array $data) {
                        $motivo = trim($data['motivo'].' '.($data['nota'] ?? ''));
                        Correcciones::aplicar($this->record, ['estado' => 'baja', 'baja_motivo' => $data['motivo'], 'baja_fecha' => $data['fecha']], 'Baja: '.$motivo);
                        Notification::make()->title('Baja registrada. El historial se conserva.')->success()->send();
                    }),
            ])->label('Más acciones')->icon('heroicon-m-chevron-down')->iconPosition(\Filament\Support\Enums\IconPosition::After)->button()->color('gray'),
        ];
    }
}
