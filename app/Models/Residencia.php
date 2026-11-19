<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Residencia extends Model
{
    protected $fillable = [
        'nombre', 'lema', 'responsable', 'direccion', 'telefono', 'email', 'cif',
        'plazas_autorizadas', 'proxima_auditoria', 'piloto_hasta', 'fotos',
    ];

    protected function casts(): array
    {
        return ['proxima_auditoria' => 'date', 'piloto_hasta' => 'date', 'fotos' => 'array'];
    }

    /** Solo hay una residencia por instalación en el MVP. */
    public static function actual(): self
    {
        return static::query()->firstOrCreate([], ['nombre' => 'Residencia Los Almendros']);
    }
}
