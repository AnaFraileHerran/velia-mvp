<?php

namespace App\Filament\Direccion\Resources\AuxiliarResource\Pages;

use App\Filament\Direccion\Resources\AuxiliarResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

/** Al añadir una auxiliar, el panel genera su PIN provisional y un código para vincular su móvil. */
class CreateAuxiliar extends CreateRecord
{
    protected static string $resource = AuxiliarResource::class;

    protected static ?string $title = 'Añadir auxiliar';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['rol'] = 'auxiliar';
        $data['codigo_invitacion'] = Str::upper(Str::random(3)).random_int(100, 999);

        return $data;
    }

    protected function afterCreate(): void
    {
        $pin = $this->record->generarPinProvisional(auth()->user());
        Notification::make()
            ->title("PIN provisional de {$this->record->name}: {$pin}")
            ->body("Para su móvil, código de invitación: {$this->record->codigo_invitacion}. Comunícaselo en persona; creará su PIN al entrar.")
            ->persistent()->success()->send();
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
