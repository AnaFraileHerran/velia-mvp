<?php

namespace App\Http\Middleware;

use App\Models\Residencia;
use App\Support\Avatar;
use App\Support\TurnoActual;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;

/**
 * App de la auxiliar en shadcn-vue (/auxiliar, 25/11/2026). Datos comunes a todas las pantallas:
 * quién está, su planta y los contadores de la barra inferior (los mismos que en el panel de Filament).
 */
class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'auxiliar';

    public function share(Request $request): array
    {
        $u = auth('turno')->user();
        $t = $u ? TurnoActual::para($u) : null;

        return [
            ...parent::share($request),
            'residencia' => Residencia::actual()?->only(['nombre', 'lema']),
            'yo' => $u ? [
                'nombre' => $u->name,
                'corto' => strtok($u->name, ' '),
                'planta' => $u->planta?->nombre ?? 'Todas las plantas',
                'turno' => $u->etiquetaTurno(),
                'responsable' => (bool) $u->responsable_turno,
                'foto' => Avatar::url($u->foto, $u->name),
                'desde' => $u->turnoAbierto()?->inicio?->format('H:i'),
            ] : null,
            'contadores' => $t ? [
                'planta' => $t->porRegistrar() + $t->preguntas()->count(),
                'porRegistrar' => $t->porRegistrar(),
            ] : null,
            'aviso' => fn () => $request->session()->get('aviso'),
            // Guía de la primera vez: una vez por persona (no por dispositivo: la tablet es compartida)
            'guia' => $u && ! $u->guia_vista_at && ! $request->routeIs('auxiliar.entrar'),
            // Micro flotante: la lista para elegir a quién registrar solo se pide al abrirlo (primero, quien no tiene registro).
            'paraGrabar' => Inertia::optional(fn () => $t ? $t->residentes()->reject(fn ($r) => $r->estado === 'fuera')
                ->map(fn ($r) => ['id' => $r->id, 'nombre' => $r->nombre, 'habitacion' => $r->habitacion, 'foto' => Avatar::url($r->foto, $r->nombre), 'estado' => $t->estado($r)])
                ->sortBy(fn ($x) => $x['estado'] === 'registrado' ? 1 : 0)->values() : []),
        ];
    }
}
