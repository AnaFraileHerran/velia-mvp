<x-filament-widgets::widget>
    @php($semana = \App\Support\Semana::resumen())
    {{-- Cómo va la semana: solo tendencia de 7 días; cada cifra se puede abrir --}}
    <x-filament::section heading="Últimos 7 días" description="Cómo ha ido, frente a los 7 días anteriores.">
        <dl style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:24px">
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">Turnos cerrados completos</dt>
                <dd class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $semana['completos'] }} de {{ $semana['turnos'] }}</dd>
                @if ($semana['con_pendientes'])
                    <dd class="flex flex-wrap items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        <span>{{ $semana['con_pendientes'] }} con pendientes{{ $semana['motivo'] ? ' · sobre todo '.mb_strtolower($semana['motivo']) : '' }}</span>
                        {{ $this->verTurnosAction }}
                    </dd>
                @endif
            </div>
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">Incidencias</dt>
                <dd class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $semana['incidencias'] }}
                    <span class="text-sm font-normal text-gray-500">
                        @if ($semana['diferencia'] > 0) · {{ $semana['diferencia'] }} más que la semana anterior
                        @elseif ($semana['diferencia'] < 0) · {{ abs($semana['diferencia']) }} menos que la semana anterior
                        @else · igual que la semana anterior @endif
                    </span>
                </dd>
                <dd class="mt-1 flex flex-wrap items-center gap-2 text-sm">
                    {{-- Por tipo en texto (no píldoras: no se pulsan). El tipo que sube, en rojo con flecha. --}}
                    @foreach ($semana['tipos'] as $t)
                        <span class="{{ $t['n'] > $t['antes'] ? 'font-semibold text-danger-700' : 'text-gray-600 dark:text-gray-300' }}">{{ $t['n'] > $t['antes'] ? '↑ ' : '' }}{{ $t['n'] }} {{ mb_strtolower($t['tipo']) }}</span>@if (! $loop->last)<span class="text-gray-400" aria-hidden="true">·</span>@endif
                    @endforeach
                    @if ($semana['incidencias']){{ $this->verIncidenciasAction }}@endif
                </dd>
            </div>
        </dl>
    </x-filament::section>
    <x-filament-actions::modals />
</x-filament-widgets::widget>
