<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Planta extends Model
{
    protected $fillable = ['nombre'];

    public function auxiliares(): HasMany
    {
        return $this->hasMany(User::class)->where('rol', 'auxiliar');
    }

    public function residentes(): HasMany
    {
        return $this->hasMany(Residente::class);
    }
}
