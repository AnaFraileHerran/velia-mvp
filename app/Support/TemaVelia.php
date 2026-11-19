<?php

namespace App\Support;

use Filament\Support\Colors\Color;

/**
 * Tokens del Design System de Velia (https://claude.ai/artifact/7aHoYxDjtYRvjtwSsFfJTH) para Filament.
 * Dirección visual v2: gris y minimal, con un solo color de acción, cobalto suave #3B5BA9
 * (primary-600, 6,5:1 con texto blanco).
 */
class TemaVelia
{
    public const COBALTO = [
        50 => '243, 245, 251', 100 => '233, 238, 248', 200 => '207, 216, 239', 300 => '169, 185, 225',
        400 => '120, 145, 205', 500 => '86, 114, 187', 600 => '59, 91, 169', 700 => '48, 74, 139',
        800 => '40, 61, 113', 900 => '33, 50, 92', 950 => '21, 31, 58',
    ];

    public static function colores(): array
    {
        return [
            'primary' => self::COBALTO,
            'gray' => Color::Stone,      // neutros cálidos, sin que parezca desactivado
            'danger' => Color::Red,      // solo incidencias, bajas y errores: el único color además del cobalto
            'warning' => Color::Amber,   // solo badges y bordes (con texto blanco no pasa AA)
            'success' => Color::Green,   // solo badges e iconos, siempre con texto
            'info' => Color::Stone,      // informativo en neutro: el color se reserva para la acción
        ];
    }
}
