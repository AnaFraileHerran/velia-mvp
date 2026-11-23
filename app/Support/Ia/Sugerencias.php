<?php

namespace App\Support\Ia;

use App\Models\Aviso;
use App\Models\Registro;
use App\Models\Resumen;
use App\Models\User;
use App\Support\Franja;
use Illuminate\Support\Carbon;

/**
 * «Velia te sugiere» en los flujos (23/11/2026). La IA propone y la persona confirma: cada sugerencia se acepta,
 * se cambia o se descarta, y queda anotada (App\Models\SugerenciaIa).
 * Simulación con reglas sobre los datos de Velia: no sale nada de la residencia. Sustituible por un modelo real.
 */
class Sugerencias
{
    /**
     * Inicio · a quién asignar un aviso o qué preguntar al turno.
     *
     * @return array{tipo: string, texto: string, porque: string, datos: array}|null
     */
    public static function paraAviso(Aviso $aviso): ?array
    {
        $r = $aviso->residente;
        if (! $r) {
            return null;
        }

        if ($aviso->tipo === 'revision_vencida') {
            [$quien, $porque] = self::quienSigue($aviso);
            if (! $quien) {
                return null;
            }
            $plazo = $aviso->urgencia <= 1 ? today()->addDay() : today()->addDays(2);

            return [
                'tipo' => 'asignar',
                'texto' => 'Asignar a '.$quien->name.' para el '.self::dia($plazo),
                'porque' => $porque,
                'datos' => ['responsable_id' => $quien->id, 'vence_el' => $plazo->toDateString()],
            ];
        }

        if (in_array($aviso->tipo, ['patron', 'incidencia'])) {
            $pregunta = $aviso->tipo === 'patron'
                ? '¿Habéis notado algo distinto en '.strtok($r->nombre, ' ').' estos días (dolor, ruido, cambios en la cena)?'
                : '¿Cómo está '.strtok($r->nombre, ' ').' después de lo que se registró? ¿Hace falta avisar a enfermería?';

            return [
                'tipo' => 'preguntar',
                'texto' => 'Preguntar al turno: «'.$pregunta.'»',
                'porque' => $aviso->tipo === 'patron' ? 'Se repite: antes de decidir, conviene saber qué ve el equipo.' : 'Es una incidencia: la respuesta del turno queda como evidencia.',
                'datos' => ['texto' => $pregunta],
            ];
        }

        return null;
    }

    /** Quién debería hacer el seguimiento: su profesional de referencia, si no la responsable de turno de su planta, si no quien más le registra. */
    private static function quienSigue(Aviso $aviso): array
    {
        $r = $aviso->residente;
        if ($r->planResponsable && $r->planResponsable->activo) {
            return [$r->planResponsable, 'Es su profesional de referencia.'];
        }
        $responsable = User::auxiliares()->where('activo', true)->where('planta_id', $r->planta_id)->where('responsable_turno', true)->first();
        if ($responsable) {
            return [$responsable, 'No tiene profesional de referencia; es la responsable de turno de su planta.'];
        }
        $id = Registro::where('residente_id', $r->id)->where('created_at', '>=', now()->subDays(14))
            ->selectRaw('user_id, count(*) as n')->groupBy('user_id')->orderByDesc('n')->value('user_id');
        $u = $id ? User::find($id) : null;

        return [$u, $u ? 'Es quien más le ha atendido en las dos últimas semanas.' : ''];
    }

    /** Familias · borrador del resumen semanal en lenguaje sencillo, con los registros confirmados de la semana. */
    public static function resumenFamilia(Resumen $resumen): string
    {
        $r = $resumen->residente;
        $nombre = strtok($r->nombre, ' ');
        $registros = $resumen->registrosDeLaSemana()->orderBy('created_at')->get();
        if ($registros->isEmpty()) {
            return "Hola. Esta semana no hay registros de {$nombre} en Velia. Si quieres saber cómo está, llámanos y te lo contamos.";
        }

        $tranquilos = $registros->where('estado', 'sin_novedad')->count();
        $cats = $registros->flatMap(fn (Registro $x) => array_keys($x->categorias ?? []))->countBy();
        $frases = ["Hola. Te contamos cómo ha ido la semana de {$nombre}."];

        $bien = [];
        foreach (['Alimentación e hidratación' => 'ha comido y bebido con normalidad', 'Descanso' => 'ha descansado', 'Ánimo' => 'el equipo ha anotado cómo estaba de ánimo', 'Movilidad' => 'ha seguido con sus paseos y cambios de postura'] as $cat => $texto) {
            if (($cats[$cat] ?? 0) > 0) {
                $bien[] = $texto;
            }
        }
        if ($bien) {
            $frases[] = 'En general, '.implode(', ', array_slice($bien, 0, 3)).'.';
        }
        if ($tranquilos) {
            $frases[] = "Hubo {$tranquilos} ".($tranquilos === 1 ? 'turno' : 'turnos').' sin ninguna novedad.';
        }
        foreach ($registros->where('incidencia_destacada', true)->take(2) as $inc) {
            $txt = $inc->categorias['Incidencias'] ?? $inc->resumenCorto();
            $frases[] = 'El '.self::dia($inc->created_at).' el equipo anotó: «'.rtrim($txt, '. ').'». Si quieres más detalle, llámanos.';
        }
        $frases[] = 'Un saludo del equipo de '.(\App\Models\Residencia::actual()->nombre ?? 'la residencia').'.';

        return implode(' ', $frases);
    }

