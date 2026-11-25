<?php

namespace App\Support\Demo;

use App\Models\Aviso;
use App\Models\Residente;

/**
 * Avisos de ejemplo con el alcance de la dirección (25/11/2026, comentario de Pablo en INI-01):
 * patrones de planta, equipo, familias y organización, no solo el cuidado de un residente.
 * Se pueden sembrar varias veces: no duplica (busca por título).
 */
class AvisosTransversales
{
    public static function sembrar(): void
    {
        $dolores = Residente::where('nombre', 'Dolores Ramos')->value('id');
        $avisos = [
            ['patron', 'Caídas de noche en Planta 2: 3 esta semana, el doble que la anterior', null, 1, 'Mira si coincide con un cambio de turno o de personal.'],
            ['equipo', 'La tarde de Planta 1 cierra con pendientes 4 de 7 días: carga de trabajo', null, 2, 'Motivo más repetido al cerrar: carga de trabajo.'],
            ['organizacion', 'Diciembre: 3 auxiliares de Planta 2 piden vacaciones la misma semana', null, 3, null],
        ];
        if ($dolores) {
            $avisos[] = ['familia', 'La familia pide una reunión por la rojez del talón', $dolores, 2, null];
        }
        foreach ($avisos as [$tipo, $titulo, $residente, $urgencia, $detalle]) {
            Aviso::firstOrCreate(['titulo' => $titulo], ['tipo' => $tipo, 'residente_id' => $residente, 'urgencia' => $urgencia, 'detalle' => $detalle]);
        }
    }
}
