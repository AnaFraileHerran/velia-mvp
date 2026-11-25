<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Área del equipo creada por la dirección (Ocio, Fisioterapia…). Sus personas tienen rol «otro» y no entran en la app. */
class AreaEquipo extends Model
{
    protected $table = 'areas_equipo';

    protected $fillable = ['nombre'];

    public function personas(): HasMany
    {
        return $this->hasMany(User::class, 'area_equipo_id');
    }
}
