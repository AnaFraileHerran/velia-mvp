<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pregunta extends Model
{
    protected $fillable = ['residente_id', 'aviso_id', 'texto', 'respuesta', 'respondida_por', 'cerrada_at'];

    protected function casts(): array
    {
        return ['cerrada_at' => 'datetime'];
    }

    public function autora(): BelongsTo
    {
        return $this->belongsTo(User::class, 'respondida_por');
    }

    /** Preguntas que la dirección aún no ha dado por resueltas (esperando respuesta o con respuesta por leer). */
    public function scopeAbiertas($query)
    {
        return $query->whereNull('cerrada_at');
    }

    public function residente(): BelongsTo
    {
        return $this->belongsTo(Residente::class);
    }
}
