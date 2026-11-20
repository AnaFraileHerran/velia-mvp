<?php

namespace App\Support;

use App\Models\Correccion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Toda corrección de datos pide motivo y guarda versión (regla 5 de CLAUDE.md).
 * Es la evidencia que revisa la auditoría UNE 158101.
 */
class Correcciones
{
    /** @param array<string, mixed> $cambios campo => valor nuevo */
    public static function aplicar(Model $modelo, array $cambios, string $motivo): int
    {
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw new \InvalidArgumentException('El motivo es obligatorio.');
        }

        return DB::transaction(function () use ($modelo, $cambios, $motivo) {
            $hechos = 0;
            foreach ($cambios as $campo => $nuevo) {
                $antes = $modelo->getAttribute($campo);
                $antesTexto = self::texto($antes);
                $nuevoTexto = self::texto($nuevo);
                if ($antesTexto === $nuevoTexto) {
                    continue;
                }
                Correccion::create([
                    'corregible_type' => $modelo::class,
                    'corregible_id' => $modelo->getKey(),
                    'campo' => $campo,
                    'antes' => $antesTexto,
                    'ahora' => $nuevoTexto,
                    'motivo' => $motivo,
                    'user_id' => \Filament\Facades\Filament::auth()->id(),
                ]);
                $modelo->setAttribute($campo, $nuevo);
                $hechos++;
            }
            $modelo->save();

            return $hechos;
        });
    }

    private static function texto(mixed $valor): ?string
    {
        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d');
        }
        if (is_array($valor)) {
            return $valor === [] ? null : implode(', ', array_map(fn ($v) => basename((string) $v), $valor));
        }
        if (is_bool($valor)) {
            return $valor ? 'Sí' : 'No';
        }

        return $valor === null ? null : (string) $valor;
    }
}
