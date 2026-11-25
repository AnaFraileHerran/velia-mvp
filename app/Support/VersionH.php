<?php

namespace App\Support;

/**
 * Versión H de la web de dirección (25/11/2026): mismo flujo y mismos datos que /direccion,
 * con patrones de uso inspirados en Holded (crear desde cualquier sitio, Inicio con pestañas,
 * listas con estados y contador, ayuda por sección). Sirve para compararlas en un test con directoras.
 */
class VersionH
{
    public const PANEL = 'direccion-h';

    public static function activa(): bool
    {
        return filament()->getCurrentPanel()?->getId() === self::PANEL;
    }

    /** Ayuda corta de cada sección (el «libro» de Holded). Clave: parte del nombre de la clase de la página. */
    public static function ayudaSeccion(array $scopes): ?array
    {
        $clase = implode(' ', $scopes);
        $secciones = [
            'Inicio' => ['Inicio', 'Lo que necesita tu decisión hoy, cómo va la semana y lo que falta para la auditoría, cada cosa en su pestaña.', ['Decide los avisos de «Hoy» de arriba abajo.', 'Las pestañas muestran cuántas cosas hay en cada una.'], '¿Por dónde empiezo hoy?'],
            'Residente' => ['Residentes', 'Quién vive en la residencia, dónde está y qué le falta a su ficha.', ['Las pestañas separan quién está en el centro, quién ha salido y a quién le falta algo.', 'Para cambiar un dato, abre la ficha y usa «Corregir» en el bloque: pide motivo.'], '¿Cómo corrijo un dato de un residente?'],
            'Auxiliar' => ['Equipo', 'Las auxiliares y el personal de enfermería y medicina, con su PIN y sus turnos.', ['Si alguien olvida el PIN, lo restablece la responsable de turno.', 'Dar de baja está en el menú «…» de cada persona.'], '¿Cómo restablezco un PIN?'],
            'Ausencia' => ['Ausencias', 'Salidas de residentes: quién está fuera ahora, las previstas y las que ya han vuelto.', ['Registra la salida antes de que ocurra si la conoces.', 'Al volver, márcalo para que el turno lo vea.'], '¿Cómo registro una salida?'],
            'Resumen' => ['Familias', 'El resumen de la semana de cada residente, hecho con los registros confirmados.', ['Revisa los que tienen incidencias uno a uno.', 'Selecciona varios para enviar de una vez los que no tienen incidencias.'], '¿Qué se envía a las familias?'],
            'Calidad' => ['Calidad y auditoría', 'Lo que pide la UNE 158101 y lo que Velia ya puede enseñar con tus registros.', ['Los indicadores marcados «Ejemplo» aún no se calculan de verdad.', 'Empieza por lo que falta: cada línea lleva a dónde resolverlo.'], '¿Qué me falta para la auditoría?'],
            'MiResidencia' => ['Mi residencia', 'Datos del centro, plantas y plazas.', ['Estos datos salen en los informes y en el resumen de las familias.'], '¿Dónde cambio los datos del centro?'],
            'Ocupacion' => ['Residencia y ocupación', 'Plazas ocupadas y libres, y movimientos del mes.', ['Las altas y bajas se hacen desde Residentes.'], '¿Cómo doy de alta a un residente?'],
        ];
        foreach ($secciones as $clave => [$titulo, $texto, $consejos, $pregunta]) {
            if (str_contains($clase, $clave)) {
                return compact('titulo', 'texto', 'consejos', 'pregunta');
            }
        }

        return null;
    }
}
