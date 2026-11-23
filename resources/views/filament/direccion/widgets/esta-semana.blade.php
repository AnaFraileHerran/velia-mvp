<x-filament-widgets::widget class="velia-alto">
    <div class="flex flex-col gap-6">
        <x-filament::section heading="Esta semana" description="Lo que vence o está listo en los próximos 7 días.">
            <ul class="flex flex-col gap-3 text-sm">
                @if ($sinEmergencia)
                    {{-- Datos que faltan: riesgo para el cuidado (caída de noche sin a quién llamar) y para la auditoría --}}
                    <li class="flex items-center justify-between gap-2">
                        <span><span class="font-semibold text-gray-950 dark:text-white">{{ $sinEmergencia }} {{ $sinEmergencia === 1 ? 'residente' : 'residentes' }}</span> sin contacto de emergencia</span>
                        <x-filament::link :href="\App\Filament\Direccion\Resources\ResidenteResource::getUrl('index', ['tableFilters' => ['sin_emergencia' => ['isActive' => true]]])" style="white-space:nowrap;flex-shrink:0">Completar</x-filament::link>
                    </li>
                @endif
                <li class="flex items-center justify-between gap-2">
                    <span>{{ $resumenesPorRevisar }} resúmenes para las familias por revisar</span>
                    <x-filament::link :href="\App\Filament\Direccion\Resources\ResumenResource::getUrl()" class="shrink-0 whitespace-nowrap" style="white-space:nowrap;flex-shrink:0">Ir a Familias</x-filament::link>
                </li>
                @foreach ($revisiones as $r)
                    <li class="flex items-center justify-between gap-2">
                        <span>Revisión del plan personal de {{ $r->nombre }} · vence el {{ $r->plan_proxima_revision->locale('es')->isoFormat('D MMM') }}</span>
                        <x-filament::link :href="\App\Filament\Direccion\Resources\ResidenteResource::getUrl('view', ['record' => $r])" style="white-space:nowrap;flex-shrink:0">Ver</x-filament::link>
                    </li>
                @endforeach
                @if ($esperando || $respondidas)
                    <li class="flex items-center justify-between gap-2">
                        {{-- Solo lo más urgente: si hay respuestas, leerlas; el resto se ve al abrir --}}
                        <span>{{ $respondidas ? $respondidas.' '.($respondidas === 1 ? 'respuesta del turno por leer' : 'respuestas del turno por leer') : $esperando.' '.($esperando === 1 ? 'pregunta esperando respuesta' : 'preguntas esperando respuesta') }}</span>
                        <span style="white-space:nowrap;flex-shrink:0">{{ $this->preguntasAction }}</span>
                    </li>
                @endif
            </ul>
        </x-filament::section>

    </div>
    <x-filament-actions::modals />
</x-filament-widgets::widget>
