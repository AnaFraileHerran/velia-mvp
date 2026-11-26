{{--
    Calidad y auditoría · Nueva versión (26/11/2026). Comentarios de Pablo acordados con Ana:
    1) Velia: sugerencias en chips que abren el asistente con esa pregunta + un campo para preguntar sobre tu auditoría (v1.5).
    2) Una sola lista por apartado de la norma: estado + lo que falta + el botón para resolverlo. Acordeón: abiertos solo los que tienen pendientes.
    3) Indicadores: los mismos (nombre y cifra) que en el Inicio, aquí con el detalle. «Ejemplo» lo que aún no se calcula (regla 11).
--}}
<x-filament-panels::page>
    @php
        $ayuda = \App\Filament\Direccion\Pages\Ayuda::getUrl();
        $ind = \App\Support\Indicadores::lista();
        $p = $this->pendientes();
    @endphp

    {{-- 1. Velia: por dónde empezar + preguntas sugeridas + preguntar --}}
    <x-filament::section compact class="velia-calidad-velia">
        <div class="space-y-3">
            @if ($sug = $this->sugerencia())
                <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                    <x-filament::icon icon="heroicon-o-light-bulb" class="h-5 w-5 text-primary-600" />
                    <p class="text-sm"><span class="font-semibold">Velia te sugiere empezar por:</span> {{ $sug['texto'] }}</p>
                    <x-filament::button size="xs" wire:click="seguirSugerencia">{{ $sug['accion'] }}</x-filament::button>
                    <x-filament::link tag="button" color="gray" size="sm" wire:click="descartarSugerencia">Ahora no</x-filament::link>
                </div>
            @endif
            <form action="{{ $ayuda }}" method="get" class="flex flex-wrap items-center gap-2">
                @foreach ($this->preguntasSugeridas() as $pregunta)
                    <a href="{{ $ayuda }}?pregunta={{ urlencode($pregunta) }}" class="velia-chip">{{ $pregunta }}</a>
                @endforeach
                <x-filament::input.wrapper class="min-w-64 flex-1" prefix-icon="heroicon-m-sparkles">
                    <x-filament::input type="text" name="pregunta" placeholder="Pregunta sobre tu auditoría" aria-label="Pregunta sobre tu auditoría" />
                </x-filament::input.wrapper>
                <x-filament::button type="submit" size="sm" color="gray">Preguntar</x-filament::button>
            </form>
            <p class="text-xs text-gray-500">Velia responde con lo que pide la norma. No envía datos de residentes.</p>
        </div>
    </x-filament::section>

    {{-- 2. Una sola lista: apartados de la norma con su estado, lo que falta y la acción --}}
    <x-filament::section heading="Apartados de la auditoría"
        :description="$p['total'] ? $p['total'].' '.($p['total'] === 1 ? 'cosa pendiente' : 'cosas pendientes').' con lo que mide Velia. Abiertos, los apartados con algo que resolver.' : 'Con lo que mide Velia, no falta nada. Abre cada apartado para ver la evidencia.'">
        <div class="velia-apartados">
            @foreach ($this->apartadosConPendientes() as $a)
                @php
                    [$est, $n] = $a['estado'];
                    $txt = ['al_dia' => 'Al día', 'falta' => 'Falta '.$n, 'no_mide' => 'Velia no lo mide todavía', 'otro' => 'Lo lleva otro'][$est];
                    $ico = ['al_dia' => 'heroicon-o-check-circle', 'falta' => 'heroicon-o-exclamation-circle', 'no_mide' => 'heroicon-o-minus-circle', 'otro' => 'heroicon-o-arrow-top-right-on-square'][$est];
                @endphp
                <details class="velia-apartado velia-apartado-{{ $est }}" @if ($est === 'falta') open @endif>
                    <summary>
                        <span class="min-w-0 flex-1">
                            <span class="velia-apartado-titulo">{{ $a['titulo'] }}</span>
                            <span class="velia-apartado-pide">{{ $a['pide'] }}</span>
                        </span>
                        <span class="velia-apartado-estado"><x-filament::icon :icon="$ico" class="h-4 w-4" /> {{ $txt }}</span>
                        <x-filament::icon icon="heroicon-m-chevron-down" class="velia-apartado-flecha h-5 w-5" />
                    </summary>
                    <div class="velia-apartado-cuerpo">
                        <p class="text-sm text-gray-600"><span class="font-medium text-gray-800">Evidencia en Velia:</span> {{ $a['dato'] }}</p>
                        @foreach ($a['pendientes'] as $i)
                            <div class="velia-apartado-falta">
                                <p class="text-sm"><span class="font-semibold">{{ $i['n'] }}</span> {{ $i['texto'] }}</p>
                                <x-filament::button tag="a" :href="$i['url']" size="sm">{{ $i['accion'] }}</x-filament::button>
                            </div>
                        @endforeach
                        <a href="{{ $ayuda }}?pregunta={{ urlencode('¿Qué pide la norma en '.mb_strtolower($a['titulo']).'?') }}" class="text-sm font-medium text-primary-700 hover:underline">Preguntar a Velia qué pide la norma aquí →</a>
                    </div>
                </details>
            @endforeach
        </div>
    </x-filament::section>

    {{-- 3. Indicadores: los mismos que en el Inicio, con el detalle --}}
    <x-filament::section heading="Indicadores de calidad" description="Los mismos datos que ves en el Inicio, con cómo se calculan. «Ejemplo»: aún no se calcula con tus registros.">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($ind as $i)
                <div class="velia-indicador {{ $i['estado'] !== 'real' ? 'velia-indicador-ejemplo' : '' }}">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-medium text-gray-700">{{ $i['nombre'] }}</p>
                        @if ($i['estado'] === 'ejemplo')<span class="velia-sugiere-ejemplo">Ejemplo</span>@elseif ($i['estado'] === 'no_mide')<x-filament::badge color="gray" size="sm">Aún no se mide</x-filament::badge>@endif
                    </div>
                    <p class="velia-kpi-cifra {{ $i['estado'] !== 'real' ? 'text-gray-400' : '' }}">{{ $i['cifra'] }}@if ($i['de'])<span class="velia-kpi-de">{{ $i['de'] }}</span>@endif</p>
                    @if (! is_null($i['pct']))<span class="velia-kpi-barra" aria-hidden="true"><span style="width: {{ $i['pct'] }}%"></span></span>@endif
                    <p class="text-xs text-gray-500">{{ $i['nota'] }}</p>
                    <p class="mt-2 text-xs text-gray-600">{{ $i['detalle'] }}</p>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
