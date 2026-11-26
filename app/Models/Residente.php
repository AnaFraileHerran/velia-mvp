<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Residente extends Model
{
    protected $fillable = [
        'nombre', 'dni', 'fecha_nacimiento', 'fecha_ingreso', 'planta_id', 'habitacion', 'tipo_habitacion',
        'estado', 'baja_motivo', 'baja_fecha', 'alergias', 'riesgos', 'movilidad', 'sujecion', 'dieta',
        'como_tratarle', 'notas', 'informes', 'plan_ultima_revision', 'plan_proxima_revision', 'plan_responsable_id', 'foto', 'sujecion_anotada',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'informes' => 'array',
            'fecha_ingreso' => 'date',
            'baja_fecha' => 'date',
            'plan_ultima_revision' => 'date',
            'plan_proxima_revision' => 'date',
            'sujecion' => 'boolean',
        ];
    }

    public function planta(): BelongsTo
    {
        return $this->belongsTo(Planta::class);
    }

    public function planResponsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'plan_responsable_id');
    }

    protected static function booted(): void
    {
        // Si alguien guarda el dato de sujeción (sí o no), deja de estar «sin anotar».
        static::saving(function (self $r) {
            if ($r->isDirty('sujecion')) {
                $r->sujecion_anotada = true;
            }
        });
    }

    public function contactos(): HasMany
    {
        return $this->hasMany(Contacto::class);
    }

    /** Comunicaciones con la familia (26/11/2026): evidencia para la inspección. */
    public function comunicaciones(): HasMany
    {
        return $this->hasMany(Comunicacion::class)->latest('fecha');
    }

    public function registros(): HasMany
    {
        return $this->hasMany(Registro::class)->latest();
    }

    public function ausencias(): HasMany
    {
        return $this->hasMany(Ausencia::class);
    }

    public function correcciones(): MorphMany
    {
        return $this->morphMany(Correccion::class, 'corregible')->latest();
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', '!=', 'baja');
    }

    public function contactoEmergencia(): ?Contacto
    {
        return $this->contactos->firstWhere('emergencia', true);
    }

    public function registradoEnTurno(?Turno $turno): bool
    {
        if (! $turno) {
            return false;
        }

        return $this->registros()->where('turno_id', $turno->id)->whereIn('estado', ['confirmado', 'sin_novedad'])->exists();
    }

    public function etiquetaEstado(): string
    {
        return match ($this->estado) {
            'en_centro' => 'En el centro',
            'fuera' => 'Fuera',
            'baja' => 'De baja',
            default => $this->estado,
        };
    }
}
