<?php

namespace App\Support;

use Filament\Support\Colors\Color;

/**
 * Tokens del Design System de Velia (https://claude.ai/artifact/7aHoYxDjtYRvjtwSsFfJTH) para Filament.
 * Acción en verde pino #2F5D50 (primary-600, 7,5:1 con blanco). El ámbar por defecto no pasa AA.
 */
class TemaVelia
{
    public const PINO = [
        50 => '240, 246, 243', 100 => '220, 234, 227', 200 => '185, 212, 199', 300 => '141, 183, 165',
        400 => '94, 149, 128', 500 => '67, 122, 102', 600 => '47, 93, 80', 700 => '40, 78, 68',
        800 => '33, 63, 55', 900 => '27, 51, 45', 950 => '15, 31, 27',
    ];

    public static function colores(): array
    {
        return [
            'primary' => self::PINO,
            'gray' => Color::Stone,      // neutros cálidos: hogar, no hospital
            'danger' => Color::Red,      // solo incidencias, bajas y errores
            'warning' => Color::Amber,   // solo badges y bordes (no botones: no pasa AA)
            'success' => Color::Green,   // solo badges e iconos, siempre con texto
            'info' => Color::hex('#3B4E8C'),
        ];
    }
}
