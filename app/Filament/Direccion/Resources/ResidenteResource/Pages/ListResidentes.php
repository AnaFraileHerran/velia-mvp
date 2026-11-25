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
use App\Support\VersionH;
use Filament\Schemas\Components\Tabs\Tab;

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

        // Versión H: las pestañas ya cuentan quién está fuera y a quién le falta algo; aquí solo la ocupación.
        if (VersionH::activa()) {
            return $partes[0];
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

    /** Versión H: pestañas de estado con contador, como las listas de Holded. En la versión actual, sin pestañas. */
    public function getTabs(): array
    {
        if (! VersionH::activa()) {
            return [];
        }

        $activos = fn ($query) => $query->where('estado', '!=', 'baja');
        $falta = fn ($query) => $query->where('estado', '!=', 'baja')->where(fn ($w) => $w
            ->whereDate('plan_proxima_revision', '<', today())
            ->orWhereNull('plan_responsable_id')
            ->orWhere('sujecion_anotada', false)
            ->orWhereDoesntHave('contactos', fn ($c) => $c->where('emergencia', true)));

        return [
            'todos' => Tab::make('Todos')->modifyQueryUsing($activos)->badge($activos(Residente::query())->count()),
            'en_centro' => Tab::make('En el centro')->modifyQueryUsing(fn ($query) => $query->where('estado', 'en_centro'))->badge(Residente::where('estado', 'en_centro')->count()),
            'fuera' => Tab::make('Fuera')->modifyQueryUsing(fn ($query) => $query->where('estado', 'fuera'))->badge(Residente::where('estado', 'fuera')->count() ?: null)->badgeColor('warning'),
            'falta' => Tab::make('Les falta algo')->icon('heroicon-o-exclamation-triangle')->modifyQueryUsing($falta)->badge($falta(Residente::query())->count() ?: null)->badgeColor('danger'),
        ];
    }
}
