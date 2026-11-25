<?php

namespace App\Filament\Direccion\Widgets;

use App\Models\Ausencia;
use App\Support\Franja;
use App\Support\Sanitarios;
use Filament\Actions\Action;

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
}
