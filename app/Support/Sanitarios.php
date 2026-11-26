<?php

namespace App\Support;

use App\Filament\Direccion\Resources\AuxiliarResource;
use App\Models\Residencia;
use App\Models\User;

/**
 * Enfermería y medicina en «Ahora en el centro». No usan la app: Velia solo conoce su HORARIO, no si están.
 * Por eso se muestra «según horario», sin punto verde (decisión 23/11/2026, opción B1).
 */
class Sanitarios
{
    /** @return array<int, array{puesto: string, texto: string, contacto: ?string, ficha: ?string, ahora: bool}> */
    public static function ahora(): array
    {
        $dias = [1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sábado', 7 => 'domingo'];
        $hoy = (int) now()->isoWeekday();
        $hora = now()->format('H:i');
        $filas = [];

        foreach (User::sanitarios()->where('activo', true)->get()->groupBy('puesto') as $puesto => $personas) {
            $etiqueta = Residencia::PUESTOS[$puesto] ?? 'Equipo sanitario';
            $aqui = $personas->first(fn (User $u) => in_array($hoy, array_map('intval', $u->dias ?? []), true)
                && $u->desde && $u->hasta && $u->desde <= $hora && $hora < $u->hasta);

            if ($aqui) {
                $filas[] = ['puesto' => $etiqueta, 'texto' => strtok($aqui->name, ' ').' · hasta las '.$aqui->hasta, 'contacto' => $aqui->contacto,
                    'ficha' => AuxiliarResource::getUrl('view', ['record' => $aqui]), 'ahora' => true];

                continue;
            }

            // Próxima vez que viene alguien de ese puesto (hoy más tarde o los próximos 7 días)
            $proxima = null;
            foreach (range(0, 7) as $n) {
                $d = (($hoy - 1 + $n) % 7) + 1;
                $candidatas = $personas->filter(fn (User $u) => in_array($d, array_map('intval', $u->dias ?? []), true) && $u->desde
                    && ($n > 0 || $u->desde > $hora))->sortBy('desde');
                if ($candidatas->isNotEmpty()) {
                    $u = $candidatas->first();
                    $cuando = $n === 0 ? 'Hoy, '.$u->desde : ($n === 1 ? 'Mañana, '.$u->desde : ucfirst($dias[$d]).', '.$u->desde);
                    $proxima = ['texto' => $cuando, 'u' => $u];
                    break;
                }
            }
            $filas[] = ['puesto' => $etiqueta, 'texto' => $proxima['texto'] ?? 'Sin horario fijo', 'contacto' => $proxima['u']->contacto ?? null,
                'ficha' => isset($proxima['u']) ? AuxiliarResource::getUrl('view', ['record' => $proxima['u']]) : null, 'ahora' => false];
        }

        return $filas;
    }
}
