<?php

namespace App\Filament\Direccion\Widgets;

use App\Models\Ausencia;
use App\Support\Franja;
use App\Support\Sanitarios;
use Filament\Actions\Action;
use App\Filament\Direccion\Resources\ResidenteResource;
use App\Models\Planta;
use App\Models\Registro;
use App\Models\Residente;
use App\Models\Turno;

/**
 * Flujo nuevo (versión H, 25/11/2026, comentarios de Pablo en INI-01): «Ahora» compacto y escalable.
 * - Sin banda roja arriba: lo que falla es una pista dentro de su planta (ámbar, no rojo) y al pulsarla se ve quién falta o «no procede».
 * - Una tarjeta pequeña por planta, profesional o ausencia, en rejilla que se reparte sola: caben más plantas y más roles sin rediseñar.
 * Hereda de AlertaTurno las acciones «Ver quién falta» y «No procede» (mismo comportamiento y mismo rastro).
 */
class AhoraH extends AlertaTurno
{
    protected string $view = 'filament.direccion.widgets.ahora-h';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $avisos = $this->getAvisos()->keyBy(fn ($a) => (int) $a->detalle);

        return [
            'plantas' => collect(EstaSemana::ahoraPorPlanta())->map(fn ($p) => [...$p, 'aviso' => $avisos->get($p['id'])])->all(),
            'sanitarios' => Sanitarios::ahora(),
            'fuera' => Ausencia::where('ha_vuelto', false)->whereDate('sale', '<=', today())->with('residente')->orderBy('vuelve')->get(),
            'franja' => Franja::actual(),
        ];
    }

    /** La pista de la planta sin turno: ámbar y en texto, no un botón rojo. Al pulsarla, el mismo «¿Quién debería estar?». */
    public function quienFaltaAction(): Action
    {
        return parent::quienFaltaAction()
            ->label('Sin turno desde las '.Franja::actual()['inicio']->format('H:i'))
            ->link()
            ->color('warning')
            ->icon('heroicon-m-exclamation-circle');
    }

    public function noProcedeAction(): Action
    {
        return parent::noProcedeAction()->color('gray');
    }

    /**
     * Pulsar una planta (25/11/2026, petición de Ana): qué pasa ahora en ella sin salir del Inicio.
     * Quién está, a quién falta registrar, incidencias de hoy y el enlace a sus residentes.
     */
    public function verPlantaAction(): Action
    {
        return Action::make('verPlanta')
            ->modalHeading(fn (array $arguments) => (Planta::find($arguments['planta'] ?? null)?->nombre ?? 'Planta').' · ahora')
            ->modalDescription(fn () => 'Turno de '.Franja::actual()['nombre'].' · desde las '.Franja::actual()['inicio']->format('H:i'))
            ->slideOver()
            ->modalWidth('lg')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(fn (array $arguments) => view('filament.direccion.widgets.planta-ahora', $this->datosPlanta((int) ($arguments['planta'] ?? 0))));
    }

    private function datosPlanta(int $id): array
    {
        $p = Planta::find($id);
        $f = Franja::actual();
        $residentes = Residente::activos()->where('planta_id', $id)->orderBy('habitacion')->get();
        $registrados = Registro::whereIn('residente_id', $residentes->pluck('id'))->whereIn('estado', ['confirmado', 'sin_novedad'])
            ->where('created_at', '>=', $f['inicio'])->pluck('residente_id')->unique();
        $estado = fn ($r) => $r->estado === 'fuera' ? 'fuera' : ($registrados->contains($r->id) ? 'registrado' : 'pendiente');

        return [
            'planta' => $p,
            'personas' => Turno::whereNull('fin')->with('user')->where(fn ($q) => $q->where('planta_id', $id)->orWhereNull('planta_id'))->get()->map->user->filter()->unique('id'),
            'residentes' => $residentes->map(fn ($r) => ['r' => $r, 'estado' => $estado($r)])->sortBy(fn ($x) => ['pendiente' => 0, 'registrado' => 1, 'fuera' => 2][$x['estado']])->values(),
            'incidencias' => Registro::with('residente')->whereIn('residente_id', $residentes->pluck('id'))->where('incidencia_destacada', true)->whereDate('created_at', today())->latest()->get(),
            'sinTurno' => $this->getAvisos()->contains(fn ($a) => (int) $a->detalle === $id),
            'urlResidentes' => ResidenteResource::getUrl('index', ['tableFilters' => ['planta_id' => ['value' => $id]]]),
        ];
    }
}
