<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resumen extends Model
{
    protected $table = 'resumenes';

    protected $fillable = ['residente_id', 'semana_inicio', 'texto', 'tiene_incidencias', 'estado', 'aprobado_por', 'enviado_at'];

    protected function casts(): array
    {
        return ['semana_inicio' => 'date', 'tiene_incidencias' => 'boolean', 'enviado_at' => 'datetime'];
    }

    public function residente(): BelongsTo
    {
        return $this->belongsTo(Residente::class);
    }

    /** Registros de la semana que originan el resumen (se enseñan al revisar). */
    public function registrosDeLaSemana()
    {
        return Registro::where('residente_id', $this->residente_id)
            ->whereBetween('created_at', [$this->semana_inicio->copy()->startOfDay(), $this->semana_inicio->copy()->addDays(6)->endOfDay()])
            ->where('estado', '!=', 'borrador');
    }

    public function numeroIncidencias(): int
    {
        return (clone $this->registrosDeLaSemana())->where('incidencia_destacada', true)->count() ?: ($this->tiene_incidencias ? 1 : 0);
    }

    public function tieneContacto(): bool
    {
        return $this->residente->contactos()->where('recibe_resumen', true)->exists();
    }
}
