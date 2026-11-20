<x-filament-panels::page>

    {{-- 1. Lo que pide algo: apartados de la norma, los pendientes primero --}}
    <x-filament::section heading="Apartados de la norma y sus evidencias">
        <ul class="divide-y divide-gray-100 dark:divide-white/5">
            @foreach ($this->apartados() as $a)
                <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                    <div class="flex items-start gap-3">
                        <x-filament::icon :icon="$a['ok'] ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-circle'"
                            @class(['mt-0.5 h-5 w-5', 'text-success-600' => $a['ok'], 'text-warning-600' => ! $a['ok']]) />
                        <div>
                            <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $a['titulo'] }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $a['dato'] }}</p>
                        </div>
                    </div>
                    @if (! $a['ok'] && $a['accion'])
                        <x-filament::button tag="a" :href="$a['accion'][1]" size="sm">{{ $a['accion'][0] }}</x-filament::button>
                    @endif
                </li>
            @endforeach
        </ul>
    </x-filament::section>

    {{-- 3. Indicadores: «Ejemplo» hasta validar H2 (regla 10) --}}
    <x-filament::section heading="Indicadores del mes" description="Se calcularán con tus registros cuando confirmemos cómo se cuentan. Por ahora son un ejemplo." collapsible collapsed>
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
