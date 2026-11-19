<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Aviso extends Model
{
    public const TIPOS = [
        'revision_vencida' => 'Revisión vencida',
        'patron' => 'Patrón',
        'incidencia' => 'Incidencia destacada',
        'turno_con_motivo' => 'Turno con motivo',
    ];

    protected $fillable = ['tipo', 'titulo', 'residente_id', 'detalle', 'urgencia', 'estado', 'responsable_id', 'revisado_por', 'revisado_at'];

    protected function casts(): array
    {
        return ['revisado_at' => 'datetime'];
    }

    public function residente(): BelongsTo
    {
        return $this->belongsTo(Residente::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /** Lo que sigue pidiendo una decisión de la dirección, por urgencia. */
    public function scopeAbiertos(Builder $query): Builder
    {
        return $query->whereIn('estado', ['pendiente'])->orderBy('urgencia')->latest();
    }
}
