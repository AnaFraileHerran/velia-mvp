<?php

namespace App\Support;

/**
 * Simulación de «la IA ordena lo que dices»: reparte frases por categorías con palabras clave.
 * Sustituir por el modelo de IA real cuando se conecte. La auxiliar siempre revisa y confirma.
 */
class OrdenarRegistro
{
    private const CLAVES = [
        'Alimentación e hidratación' => ['com', 'desayun', 'cen', 'bebi', 'agua', 'hidrat', 'merend', 'almuerz'],
        'Higiene' => ['duch', 'aseo', 'lav', 'pañal', 'baño', 'higiene'],
        'Movilidad' => ['andador', 'camin', 'levant', 'silla', 'paseo', 'cambio postural', 'cambios posturales'],
        'Sujeción' => ['sujeci', 'barandill', 'cinturón'],
        'Descanso' => ['dorm', 'durm', 'sueño', 'despert', 'siesta', 'noche'],
        'Ánimo' => ['triste', 'content', 'tranquil', 'nervios', 'agitad', 'llor', 'habladora', 'ánimo'],
        'Incidencias' => ['caíd', 'cayó', 'mare', 'rojez', 'herida', 'úlcera', 'golpe', 'fiebre', 'vómit'],
    ];

    /** @return array{categorias: array<string,string>, incidencia: bool, tipo_incidencia: ?string} */
    public static function ordenar(string $texto): array
    {
        $frases = preg_split('/(?<=[.;!?])\s+|\n+/u', trim($texto)) ?: [];
        $categorias = [];
        $incidencia = false;
        $tipo = null;

        foreach ($frases as $frase) {
            $f = trim($frase);
            if ($f === '') {
                continue;
            }
            $min = mb_strtolower($f);
            $asignada = 'Observaciones';
            foreach (self::CLAVES as $cat => $claves) {
                foreach ($claves as $clave) {
                    if (str_contains($min, $clave)) {
                        $asignada = $cat;
                        break 2;
                    }
                }
            }
            // Una frase puede ser de Movilidad y a la vez una incidencia (p. ej. «se ha mareado al levantarse»).
            $esIncidencia = $asignada === 'Incidencias';
            foreach (self::CLAVES['Incidencias'] as $clave) {
                $esIncidencia = $esIncidencia || str_contains($min, $clave);
            }
            if ($esIncidencia) {
                $incidencia = true;
                $tipo = str_contains($min, 'rojez') || str_contains($min, 'úlcera') ? 'Piel y úlceras' : 'Caídas y mareos';
            }
            $categorias[$asignada] = trim(($categorias[$asignada] ?? '').' '.$f);
        }

        return ['categorias' => $categorias, 'incidencia' => $incidencia, 'tipo_incidencia' => $tipo];
    }
}
