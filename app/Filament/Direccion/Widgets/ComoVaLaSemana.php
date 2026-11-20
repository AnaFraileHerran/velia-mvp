<?php

namespace App\Filament\Direccion\Widgets;

use Filament\Widgets\Widget;

/** Inicio · Cómo va la semana (antes en Calidad): la tendencia, debajo de lo que hay que decidir hoy. */
class ComoVaLaSemana extends Widget
{
    protected static string $view = 'filament.direccion.widgets.como-va-la-semana';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;
}
