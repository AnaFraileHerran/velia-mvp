<?php

namespace App\Support;

use App\Models\Aviso;
use App\Models\Planta;
use App\Models\Turno;
use App\Models\User;

/**
 * Turno sin abrir (decisión 23/11/2026): si a los 30 minutos de empezar la franja nadie ha abierto turno en una planta,
 * se crea un aviso urgente en «Hoy necesita tu decisión». Si después alguien lo abre, el aviso se retira solo.
 * Se comprueba al abrir el Inicio (no hay tareas programadas en el MVP).
 */
class TurnosSinAbrir
{
    public const MARGEN_MINUTOS = 30;

    public static function plantaCubierta(Planta $p): bool
    {
        return Turno::whereNull('fin')->where(fn ($q) => $q->where('planta_id', $p->id)->orWhereNull('planta_id'))->exists();
    }

    /** Auxiliares que deberían estar en esa planta en la franja actual (las de «todas las plantas» incluidas). */
    public static function quienDeberia(Planta $p)
    {
        $f = Franja::actual();

        return User::auxiliares()->where('activo', true)->where('turno_habitual', $f['clave'])
            ->where(fn ($q) => $q->where('planta_id', $p->id)->orWhereNull('planta_id'))->orderBy('name')->get();
    }

    public static function comprobar(): void
    {
        $f = Franja::actual();
        foreach (Planta::all() as $p) {
            $titulo = $p->nombre.' · turno de '.$f['nombre'].' sin abrir desde las '.$f['inicio']->format('H:i');
            $existente = Aviso::where('tipo', 'turno_sin_abrir')->where('titulo', $titulo)->first();
            if (self::plantaCubierta($p)) {
                if ($existente && $existente->estado === 'pendiente') {
                    $existente->update(['estado' => 'resuelto_solo']); // alguien abrió turno: ya no hay que decidir nada
                }

                continue;
            }
            if (! $existente && $f['inicio']->diffInMinutes(now()) >= self::MARGEN_MINUTOS) {
                Aviso::create(['tipo' => 'turno_sin_abrir', 'titulo' => $titulo, 'urgencia' => 0, 'estado' => 'pendiente', 'detalle' => (string) $p->id]);
            }
        }
    }
}
