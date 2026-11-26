<?php

namespace App\Support;

use Filament\Support\Colors\Color;

/**
 * Tokens del Design System de Velia v2 (https://claude.ai/artifact/7aHoYxDjtYRvjtwSsFfJTH) para Filament.
 * Gris claro y sencillo, con un solo color de acción: azul #0F3D95 (9,9:1 con texto blanco; hover #0B2F75). Decisión del 23/11/2026.
 */
class TemaVelia
{
    public const COBALTO = [
        50 => '240, 243, 250', 100 => '225, 231, 244', 200 => '196, 207, 232', 300 => '150, 169, 212',
        400 => '92, 121, 184', 500 => '45, 86, 166', 600 => '15, 61, 149', 700 => '11, 47, 117',
        800 => '10, 38, 94', 900 => '9, 31, 75', 950 => '6, 19, 46',
    ];

    /** Neutros fríos con un toque del azul de marca (23/11/2026; antes cálidos #F4F6F9). 50 = fondo (#F7F8FB); 500 = texto secundario (5,9:1); 900 = texto (#14161B). */
    public const NEUTRO = [
        50 => '247, 248, 251', 100 => '236, 239, 244', 200 => '226, 230, 237', 300 => '203, 209, 219',
        400 => '134, 141, 153', 500 => '94, 101, 114', 600 => '71, 77, 89', 700 => '55, 60, 70',
        800 => '36, 39, 46', 900 => '20, 22, 27', 950 => '11, 12, 15',
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
