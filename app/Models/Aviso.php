<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Aviso extends Model
{
    public const MOTIVOS_DESCARTE = [
        'duplicado' => 'Está duplicado',
        'error' => 'El aviso es un error',
        'resuelto' => 'Ya estaba resuelto',
        'otro' => 'Otro motivo',
    ];

    public const TIPOS = [
        'turno_sin_abrir' => 'Turno sin abrir',
        'revision_vencida' => 'Revisión vencida',
        'patron' => 'Se repite',
        'incidencia' => 'Incidencia',
        'turno_con_motivo' => 'Turno incompleto',
    ];

    protected $fillable = ['tipo', 'titulo', 'residente_id', 'detalle', 'urgencia', 'estado', 'responsable_id', 'vence_el', 'hecho_at', 'revisado_por', 'revisado_at', 'motivo_descarte'];

    protected function casts(): array
    {
        return ['revisado_at' => 'datetime', 'vence_el' => 'date', 'hecho_at' => 'datetime'];
    }

    public function residente(): BelongsTo
    {
        return $this->belongsTo(Residente::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /** Lo que sigue pidiendo una decisión de la dirección: pendientes y asignados que vencieron sin hacerse. Vencidos primero. */
    public function scopeAbiertos(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->where('estado', 'pendiente')
            ->orWhere(fn (Builder $v) => $v->where('estado', 'asignado')->whereNull('hecho_at')->whereDate('vence_el', '<', today())))
            ->orderByRaw("case when estado = 'asignado' then 0 else 1 end")
            ->orderBy('urgencia')->latest();
    }

    /** Asignado a esta persona y sin hacer: aparece en su relevo. */
    public function scopeAsignadosA(Builder $query, int $userId): Builder
    {
        return $query->where('estado', 'asignado')->where('responsable_id', $userId)->whereNull('hecho_at')->orderBy('vence_el');
    }

    public function vencido(): bool
    {
        return $this->estado === 'asignado' && ! $this->hecho_at && $this->vence_el && $this->vence_el->lt(today());
    }
}
