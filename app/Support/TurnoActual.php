<?php

namespace App\Support;

use Filament\Facades\Filament;
use App\Models\Ausencia;
use App\Models\Pregunta;
use App\Models\Registro;
use App\Models\Residente;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Lo que la auxiliar necesita saber de su planta en este turno. Un solo sitio para que
 * Relevo, Residentes, Mi turno y la barra inferior cuenten lo mismo.
 */
class TurnoActual
{
    public function __construct(public User $user, public ?Turno $turno) {}

    public static function make(): self
    {
        $u = Filament::auth()->user();

        return new self($u, $u->turnoAbierto());
    }

    public function residentes(): Collection
    {
        return Residente::activos()
            ->when($this->user->planta_id, fn ($q) => $q->where('planta_id', $this->user->planta_id))
            ->with(['registros' => fn ($q) => $q->latest()->limit(5)])
            ->orderBy('habitacion')->get();
    }

    /** Estado de cada residente en este turno: pendiente | borrador | registrado | fuera. */
    public function estado(Residente $r): string
    {
        if ($r->estado === 'fuera') {
            return 'fuera';
        }
        $delTurno = $r->registros->where('turno_id', $this->turno?->id);
        if ($delTurno->whereIn('estado', ['confirmado', 'sin_novedad'])->isNotEmpty()) {
            return 'registrado';
        }

        return $delTurno->where('estado', 'borrador')->isNotEmpty() ? 'borrador' : 'pendiente';
    }

    /** @return array{por_registrar: Collection, registrados: Collection, fuera: Collection} */
    public function agrupados(): array
    {
        $g = $this->residentes()->groupBy(fn ($r) => $this->estado($r));

        return [
            // Borradores primero: ya empezados, se terminan antes.
            'por_registrar' => collect($g->get('borrador', []))->concat($g->get('pendiente', [])),
            'registrados' => collect($g->get('registrado', [])),
            'fuera' => collect($g->get('fuera', [])),
        ];
    }

    public function porRegistrar(): int
    {
        return $this->agrupados()['por_registrar']->count();
    }

    public function idsPlanta(): Collection
    {
        return $this->residentes()->pluck('id');
    }

    /** Registros de las últimas 12 h de otras personas, con algo que contar. */
    public function novedades(): Collection
    {
        return Registro::with(['residente', 'user'])
            ->whereIn('residente_id', $this->idsPlanta())
            ->where('estado', 'confirmado')
            ->where('created_at', '>=', now()->subHours(12))
            ->where('user_id', '!=', $this->user->id)
            ->orderByDesc('incidencia_destacada')->latest()->get()
            ->unique('residente_id')->values();
    }

    public function preguntas(): Collection
    {
        return Pregunta::with('residente')->whereIn('residente_id', $this->idsPlanta())->whereNull('respuesta')->whereNull('cerrada_at')->latest()->get();
    }

    public function vuelvenHoy(): Collection
    {
        return Ausencia::with('residente')->whereIn('residente_id', $this->idsPlanta())->where('ha_vuelto', false)->whereDate('vuelve', today())->get();
    }

    public function notasCompaneras(): Collection
    {
        return Turno::with('user')
            ->when($this->user->planta_id, fn ($q) => $q->where(fn ($q) => $q->where('planta_id', $this->user->planta_id)->orWhereNull('planta_id')))
            ->whereNotNull('fin')->where('fin', '>=', now()->subHours(16))
            ->whereNotNull('nota_relevo')->latest('fin')->get();
    }

    public function sinNovedades(): Collection
    {
        $con = $this->novedades()->pluck('residente_id');

        return $this->residentes()->where('estado', 'en_centro')->whereNotIn('id', $con)->values();
    }

    /**
     * Compañeras del mismo turno en tu planta (o en todas, si no tienes planta fija).
     * Primero quien ya ha entrado; si aún no ha abierto turno, «Le toca hoy».
     */
    public function companeras(): Collection
    {
        $u = $this->user;

        return User::auxiliares()
            ->where('activo', true)
            ->whereKeyNot($u->id)
            ->where('turno_habitual', $u->turno_habitual)
            ->when($u->planta_id, fn ($query) => $query->where(fn ($query) => $query->where('planta_id', $u->planta_id)->orWhereNull('planta_id')))
            ->with('planta')
            ->get()
            ->map(fn (User $c) => ['user' => $c, 'enTurno' => (bool) $c->turnoAbierto()])
            ->sortBy(fn ($c) => [$c['enTurno'] ? 0 : 1, $c['user']->name])
            ->values();
    }
}
