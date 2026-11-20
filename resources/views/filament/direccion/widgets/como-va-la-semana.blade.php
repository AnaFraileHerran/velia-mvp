<x-filament-widgets::widget>
    @php($semana = \App\Support\Semana::resumen())
    {{-- Cómo va la semana: tendencia, bajo lo urgente de hoy --}}
    <x-filament::section heading="Cómo va la semana">
        <dl class="grid gap-4 sm:grid-cols-3">
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">Residentes con registro hoy</dt>
                <dd class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $semana['cobertura'] }} %</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">Turnos cerrados con pendientes</dt>
                <dd class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $semana['con_pendientes'] }} de {{ $semana['turnos'] }}</dd>
                @if ($semana['motivo'])<dd class="text-xs text-gray-500">Motivo más frecuente: {{ mb_strtolower($semana['motivo']) }}</dd>@endif
            </div>
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">Incidencias</dt>
                <dd class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $semana['incidencias'] }}</dd>
                <dd class="text-xs text-gray-500">
                    @if ($semana['diferencia'] > 0) {{ $semana['diferencia'] }} más que la semana anterior
                    @elseif ($semana['diferencia'] < 0) {{ abs($semana['diferencia']) }} menos que la semana anterior
                    @else Igual que la semana anterior @endif
                </dd>
            </div>
        </dl>
    </x-filament::section>
</x-filament-widgets::widget>
