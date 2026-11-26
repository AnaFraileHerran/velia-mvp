<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una comunicación con la familia de un residente (26/11/2026): quién, qué, cuándo y por qué canal.
 * Es evidencia para la inspección y la auditoría (guía de autoevaluación 2026 de Madrid). No se edita ni se borra:
 * si hay un error, se anota otra comunicación que lo aclara (regla «nada se borra sin rastro»).
 */
class Comunicacion extends Model
{
    protected $table = 'comunicaciones';

    public const MOTIVOS = [
        'caida' => 'Caída',
        'derivacion' => 'Derivación al hospital',
        'revision_plan' => 'Revisión del plan personal',
        'seguimiento' => 'Seguimiento',
        'resumen_semanal' => 'Resumen semanal',
        'otro' => 'Otro motivo',
    ];

    public const CANALES = [
        'telefono' => 'Por teléfono',
        'en_persona' => 'En persona',
        'email' => 'Por email',
        'whatsapp' => 'WhatsApp corporativo',
    ];

    protected $fillable = ['residente_id', 'contacto_id', 'a_quien', 'motivo', 'canal', 'que', 'fecha', 'registrado_por', 'aviso_id', 'resumen_id'];

    protected function casts(): array
    {
        return ['fecha' => 'datetime'];
    }

    public function residente(): BelongsTo
    {
        return $this->belongsTo(Residente::class);
    }

    public function contacto(): BelongsTo
    {
        return $this->belongsTo(Contacto::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    /** Una línea para listas: «Jue 26 nov, 18:10 · Caída · a Lucía (hija) por teléfono». */
    public function linea(): string
    {
        return $this->fecha->locale('es')->isoFormat('ddd D MMM, HH:mm').' · '.(self::MOTIVOS[$this->motivo] ?? $this->motivo)
            .' · a '.$this->a_quien.' '.mb_strtolower(self::CANALES[$this->canal] ?? $this->canal);
    }

    /** Opciones de «¿A quién?» para un residente: sus contactos, primero el de emergencia. */
    public static function opcionesContacto(?Residente $r): array
    {
        if (! $r) {
            return [];
        }

        return $r->contactos->sortByDesc('emergencia')->mapWithKeys(fn (Contacto $c) => [$c->id => "{$c->nombre} ({$c->relacion})"])->all();
    }

    /** Guarda una comunicación desde un formulario de la dirección. */
    public static function anotar(Residente $r, array $data, ?Aviso $aviso = null): self
    {
        $c = isset($data['contacto_id']) ? Contacto::find($data['contacto_id']) : null;

        return self::create([
            'residente_id' => $r->id,
            'contacto_id' => $c?->id,
            'a_quien' => $c ? "{$c->nombre} ({$c->relacion})" : trim((string) ($data['a_quien'] ?? 'Familia')),
            'motivo' => $data['motivo'],
            'canal' => $data['canal'],
            'que' => trim((string) $data['que']),
            'fecha' => $data['fecha'] ?? now(),
            'registrado_por' => \Filament\Facades\Filament::auth()->id(),
            'aviso_id' => $aviso?->id,
        ]);
    }
}
