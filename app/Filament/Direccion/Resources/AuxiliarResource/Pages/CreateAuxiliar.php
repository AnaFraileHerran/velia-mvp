<?php

namespace App\Filament\Direccion\Resources\AuxiliarResource\Pages;

use App\Filament\Direccion\Concerns\MigasDePan;
use Filament\Facades\Filament;
use App\Filament\Direccion\Resources\AuxiliarResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

/** Al añadir una auxiliar, el panel genera su PIN provisional y un código para vincular su móvil. */
class CreateAuxiliar extends CreateRecord
{
    use MigasDePan;

    protected function migas(): array
    {
        return [AuxiliarResource::getUrl() => 'Equipo'];
    }

    protected static string $resource = AuxiliarResource::class;

    public function getTitle(): string
    {
        $rol = $this->data['rol'] ?? request()->query('rol');
        if ($rol === 'otro') {
            $area = \App\Models\AreaEquipo::find($this->data['area_equipo_id'] ?? request()->query('area'));

            return 'Añadir a '.($area?->nombre ?? 'otro rol');
        }

        return $rol === 'sanitario' ? 'Añadir enfermería o medicina' : 'Añadir auxiliar';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (in_array($data['rol'] ?? null, ['sanitario', 'otro'], true)) {
            return $data; // no entra en la app: sin PIN ni código
        }
        $data['area_equipo_id'] = null;
        $data['rol'] = 'auxiliar';
        $data['codigo_invitacion'] = Str::upper(Str::random(3)).random_int(100, 999);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record->rol === 'otro') {
            Notification::make()->title("{$this->record->name} ya está en {$this->record->etiquetaPuesto()}.")->success()->send();

            return;
        }
        if ($this->record->rol === 'sanitario') {
            Notification::make()->title("{$this->record->name} ya aparece en «Mi turno» de las auxiliares.")->success()->send();

            return;
        }
        $pin = $this->record->generarPinProvisional(Filament::auth()->user());
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
        return static::getResource()::getUrl('index', match ($this->record->rol) {
            'sanitario' => ['tab' => 'sanitarios'],
            'otro' => ['tab' => 'area-'.$this->record->area_equipo_id],
            default => [],
        });
    }
}
