<x-filament-panels::page>
    @php($t = $this->tarea())
    {{-- La tarea de la semana, arriba y en una línea: revisar y enviar los resúmenes (antes era la pantalla entera) --}}
    <div class="velia-tarea">
        <span class="velia-tarea-icono"><x-filament::icon icon="heroicon-o-envelope" class="h-5 w-5" /></span>
        <div class="min-w-0 flex-1">
            <p class="font-semibold text-gray-950">Resúmenes de la semana</p>
            <p class="text-sm text-gray-600">
                @if ($t['pendientes'])
                    {{ $t['pendientes'] }} por revisar
                    @if ($t['incidencias'])
                        · {{ $t['incidencias'] }} con incidencias: revísalos uno a uno
                    @endif
                    · {{ $t['enviados'] }} enviados esta semana
                @else
                    Todos enviados esta semana ({{ $t['enviados'] }}).
                @endif
            </p>
        </div>
        <x-filament::button tag="a" :href="$t['url']" :color="$t['pendientes'] ? 'primary' : 'gray'">{{ $t['pendientes'] ? 'Revisar y enviar' : 'Ver enviados' }}</x-filament::button>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
