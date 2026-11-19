<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pregunta extends Model
{
    protected $fillable = ['residente_id', 'aviso_id', 'texto', 'respuesta', 'respondida_por'];

    public function residente(): BelongsTo
    {
        return $this->belongsTo(Residente::class);
    }
}
