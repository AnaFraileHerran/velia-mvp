<?php

namespace App\Support;

use Filament\Support\Colors\Color;

/**
 * Tokens del Design System de Velia v2 (https://claude.ai/artifact/7aHoYxDjtYRvjtwSsFfJTH) para Filament.
 * Gris claro y sencillo, con un solo color de acción: cobalto suave #3B5BA9 (6,5:1 con texto blanco).
 */
class TemaVelia
{
    public const COBALTO = [
        50 => '243, 245, 251', 100 => '233, 238, 248', 200 => '207, 216, 239', 300 => '169, 185, 225',
        400 => '120, 145, 205', 500 => '86, 114, 187', 600 => '59, 91, 169', 700 => '48, 74, 139',
        800 => '40, 61, 113', 900 => '33, 50, 92', 950 => '21, 31, 58',
    ];

    /** Neutros cálidos. 50 = fondo (#F5F5F2); 500 = texto secundario (6,1:1); 900 = texto (#141413). */
    public const NEUTRO = [
        50 => '245, 245, 242', 100 => '237, 237, 234', 200 => '228, 228, 223', 300 => '207, 207, 201',
        400 => '138, 138, 131', 500 => '98, 98, 92', 600 => '74, 74, 69', 700 => '58, 58, 54',
        800 => '38, 38, 36', 900 => '20, 20, 19', 950 => '11, 11, 10',
    ];

    public static function colores(): array
    {
        return [
            'primary' => self::COBALTO,
            'gray' => self::NEUTRO,
            'danger' => Color::Red,      // solo incidencias, bajas y errores
            'warning' => Color::Amber,   // solo badges y bordes (con texto blanco no pasa AA)
            'success' => Color::Green,   // solo badges e iconos, siempre con texto
            'info' => self::NEUTRO,      // informativo en neutro: el color se reserva para la acción
        ];
    }
}
