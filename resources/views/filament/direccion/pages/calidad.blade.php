<x-filament-panels::page>
    @php($p = $this->pendientes())

    @if ($sug = $this->sugerencia())
        <x-velia-sugiere :texto="$sug['texto']" :porque="$sug['porque']" style="margin-top:0">
            <x-filament::button size="sm" wire:click="seguirSugerencia">{{ $sug['accion'] }}</x-filament::button>
            <x-filament::link tag="button" color="gray" size="sm" wire:click="descartarSugerencia">Ahora no</x-filament::link>
        </x-velia-sugiere>
    @endif

    {{-- 1. Lo que falta: la lista de tareas de la norma, cada una con su acción. Los mismos pendientes que el Inicio. --}}
    <x-filament::section heading="Lo que falta para la auditoría" :description="$p['total'] ? 'Lo que Velia sabe que falta. Cada línea lleva a donde se resuelve.' : 'Con lo que mide Velia, no falta nada.'">
        @if ($p['items'])
            <ul class="divide-y divide-gray-100 dark:divide-white/5">
                @foreach ($p['items'] as $i)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <p class="text-sm"><span class="font-semibold text-gray-950 dark:text-white">{{ $i['n'] }}</span> {{ $i['texto'] }}</p>
                        <x-filament::button tag="a" :href="$i['url']" color="gray" size="sm">{{ $i['accion'] }}</x-filament::button>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="flex items-center gap-2 text-sm text-gray-700"><x-filament::icon icon="heroicon-o-check-circle" style="width:20px;height:20px;color:#15803d" /> Todo al día.</p>
        @endif
    </x-filament::section>

    {{-- 2. Apartados que comprueba la certificadora, con estado en texto (no solo color) y quién lo lleva si no es Velia --}}
    <x-filament::section heading="Apartados de la auditoría" description="Los que comprueba la certificadora (Anexo I del esquema ENAC). Incluye lo que no lleva Velia, para que nada te pille por sorpresa.">
        <ul class="divide-y divide-gray-100 dark:divide-white/5">
            @foreach ($this->apartados() as $a)
                @php([$est, $n] = $a['estado'])
                <li class="grid gap-2 py-3" style="grid-template-columns:minmax(0,1fr) auto;align-items:start">
                    <div>
                        <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $a['titulo'] }} <span class="font-normal text-gray-500">· {{ $a['pide'] }}</span></p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ $a['dato'] }}</p>
                    </div>
                    <p class="flex items-center gap-1 text-sm font-medium" style="white-space:nowrap;color:{{ ['al_dia' => '#15803d', 'falta' => 'rgb(185 28 28)', 'no_mide' => 'rgb(94 101 114)', 'otro' => 'rgb(94 101 114)'][$est] }}">
                        <x-filament::icon :icon="['al_dia' => 'heroicon-o-check-circle', 'falta' => 'heroicon-o-exclamation-circle', 'no_mide' => 'heroicon-o-minus-circle', 'otro' => 'heroicon-o-arrow-top-right-on-square'][$est]" style="width:18px;height:18px" />
                        {{ ['al_dia' => 'Al día', 'falta' => 'Falta '.$n, 'no_mide' => 'Velia no lo mide todavía', 'otro' => 'Lo lleva otro'][$est] }}
                    </p>
                </li>
            @endforeach
        </ul>
    </x-filament::section>

    {{-- 3. Indicadores: «Ejemplo» hasta validar H2 con el texto de la norma --}}
    <x-filament::section heading="Indicadores del mes" description="Se calcularán con tus registros cuando confirmemos con la norma cómo se cuentan. Por ahora son un ejemplo." collapsible collapsed>
        <div class="grid gap-4 sm:grid-cols-4">
            @foreach ([['—', 'Caídas por 1.000 estancias'], ['15 de 16', 'Planes personales revisados a tiempo'], ['2', 'Incidencias de piel'], ['—', 'Quejas resueltas']] as [$v, $l])
                <div class="rounded-lg border border-dashed border-gray-300 p-3 dark:border-white/10">
                    <x-filament::badge color="gray" size="sm">Ejemplo</x-filament::badge>
                    <p class="mt-2 text-xl font-semibold text-gray-400">{{ $v }}</p>
                    <p class="text-xs text-gray-500">{{ $l }}</p>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
