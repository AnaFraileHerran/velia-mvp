<?php

namespace App\Filament\Direccion\Resources\ResidenteResource\Pages;

use App\Filament\Direccion\Resources\ResidenteResource;
use App\Models\Aviso;
use Filament\Resources\Pages\CreateRecord;

/** Único sitio con edición completa. Crea el aviso de la valoración inicial del plan personal (primer mes). */
class CreateResidente extends CreateRecord
{
    protected static string $resource = ResidenteResource::class;

    protected static ?string $title = 'Nuevo residente';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['fecha_ingreso'] ??= now()->toDateString();
        $data['plan_proxima_revision'] ??= now()->parse($data['fecha_ingreso'])->addMonth()->toDateString();

        return $data;
    }

    protected function afterCreate(): void
    {
        Aviso::create([
            'tipo' => 'revision_vencida',
            'titulo' => 'Valoración inicial del plan personal (antes de un mes desde el ingreso)',
            'residente_id' => $this->record->id,
            'urgencia' => 2,
        ]);
    }
}
