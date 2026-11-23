<?php

namespace App\Filament\Direccion\Resources\ResidenteResource\Pages;

use App\Filament\Direccion\Concerns\MigasDePan;
use App\Models\Residente;
use App\Models\Residencia;
use App\Filament\Direccion\Resources\AusenciaResource;
use App\Filament\Direccion\Resources\ResidenteResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListResidentes extends ListRecords
{
    use MigasDePan;

    protected static string $resource = ResidenteResource::class;

    /** Capacidad de un vistazo y lo que pide atención, en una línea bajo el título. */
    public function getSubheading(): ?string
    {
        $activos = Residente::activos();
        $n = (clone $activos)->count();
        $plazas = Residencia::actual()->plazas_autorizadas;
        $fuera = (clone $activos)->where('estado', 'fuera')->count();
        $planes = (clone $activos)->whereDate('plan_proxima_revision', '<', today())->count();
        $sinEmergencia = (clone $activos)->whereDoesntHave('contactos', fn ($query) => $query->where('emergencia', true))->count();

        $partes = [$plazas ? "{$n} de {$plazas} plazas ocupadas · ".max(0, $plazas - $n).' libres' : "{$n} residentes"];
        $partes[] = ($n - $fuera).' en el centro'.($fuera ? " · {$fuera} fuera" : '');
        if ($planes) {
            $partes[] = $planes === 1 ? '1 plan personal vencido' : "{$planes} planes personales vencidos";
        }
        if ($sinEmergencia) {
            $partes[] = $sinEmergencia === 1 ? '1 sin contacto de emergencia' : "{$sinEmergencia} sin contacto de emergencia";
        }

        return implode(' · ', $partes);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ausencias')->label('Ausencias')->color('gray')->icon('heroicon-o-arrow-right-start-on-rectangle')->url(AusenciaResource::getUrl()),
            CreateAction::make()->label('Añadir residente'),
        ];
    }
}
