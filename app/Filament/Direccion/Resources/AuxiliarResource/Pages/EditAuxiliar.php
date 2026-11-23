<?php

namespace App\Filament\Direccion\Resources\AuxiliarResource\Pages;

use App\Filament\Direccion\Resources\AuxiliarResource;
use Filament\Resources\Pages\EditRecord;

/** Editar la ficha: los mismos campos que al añadir (auxiliar, o enfermería y medicina). El PIN no se edita aquí. */
class EditAuxiliar extends EditRecord
{
    use \App\Filament\Direccion\Concerns\MigasDePan;

    protected static string $resource = AuxiliarResource::class;

    protected function migas(): array
    {
        return [AuxiliarResource::getUrl() => 'Equipo', AuxiliarResource::getUrl('view', ['record' => $this->getRecord()]) => $this->getRecord()->name];
    }

    protected function migaActual(): string
    {
        return 'Editar ficha';
    }

    public function getTitle(): string
    {
        return 'Editar ficha de '.strtok($this->getRecord()->name, ' ');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['rol']); // el rol no cambia al editar

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return AuxiliarResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
