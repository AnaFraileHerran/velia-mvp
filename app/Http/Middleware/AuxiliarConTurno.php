<?php

namespace App\Http\Middleware;

use App\Models\Turno;
use Closure;
use Illuminate\Http\Request;

/** /auxiliar: hay que entrar con PIN y siempre hay un turno abierto (como TurnoAbierto en el panel de Filament). */
class AuxiliarConTurno
{
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
        if (! $user->turnoAbierto()) {
            Turno::create(['user_id' => $user->id, 'planta_id' => $user->planta_id, 'inicio' => now()]);
        }

        return $next($request);
    }
}
