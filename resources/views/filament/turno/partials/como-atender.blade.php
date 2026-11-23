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
    <dl class="space-y-3">
        @foreach (['Movilidad' => $r->movilidad, 'Sujeción' => $r->sujecion ? 'Sí. Registra cada uso.' : 'No indicada.', 'Dieta y textura' => $r->dieta, 'Cómo tratarle' => $r->como_tratarle] as $t => $v)
            <div>
                <dt class="font-semibold text-gray-950 dark:text-white">{{ $t }}</dt>
                <dd class="text-gray-600 dark:text-gray-300">{{ $v ?: '—' }}</dd>
            </div>
        @endforeach
        @if ($c = $r->contactoEmergencia())
            <div>
                <dt class="font-semibold text-gray-950 dark:text-white">Contacto de emergencia</dt>
                <dd><a href="tel:{{ preg_replace('/\s+/', '', $c->telefono) }}" class="text-primary-600 underline">{{ $c->nombre }} ({{ $c->relacion }}) · {{ $c->telefono }}</a></dd>
            </div>
        @endif
    </dl>
</div>
