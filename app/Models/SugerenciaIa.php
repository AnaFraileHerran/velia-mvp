<?php

namespace App\Models;

use App\Support\Ia\Asistente;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Una sugerencia de la IA y la decisión de la persona. «La IA propone y el profesional confirma»: queda rastro de las dos cosas. */
class SugerenciaIa extends Model
{
    protected $table = 'sugerencias_ia';

    protected $fillable = ['flujo', 'objeto_type', 'objeto_id', 'sugerencia', 'decision', 'detalle', 'user_id', 'proveedor'];

    public const DECISIONES = ['aceptada' => 'Aceptada', 'cambiada' => 'Cambiada', 'descartada' => 'Descartada'];

    public function objeto(): MorphTo
    {
        return $this->morphTo();
    }

    public static function anotar(string $flujo, string $sugerencia, string $decision, ?Model $objeto = null, ?string $detalle = null): self
    {
        return self::create([
            'flujo' => $flujo,
            'objeto_type' => $objeto?->getMorphClass(),
            'objeto_id' => $objeto?->getKey(),
            'sugerencia' => $sugerencia,
            'decision' => $decision,
            'detalle' => $detalle,
            'user_id' => Filament::auth()->id(),
            'proveedor' => Asistente::proveedor(),
        ]);
    }
}
