{{-- Wireframe «V2 · Enviar el resumen»: primero a quién va, después qué recibirá. --}}
@php
    $nombres = $contactos->pluck('nombre')->map(fn ($n) => strtok($n, ' '))->join(', ', ' y ');
    $registros = $resumen->registrosDeLaSemana()->get();
@endphp
<div class="space-y-6">
    <section>
        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Destinatarios</h3>
        <ul class="mt-2 divide-y divide-gray-100 rounded-lg border border-gray-200 dark:divide-white/5 dark:border-white/10">
            @foreach ($contactos as $c)
                <li class="flex items-center justify-between gap-4 px-3 py-2 text-sm">
                    <span class="font-medium text-gray-950 dark:text-white">{{ $c->nombre }} · {{ $c->relacion }}</span>
                    <span class="text-gray-500 dark:text-gray-400">
                        {{ $c->canal === 'whatsapp' ? $c->telefono.' · WhatsApp corporativo' : $c->email.' · email' }}
                    </span>
                </li>
            @endforeach
        </ul>
    </section>

    <section>
        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Vista previa</h3>
        <div class="mt-2 rounded-lg bg-gray-50 p-4 text-sm leading-6 text-gray-700 dark:bg-white/5 dark:text-gray-200">
            <p>Hola, {{ $nombres }}:</p>
            <p class="mt-2">{{ $resumen->texto }}</p>
            <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                Si tenéis dudas, llamad al {{ $residencia->telefono }} o escribid a {{ $residencia->email }} · {{ $residencia->nombre }}
            </p>
        </div>
    </section>

    <p class="text-xs text-gray-500 dark:text-gray-400">
        Se ha hecho con {{ $registros->count() }} registros confirmados de la semana
        @if ($resumen->tiene_incidencias) · incluye {{ $resumen->numeroIncidencias() }} incidencia(s): revisa cómo se cuenta @endif.
    </p>
</div>
