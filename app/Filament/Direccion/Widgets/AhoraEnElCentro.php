<?php

namespace App\Filament\Direccion\Widgets;

use App\Filament\Direccion\Widgets\EstaSemana;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;

/**
 * Banda «Ahora» (opción C, 23/11/2026): responde en 1 segundo a «¿está todo cubierto?».
 * A todo el ancho, arriba. Solo se pinta en rojo lo que falla; un día normal es gris y dice «Todo cubierto».
 * Lo que falla lleva al mismo sitio que su aviso en «Hoy» (no se duplica el problema).
 */
class AhoraEnElCentro extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static string $view = 'filament.direccion.widgets.ahora-en-el-centro';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    protected function getViewData(): array
    {
        $plantas = EstaSemana::ahoraPorPlanta();

        return [
            'plantas' => $plantas,
            'sanitarios' => \App\Support\Sanitarios::ahora(),
            'fuera' => \App\Models\Ausencia::where('ha_vuelto', false)->with('residente')->orderBy('vuelve')->get(),
            'franja' => \App\Support\Franja::actual(),
        ];
    }
}
