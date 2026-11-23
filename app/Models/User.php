<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'rol', 'pin', 'pin_provisional', 'codigo_invitacion',
        'planta_id', 'turno_habitual', 'responsable_turno', 'activo', 'baja_motivo',
        'pin_restablecido_at', 'pin_restablecido_por',
        'foto', 'puesto', 'dias', 'desde', 'hasta', 'contacto', 'telefono',
    ];

    protected $hidden = ['password', 'pin', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'pin_provisional' => 'boolean',
            'responsable_turno' => 'boolean',
            'activo' => 'boolean',
            'pin_restablecido_at' => 'datetime',
            'dias' => 'array',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->activo) {
            return false;
        }

        return match ($panel->getId()) {
            'direccion' => $this->rol === 'direccion',
            'turno' => $this->rol === 'auxiliar',
            default => false,
        };
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function planta(): BelongsTo
    {
        return $this->belongsTo(Planta::class);
    }

    public function turnos(): HasMany
    {
        return $this->hasMany(Turno::class);
    }

    public function turnoAbierto(): ?Turno
    {
        return $this->turnos()->whereNull('fin')->latest('inicio')->first();
    }

    /** Enfermería, medicina y otros profesionales: están en Equipo pero no entran en la app. */
    public function scopeSanitarios(Builder $query): Builder
    {
        return $query->where('rol', 'sanitario');
    }

    public function etiquetaPuesto(): string
    {
        return Residencia::PUESTOS[$this->puesto] ?? 'Equipo sanitario';
    }

    /** «L–V · 08:00–15:00» */
    public function etiquetaHorario(): string
    {
        $dias = collect($this->dias ?? [])->map(fn ($d) => (int) $d)->sort()->values();
        if ($dias->isEmpty()) {
            return 'Sin horario fijo';
        }
        $letras = [1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'];
        $seguidos = $dias->count() > 2 && $dias->last() - $dias->first() === $dias->count() - 1;
        $texto = $seguidos ? $letras[$dias->first()].'–'.$letras[$dias->last()] : $dias->map(fn ($d) => $letras[$d])->join(', ');

        return $texto.($this->desde && $this->hasta ? ' · '.$this->desde.'–'.$this->hasta : '');
    }

    public function scopeAuxiliares(Builder $query): Builder
    {
        return $query->where('rol', 'auxiliar');
    }

    /** Genera un PIN provisional de 4 cifras, lo guarda y lo devuelve en claro una sola vez. */
    public function generarPinProvisional(?User $por = null): string
    {
        $pin = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $this->forceFill([
            'pin' => Hash::make($pin),
            'pin_provisional' => true,
            'pin_restablecido_at' => now(),
            'pin_restablecido_por' => $por?->id,
        ])->save();

        return $pin;
    }

    public function etiquetaTurno(): string
    {
        return match ($this->turno_habitual) {
            'manana' => 'Mañana',
            'tarde' => 'Tarde',
            'noche' => 'Noche',
            default => 'Sin turno',
        };
    }
}
