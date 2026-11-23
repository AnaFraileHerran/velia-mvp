<?php

namespace App\Filament\Direccion\Pages;

use App\Filament\Direccion\Concerns\MigasDePan;
use App\Filament\Direccion\Resources\ResumenResource;
use App\Models\Ausencia;
use App\Models\Registro;
use App\Models\Residencia;
use App\Models\Residente;
use Filament\Actions\Action;
use Filament\Pages\Page;

/** 5 · Familias › Residencia y ocupación (wireframe «V2 · Familias · residencia y ocupación»). */
class Ocupacion extends Page
{
    use MigasDePan;

    protected function migas(): array
    {
        return [ResumenResource::getUrl() => 'Familias'];
    }

    protected static ?string $title = 'Familias';

    protected static ?string $slug = 'familias/ocupacion';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.direccion.pages.ocupacion';

    public function getSubheading(): ?string
    {
        return 'Residencia y ocupación';
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('resumenes')->label('Resúmenes de residentes')->color('gray')->url(ResumenResource::getUrl())];
    }

    public function datos(): array
    {
        $activos = Residente::where('estado', '!=', 'baja');
        $incidencias = Registro::with('residente')->where('incidencia_destacada', true)->where('created_at', '>=', now()->subDays(7))->get()
            ->groupBy('tipo_incidencia')
            ->map(fn ($g, $tipo) => ['tipo' => $tipo ?: 'Otras', 'n' => $g->count(), 'quien' => $g->pluck('residente.nombre')->map(fn ($n) => strtok($n, ' '))->unique()->join(', ')])
            ->sortByDesc('n')->values();

        $notas = collect();
        foreach (Ausencia::with('residente')->where('ha_vuelto', false)->whereDate('vuelve', today())->get() as $a) {
            $notas->push($a->residente->nombre.' vuelve hoy');
        }
        foreach (Residente::where('fecha_ingreso', '>=', now()->startOfMonth())->get() as $r) {
            $notas->push('Ingreso de '.$r->nombre.' el '.$r->fecha_ingreso->locale('es')->isoFormat('D MMM'));
        }
        foreach (Residente::where('estado', 'baja')->where('baja_fecha', '>=', now()->subMonth())->get() as $r) {
            $notas->push('Baja de '.$r->nombre.' ('.mb_strtolower($r->baja_motivo).') el '.$r->baja_fecha->locale('es')->isoFormat('D MMM'));
        }

        return [
            'ocupadas' => (clone $activos)->count(),
            'plazas' => Residencia::actual()->plazas_autorizadas,
            'dobles' => (clone $activos)->where('tipo_habitacion', 'doble')->count(),
            'individuales' => (clone $activos)->where('tipo_habitacion', 'individual')->count(),
            'ingresos' => Residente::where('fecha_ingreso', '>=', now()->startOfMonth())->count(),
            'bajas' => Residente::where('estado', 'baja')->where('baja_fecha', '>=', now()->startOfMonth())->count(),
            'incidencias' => $incidencias,
            'notas' => $notas,
        ];
    }
}
