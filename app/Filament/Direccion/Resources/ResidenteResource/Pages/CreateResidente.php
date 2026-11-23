<?php

namespace App\Filament\Direccion\Resources\ResidenteResource\Pages;

use App\Filament\Direccion\Concerns\MigasDePan;
use App\Filament\Direccion\Resources\ResidenteResource;
use App\Models\Aviso;
use App\Models\SugerenciaIa;
use App\Support\Ia\Sugerencias;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

/** Único sitio con edición completa. Crea el aviso de la valoración inicial del plan personal (primer mes). */
class CreateResidente extends CreateRecord
{
    use MigasDePan;

    protected function migas(): array
    {
        return [ResidenteResource::getUrl() => 'Residentes'];
    }

    protected static string $resource = ResidenteResource::class;

    protected static ?string $title = 'Nuevo residente';

    /** Lo que propuso Velia a partir del dictado, para anotar al guardar si se aceptó o se cambió. */
    public array $propuestaVelia = [];

    public function rellenarConVelia(string $texto): void
    {
        $campos = Sugerencias::fichaDesdeDictado($texto);
        if (! $campos) {
            Notification::make()->title('No he encontrado nada que poner en la ficha. Prueba con más detalle.')->warning()->send();

            return;
        }
        $this->data = array_replace($this->data ?? [], $campos);
        $this->propuestaVelia = $campos;
        $nombres = ['alergias' => 'alergias', 'riesgos' => 'riesgos', 'movilidad' => 'movilidad', 'sujecion' => 'sujeción', 'dieta' => 'dieta', 'como_tratarle' => 'cómo tratarle', 'notas' => 'notas', 'habitacion' => 'habitación'];
        $rellenos = collect(array_keys($campos))->map(fn ($k) => $nombres[$k] ?? null)->filter()->unique()->join(', ');
        Notification::make()->title('Velia ha rellenado: '.$rellenos)->body('Revisa cada campo antes de guardar.')->success()->send();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['fecha_ingreso'] ??= now()->toDateString();
        $data['plan_proxima_revision'] ??= now()->parse($data['fecha_ingreso'])->addMonth()->toDateString();

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->propuestaVelia) {
            $final = array_intersect_key($this->record->only(array_keys($this->propuestaVelia)), $this->propuestaVelia);
            $igual = collect($this->propuestaVelia)->every(fn ($v, $k) => (string) $v === (string) ($final[$k] ?? ''));
            SugerenciaIa::anotar('residente.alta', json_encode($this->propuestaVelia, JSON_UNESCAPED_UNICODE), $igual ? 'aceptada' : 'cambiada', $this->record);
        }

        Aviso::create([
            'tipo' => 'revision_vencida',
            'titulo' => 'Valoración inicial del plan personal (antes de un mes desde el ingreso)',
            'residente_id' => $this->record->id,
            'urgencia' => 2,
        ]);
    }
}
