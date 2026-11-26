{{-- Lo que puede hacer daño va primero y destacado: alergia y riesgo. --}}
<div class="space-y-4 text-sm">
    {{-- Foto grande: reconocer a la persona antes que leer su ficha (primer día, suplencias) --}}
    <div class="flex items-center gap-3">
        <img src="{{ \App\Support\Avatar::url($r->foto, $r->nombre) }}" alt="Foto de {{ $r->nombre }}" style="width:72px;height:72px;border-radius:9999px;object-fit:cover;flex-shrink:0">
        <div>
            <p class="text-base font-semibold text-gray-950 dark:text-white">{{ $r->nombre }}</p>
            <p class="text-gray-500">{{ $r->planta?->nombre }} · Hab. {{ $r->habitacion }}</p>
        </div>
    </div>
    @if ($r->alergias || $r->riesgos)
        <div class="rounded-xl bg-danger-50 p-3 font-semibold text-danger-700 dark:bg-danger-400/10 dark:text-danger-400">
            @if ($r->alergias) Alergia: {{ $r->alergias }} @endif
            @if ($r->alergias && $r->riesgos) · @endif
            @if ($r->riesgos) Riesgo: {{ $r->riesgos }} @endif
        </div>
    @endif
    {{-- Icono junto a cada dato (25/11/2026, comentario de Antonio en A1-04): se reconoce el bloque antes de leerlo. Iconos propios de trazo fino. --}}
    @php
        $svg = fn ($d) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$d.'</svg>';
        $iconos = [
            // Silla de ruedas
            'Movilidad' => $svg('<circle cx="9" cy="4" r="1.6"/><path d="M9 7v6h6l2.5 5"/><path d="M9 10h5"/><path d="M7 11.5a5 5 0 1 0 7.2 5.8"/>'),
            // Cinturón con hebilla
            'Sujeción' => $svg('<path d="M3 9h18v6H3z"/><rect x="9" y="8" width="6" height="8" rx="1.2"/><path d="M12 10.5v3"/>'),
            // Plato con tenedor y cuchillo
            'Dieta y textura' => $svg('<circle cx="12" cy="12" r="5.5"/><circle cx="12" cy="12" r="3"/><path d="M3 5v4a1.5 1.5 0 0 0 3 0V5M4.5 9v10"/><path d="M20 5c-1.3 0-2 1.5-2 4h2v10"/>'),
            // Bocadillo de conversación
            'Cómo tratarle' => $svg('<path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v7a2.5 2.5 0 0 1-2.5 2.5H10l-4 4v-4H6.5A2.5 2.5 0 0 1 4 13.5z"/><path d="M8.5 9.5h7M8.5 12.5h4"/>'),
            // Teléfono
            'Contacto de emergencia' => $svg('<path d="M6.5 3.5 9 4l1.5 4-2 1.5a10 10 0 0 0 6 6l1.5-2 4 1.5.5 2.5a2 2 0 0 1-2 2.5A16 16 0 0 1 4 5.5a2 2 0 0 1 2.5-2z"/>'),
        ];
    @endphp
    <dl class="space-y-3">
        @foreach (['Movilidad' => $r->movilidad, 'Sujeción' => $r->sujecion ? 'Sí. Registra cada uso.' : 'No indicada.', 'Dieta y textura' => $r->dieta, 'Cómo tratarle' => $r->como_tratarle] as $t => $v)
            <div class="velia-dato">
                <span class="velia-dato-icono">{!! $iconos[$t] !!}</span>
                <div>
                    <dt class="font-semibold text-gray-950 dark:text-white">{{ $t }}</dt>
                    <dd class="text-gray-600 dark:text-gray-300">{{ $v ?: '—' }}</dd>
                </div>
            </div>
        @endforeach
        @if ($c = $r->contactoEmergencia())
            <div class="velia-dato">
                <span class="velia-dato-icono">{!! $iconos['Contacto de emergencia'] !!}</span>
                <div>
                    <dt class="font-semibold text-gray-950 dark:text-white">Contacto de emergencia</dt>
                    <dd><a href="tel:{{ preg_replace('/\s+/', '', $c->telefono) }}" class="text-primary-600 underline">{{ $c->nombre }} ({{ $c->relacion }}) · {{ $c->telefono }}</a></dd>
                </div>
            </div>
        @endif
    </dl>
</div>
