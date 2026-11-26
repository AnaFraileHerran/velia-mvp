<?php

return [
    /*
     * Asistente e IA de Velia (23/11/2026).
     * - «simulado» (por defecto): respuestas y sugerencias preparadas con reglas, marcadas como «Ejemplo». No sale nada de la residencia.
     * - «anthropic»: el asistente de Ayuda usa un modelo real. Solo se envía la pregunta escrita o dictada, nunca datos de residentes.
     *   Antes de activarlo con datos reales: visto bueno legal (datos de salud, categoría especial) y acuerdo de tratamiento con el proveedor.
     */
    'ia' => [
        'proveedor' => env('VELIA_IA', 'simulado'),
        'clave' => env('ANTHROPIC_API_KEY'),
        'modelo' => env('VELIA_IA_MODELO', 'claude-haiku-5-5'),
    ],
];
