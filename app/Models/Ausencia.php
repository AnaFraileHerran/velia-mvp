<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ausencia extends Model
{
    public const TIPOS = [
        'familia' => 'Salida con la familia',
        'hospital' => 'Hospital',
        'vacaciones' => 'Vacaciones',
        'otro' => 'Otro',
    ];

    protected $fillable = ['residente_id', 'tipo', 'sale', 'vuelve', 'ha_vuelto', 'registrada_por'];

    protected function casts(): array
    {
        return ['sale' => 'date', 'vuelve' => 'date', 'ha_vuelto' => 'boolean'];
    }

    public function residente(): BelongsTo
    {
        return $this->belongsTo(Residente::class);
    }

    public function registradaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrada_por');
    }
}
