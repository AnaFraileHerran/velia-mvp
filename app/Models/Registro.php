<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registro extends Model
{
    public const CATEGORIAS = [
        'Alimentación e hidratación', 'Higiene', 'Movilidad', 'Sujeción', 'Descanso', 'Ánimo', 'Incidencias',
    ];

    protected $fillable = [
        'residente_id', 'user_id', 'turno_id', 'texto_original', 'categorias', 'estado',
        'incidencia_destacada', 'tipo_incidencia',
    ];

    protected function casts(): array
    {
        return ['categorias' => 'array', 'incidencia_destacada' => 'boolean'];
    }

    public function residente(): BelongsTo
    {
        return $this->belongsTo(Residente::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(Turno::class);
    }

    /** Frase corta para el relevo y el historial. */
    public function resumenCorto(): string
    {
        if ($this->estado === 'sin_novedad') {
            return 'Sin novedad.';
        }

        $cats = $this->categorias ?? [];

        return $cats['Incidencias'] ?? (reset($cats) ?: (string) $this->texto_original);
    }
}