    /** Calidad · por dónde empezar: lo que más pesa en la auditoría y se resuelve antes. */
    public static function porDondeEmpezar(array $items): ?array
    {
        if (! $items) {
            return null;
        }
        $peso = ['plan' => 1, 'ingreso' => 2, 'sanitaria' => 3, 'familias' => 4, 'cuidados' => 5];
        usort($items, fn ($a, $b) => [($peso[$a['apartado']] ?? 9), -$a['n']] <=> [($peso[$b['apartado']] ?? 9), -$b['n']]);
        $i = $items[0];
        $porque = [
            'plan' => 'El plan personal y el profesional de referencia (apartados 5.2 y 5.3) son lo primero que mira la certificadora en cada expediente.',
            'ingreso' => 'La valoración inicial en el primer mes es obligatoria en cada ingreso (apartado 4).',
            'sanitaria' => 'Las sujeciones se revisan una a una (apartado 5.5). Anotar «No usa» también cuenta.',
            'familias' => 'Sin contacto de emergencia no se puede avisar a la familia ni enviar el resumen.',
            'cuidados' => 'Los avisos sin revisar dejan cuidados sin evidencia de que alguien los ha mirado.',
        ][$i['apartado']] ?? '';

        return ['texto' => 'Empieza por: '.$i['n'].' '.$i['texto'].'.', 'porque' => $porque, 'url' => $i['url'], 'accion' => $i['accion']];
    }

    /**
     * Nuevo residente · rellenar la ficha a partir de lo que dicta o escribe la directora.
     *
     * @return array<string, mixed> campo => valor propuesto
     */
    public static function fichaDesdeDictado(string $texto): array
    {
        $frases = preg_split('/(?<=[.;!?])\s+|\n+/u', trim($texto)) ?: [];
        $campos = [];
        $add = function (string $campo, string $frase) use (&$campos) {
            $campos[$campo] = trim(($campos[$campo] ?? '').' '.$frase);
        };
        foreach ($frases as $f) {
            $f = trim($f);
            $m = mb_strtolower($f);
            if ($f === '') {
                continue;
            }
            if (preg_match('/al[eé]rgic[oa]s? (a|al) (.+?)[.;]?$/u', $f, $x)) {
                $campos['alergias'] = trim($x[2], ' .');
            } elseif (preg_match('/no (tiene|se le conocen?) alergias/u', $m)) {
                $campos['alergias'] = 'Ninguna conocida';
            } elseif (preg_match('/sujeci|barandilla|cintur[oó]n/u', $m)) {
                // El formulario usa 1 (Sí) y 0 (No); al guardar, la sujeción deja de estar «sin anotar».
                $campos['sujecion'] = preg_match('/\bno (usa|lleva|necesita)\b|\bsin sujeci/u', $m) ? 0 : 1;
            } elseif (preg_match('/dieta|textura|triturad|blanda|sin sal|diab[eé]tic|disfagia|espesante/u', $m)) {
                $add('dieta', $f);
            } elseif (preg_match('/andador|silla de ruedas|bast[oó]n|camina|gr[uú]a|encamad|movilidad/u', $m)) {
                $add('movilidad', $f);
            } elseif (preg_match('/riesgo|ca[ií]das?|se cae|desorient|fuga|deambul|[uú]lcera/u', $m)) {
                $add('riesgos', $f);
            } elseif (preg_match('/le gusta|prefiere|ll[aá]mal[ea]|tr[aá]tal[ea]|se pone nervios|le calma|le tranquiliza/u', $m)) {
                $add('como_tratarle', $f);
            } elseif (preg_match('/habitaci[oó]n (\d+)/u', $m, $x)) {
                $campos['habitacion'] = $x[1];
            } else {
                $add('notas', $f);
            }
        }

        return $campos;
    }

    /** Registrar · categorías que suelen anotarse en esta franja y no se han mencionado. */
    public static function faltanEnRegistro(array $categorias, ?array $franja = null): array
    {
        $franja ??= Franja::actual();
        $esperadas = [
            'manana' => ['Alimentación e hidratación', 'Higiene'],
            'tarde' => ['Alimentación e hidratación', 'Ánimo'],
            'noche' => ['Descanso'],
        ][$franja['clave']] ?? [];

        return array_values(array_diff($esperadas, array_keys($categorias)));
    }

    private static function dia(Carbon $f): string
    {
        return $f->locale('es')->isoFormat('dddd D');
    }
}
