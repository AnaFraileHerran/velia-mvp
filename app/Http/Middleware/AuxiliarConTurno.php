<?php

namespace App\Http\Middleware;

use App\Models\Turno;
use Closure;
use Illuminate\Http\Request;

/**
 * /auxiliar: hay que entrar con PIN. El turno no se abre ni se cierra a mano (decisión 2, 26/11/2026):
 * empieza al entrar y termina solo al acabar el horario (9 h). «Salir» cambia de persona sin terminarlo.
 */
class AuxiliarConTurno
{
    /** Duración máxima de un turno antes de darlo por terminado. [SUPOSICIÓN]: turnos de 7-8 h con margen. */
    public const HORAS_TURNO = 9;

    /** Termina un turno que nadie terminó: si quedaban residentes sin registrar, la dirección recibe el aviso con el motivo. */
    public static function terminarSolo(\App\Models\User $user, Turno $turno): void
    {
        $t = new \App\Support\TurnoActual($user, $turno);
        $pendientes = $t->agrupados()['por_registrar']->count();
        if ($pendientes) {
            \App\Http\Controllers\Auxiliar\AuxiliarController::avisoPendientes($user, $turno, $pendientes, 'otros', 'Terminó al acabar el horario, sin revisar los pendientes.');
        }
        $turno->update(['fin' => $turno->inicio->copy()->addHours(8), 'motivo_cierre' => $pendientes ? 'otros' : null,
            'motivo_texto' => $pendientes ? 'Terminó al acabar el horario' : null]);
    }

    public function handle(Request $request, Closure $next)
    {
        $user = auth('turno')->user();
        if (! $user || ! $user->activo || $user->rol !== 'auxiliar') {
            return redirect()->guest(route('auxiliar.entrar'));
        }
        if ($user->pin_provisional) {
            // El primer acceso (crear su PIN) sigue en el panel de Filament por ahora.
            return redirect()->route('filament.turno.primer-acceso', ['paso' => 2]);
        }
        $abierto = $user->turnoAbierto();
        if ($abierto && $abierto->inicio->lt(now()->subHours(self::HORAS_TURNO))) {
            self::terminarSolo($user, $abierto);
            $abierto = null;
        }
        if (! $abierto) {
            Turno::create(['user_id' => $user->id, 'planta_id' => $user->planta_id, 'inicio' => now()]);
        }

        return $next($request);
    }
}
