<?php

namespace App\Support\Ia;

use App\Support\Destinos;
use App\Support\PendientesAuditoria;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Asistente de Ayuda («Pregúntale a Velia»). Responde dudas de uso y lleva al sitio donde se resuelven.
 * Simulado por defecto (config/velia.php). Con un proveedor real, solo se envía la pregunta: nunca datos de residentes.
 */
class Asistente
{
    public static function proveedor(): string
    {
        $p = config('velia.ia.proveedor', 'simulado');

        return $p === 'anthropic' && config('velia.ia.clave') ? 'anthropic' : 'simulado';
    }

    public static function esSimulado(): bool
    {
        return self::proveedor() === 'simulado';
    }

    /** @return array{texto: string, enlaces: array<int, array{titulo: string, url: string}>, proveedor: string} */
    public static function responder(string $pregunta): array
    {
        $pregunta = trim(mb_substr($pregunta, 0, 500));
        if (self::proveedor() === 'anthropic') {
            try {
                return self::conModelo($pregunta);
            } catch (Throwable $e) {
                report($e);
                $r = self::simulado($pregunta);
                $r['texto'] = 'No he podido conectar con el asistente. Te respondo con la ayuda guardada: '.$r['texto'];

                return $r;
            }
        }

        return self::simulado($pregunta);
    }

    /** Intenciones frecuentes de la dirección. Cada una: palabras que la activan, respuesta corta y destinos. */
    private static function intenciones(): array
    {
        return [
            [['auditoria', 'falta', 'une', 'norma', 'certific'], fn () => self::loQueFalta(), ['calidad']],
            [['alta', 'auxiliar', 'contrat', 'nueva', 'trabajador'], 'En Equipo, «Añadir auxiliar». Velia genera un PIN provisional y un código para su móvil: díselos en persona. El primer día crea su propio PIN.', ['equipo-nueva', 'equipo']],
            [['pin', 'entrar', 'olvid', 'acceso', 'movil nuevo'], 'Abre su ficha en Equipo y pulsa «No puede entrar». Eliges si ha olvidado el PIN o tiene un móvil nuevo, y Velia genera el código. De noche, también lo puede hacer la responsable de turno desde la tablet.', ['equipo']],
            [['ingreso', 'residente nuevo', 'alta residente', 'dar de alta a un residente'], 'En Residentes, «Añadir residente». Puedes dictar lo que sabes de la persona y Velia rellena la ficha de cuidados; tú lo revisas antes de guardar. Al guardar, se crea el aviso de la valoración inicial (antes de un mes).', ['residente-nuevo']],
            [['familia', 'resumen', 'enviar', 'whatsapp'], 'En Familias, revisa los resúmenes con incidencias uno a uno. Con «Redactar con Velia» tienes un borrador hecho con los registros de la semana; lo editas y lo apruebas tú. Los que no tienen incidencias se pueden enviar juntos.', ['familias']],
            [['plan', 'pai', 'revision', 'referencia'], 'El plan personal se revisa al menos cada seis meses y cada residente necesita un profesional de referencia. En Residentes tienes los filtros «Plan vencido» y «Sin profesional de referencia».', ['planes-vencidos', 'sin-referencia']],
            [['sujecion', 'barandilla', 'cinturon'], 'Anota en la ficha de cada residente si usa sujeción o no («Corregir» en Cuidados). Si la usa, cada uso se registra en el turno. Los que están sin anotar cuentan como pendiente para la auditoría.', ['sujecion']],
            [['hospital', 'ausencia', 'sale', 'fuera', 'vuelve'], 'En Ausencias registra la salida y cuándo vuelve. Las auxiliares lo ven en su relevo y el residente no cuenta como «por registrar».', ['ausencias']],
            [['turno sin abrir', 'nadie', 'falta alguien', 'no ha venido'], 'Si a los 30 minutos de empezar la franja una planta no tiene turno abierto, sale un aviso arriba en Inicio con «Ver quién falta»: quién debería estar, su contacto y el estado de su PIN.', ['inicio']],
            [['microfono', 'voz', 'dictar', 'conexion', 'internet'], 'Nada bloquea un registro: si falla el micrófono o la conexión, siempre se puede escribir, y el texto se guarda en la tablet hasta que vuelve la red. Revisa el permiso del micrófono en los ajustes del navegador.', ['ayuda']],
            [['corregir', 'error', 'dato mal', 'equivoc'], 'Usa «Corregir» en el bloque de la ficha. Pide un motivo y guarda la versión anterior: así queda la evidencia para la auditoría.', ['residentes']],
            [['ocupacion', 'plazas', 'libres'], 'En Residencia y ocupación ves las plazas ocupadas y libres por planta.', ['ocupacion']],
            [['hoy', 'decidir', 'aviso', 'empez', 'que hago'], 'Empieza por Inicio: «Hoy necesita tu decisión» muestra como máximo 5 cosas, ordenadas por urgencia, y Velia te sugiere a quién asignarlas. Si no hay ninguna, el día va bien.', ['inicio']],
        ];
    }

