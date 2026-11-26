<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/** Franja de turno en curso: mañana 7–15, tarde 15–23, noche 23–7. */
class Franja
{
    /** @return array{clave: string, nombre: string, inicio: Carbon} */
    public static function actual(): array
    {
        $h = (int) now()->format('G');
        if ($h >= 7 && $h < 15) {
            return ['clave' => 'manana', 'nombre' => 'mañana', 'inicio' => today()->setTime(7, 0)];
        }
        if ($h >= 15 && $h < 23) {
            return ['clave' => 'tarde', 'nombre' => 'tarde', 'inicio' => today()->setTime(15, 0)];
        }

        return ['clave' => 'noche', 'nombre' => 'noche', 'inicio' => ($h >= 23 ? today() : today()->subDay())->setTime(23, 0)];
    }
}
