<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Turno extends Model
{
    public const MOTIVOS_CIERRE = [
        'urgencia' => 'Urgencia en planta',
        'personal' => 'Falta de personal',
        'carga' => 'Carga de trabajo',
        'residente_fuera' => 'Residente fuera o no disponible',
        'tecnico' => 'Problema con la tablet o la conexión',
        'otros' => 'Otros',
    ];

    protected $fillable = ['user_id', 'planta_id', 'inicio', 'fin', 'motivo_cierre', 'motivo_texto', 'nota_relevo'];

    protected function casts(): array
    {
        return ['inicio' => 'datetime', 'fin' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function planta(): BelongsTo
    {
        return $this->belongsTo(Planta::class);
    }

    public function registros(): HasMany
    {
        return $this->hasMany(Registro::class);
    }
}
