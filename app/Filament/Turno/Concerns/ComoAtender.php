<?php

namespace App\Filament\Turno\Concerns;

use App\Filament\Turno\Pages\Registrar;
use App\Models\Residente;
use Filament\Actions\Action;
use Filament\Support\Enums\MaxWidth;

/** Hoja «Cómo atender» (117:359): ficha de cuidados en solo lectura, con «Registrar» al pie. */
trait ComoAtender
{
    public function comoAtenderAction(): Action
    {
        return Action::make('comoAtender')
            ->slideOver()
            ->modalWidth(MaxWidth::Medium)
            ->modalHeading(fn (array $arguments) => 'Cómo atender a '.strtok(Residente::find($arguments['residente'])?->nombre ?? '', ' '))
            ->modalDescription(fn (array $arguments) => 'Hab. '.Residente::find($arguments['residente'])?->habitacion.' · solo lectura · la mantiene la dirección')
            ->modalContent(fn (array $arguments) => view('filament.turno.partials.como-atender', ['r' => Residente::with('contactos')->find($arguments['residente'])]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->extraModalFooterActions(fn (array $arguments) => [
                Action::make('registrar')->label('Registrar')->icon('heroicon-o-microphone')
                    ->url(Registrar::getUrl(['residente' => $arguments['residente']])),
            ]);
    }
}
