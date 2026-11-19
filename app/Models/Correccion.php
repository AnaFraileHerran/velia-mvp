<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Correccion extends Model
{
    protected $table = 'correcciones';

    protected $fillable = ['corregible_type', 'corregible_id', 'campo', 'antes', 'ahora', 'motivo', 'user_id'];

    public function corregible(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