    private static function simulado(string $pregunta): array
    {
        $q = Destinos::normalizar($pregunta);
        $mejor = null;
        $puntos = 0;
        foreach (self::intenciones() as [$claves, $texto, $destinos]) {
            $p = collect($claves)->filter(fn ($c) => str_contains($q, $c))->count();
            if ($p > $puntos) {
                [$mejor, $puntos] = [[$texto, $destinos], $p];
            }
        }

        if ($mejor) {
            [$texto, $ids] = $mejor;
            $enlaces = collect($ids)->map(fn ($id) => Destinos::porId($id))->filter()->map(fn ($d) => ['titulo' => $d['titulo'], 'url' => $d['url']])->values()->all();

            return ['texto' => $texto instanceof \Closure ? $texto() : $texto, 'enlaces' => $enlaces, 'proveedor' => 'simulado'];
        }

        $encontrados = Destinos::buscar($pregunta);
        if ($encontrados) {
            return ['texto' => 'No tengo una respuesta preparada para eso, pero esto puede ser lo que buscas:', 'enlaces' => array_map(fn ($d) => ['titulo' => $d['titulo'], 'url' => $d['url']], $encontrados), 'proveedor' => 'simulado'];
        }

        return ['texto' => 'No lo sé todavía. Durante el piloto, tu contacto en Icarus te responde. Prueba también con otras palabras, por ejemplo «auditoría», «familias» o «PIN».', 'enlaces' => [], 'proveedor' => 'simulado'];
    }

    /** Respuesta con datos de Velia (no salen de la residencia): lo que falta para la auditoría. */
    private static function loQueFalta(): string
    {
        $p = PendientesAuditoria::resumen();
        if (! $p['total']) {
            return 'Con lo que mide Velia, no te falta nada para la auditoría. En Calidad ves también los apartados que lleva tu consultora o tu programa de gestión.';
        }
        $lineas = collect($p['items'])->take(3)->map(fn ($i) => $i['n'].' '.$i['texto'])->join('; ');

        return 'Te faltan '.$p['total'].' cosas. Lo primero: '.$lineas.'. En Calidad tienes cada una con el botón para resolverla.';
    }

    private static function conModelo(string $pregunta): array
    {
        $destinos = collect(Destinos::fijos())->map(fn ($d) => $d['id'].': '.$d['titulo'])->join("\n");
        $sistema = "Eres el asistente de ayuda de Velia, un software para residencias de mayores pequeñas de Madrid. Hablas con la directora.\n"
            ."Responde en español de España, tuteando, en 2 o 3 frases cortas y sin jerga. No inventes funciones: si no lo sabes, dilo.\n"
            ."No pidas ni des datos de residentes. Al final, en líneas aparte, escribe IR: seguido del id de hasta 2 pantallas útiles de esta lista:\n".$destinos;

        $r = Http::timeout(15)->withHeaders([
            'x-api-key' => config('velia.ia.clave'),
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => config('velia.ia.modelo'),
            'max_tokens' => 400,
            'system' => $sistema,
            'messages' => [['role' => 'user', 'content' => $pregunta]],
        ])->throw()->json();

        $texto = collect($r['content'] ?? [])->where('type', 'text')->pluck('text')->join("\n");
        preg_match_all('/^IR:\s*([a-z0-9-]+)/m', $texto, $m);
        $enlaces = collect($m[1])->map(fn ($id) => Destinos::porId($id))->filter()->map(fn ($d) => ['titulo' => $d['titulo'], 'url' => $d['url']])->values()->all();

        return ['texto' => trim(preg_replace('/^IR:.*$/m', '', $texto)), 'enlaces' => $enlaces, 'proveedor' => 'anthropic'];
    }
}
