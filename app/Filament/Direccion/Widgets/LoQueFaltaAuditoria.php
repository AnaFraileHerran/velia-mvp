<?php

namespace App\Filament\Direccion\Widgets;

use App\Filament\Direccion\Pages\Calidad;
use App\Models\Residencia;
use App\Support\PendientesAuditoria;
use Filament\Widgets\Widget;

/** Pestaña «Auditoría» del Inicio en la versión H: lo que falta y Velia sabe medir, con el enlace para resolverlo. */
class LoQueFaltaAuditoria extends Widget
{
    protected string $view = 'filament.direccion.widgets.lo-que-falta-auditoria';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $r = Residencia::actual();

        return [
            'items' => PendientesAuditoria::items(),
            'fecha' => $r?->proxima_auditoria?->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
            'calidad' => Calidad::getUrl(),
        ];
    }
}
