<?php

namespace App\Http\Middleware;

use App\Models\Turno;
use Closure;
use Illuminate\Http\Request;

/**
 * Al entrar, la auxiliar tiene siempre un turno abierto (lo abre el primer acceso del día).
 * Si aún tiene PIN provisional, la lleva a crear el suyo.
 */
class TurnoAbierto
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user?->pin_provisional) {
            return redirect()->route('filament.turno.primer-acceso', ['paso' => 2]);
        }
        if ($user && ! $user->turnoAbierto()) {
            Turno::create([
                'user_id' => $user->id,
                'planta_id' => $user->planta_id,
                'inicio' => now(),
            ]);
        }

        return $next($request);
    }
}
