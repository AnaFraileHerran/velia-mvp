<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contacto extends Model
{
    protected $fillable = ['residente_id', 'nombre', 'relacion', 'telefono', 'email', 'emergencia', 'recibe_resumen', 'canal'];

    protected function casts(): array
    {
        return ['emergencia' => 'boolean', 'recibe_resumen' => 'boolean'];
    }

    public function residente(): BelongsTo
    {
        return $this->belongsTo(Residente::class);
    }
}
