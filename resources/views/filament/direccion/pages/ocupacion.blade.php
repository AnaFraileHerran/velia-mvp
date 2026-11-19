<x-filament-panels::page>
    @php($d = $this->datos())
    <div class="grid gap-4 sm:grid-cols-3">
        <x-filament::section>
            <p class="text-3xl font-semibold text-gray-950 dark:text-white">{{ $d['ocupadas'] }}@if ($d['plazas']) <span class="text-lg text-gray-400">de {{ $d['plazas'] }}</span>@endif</p>
            <p class="text-sm text-gray-500">plazas ocupadas</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-3xl font-semibold text-gray-950 dark:text-white">{{ $d['dobles'] }} · {{ $d['individuales'] }}</p>
            <p class="text-sm text-gray-500">residentes en doble · en individual</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-3xl font-semibold text-gray-950 dark:text-white">{{ $d['ingresos'] }} · {{ $d['bajas'] }}</p>
            <p class="text-sm text-gray-500">ingresos · bajas este mes</p>
        </x-filament::section>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-filament::section heading="Incidencias de la semana">
            <ul class="divide-y divide-gray-100 text-sm dark:divide-white/5">
                @forelse ($d['incidencias'] as $i)
                    <li class="flex justify-between gap-3 py-2"><span class="font-medium">{{ $i['tipo'] }} · {{ $i['n'] }}</span><span class="text-gray-500">{{ $i['quien'] }}</span></li>
                @empty
                    <li class="py-2 text-gray-500">Ninguna esta semana.</li>
                @endforelse
            </ul>
        </x-filament::section>
        <x-filament::section heading="Notas importantes">
            <ul class="list-disc space-y-1 ps-5 text-sm">
                @forelse ($d['notas'] as $n)<li>{{ $n }}</li>@empty<li class="list-none ps-0 text-gray-500">Nada destacable.</li>@endforelse
            </ul>
        </x-filament::section>
    </div>
</x-filament-panels::page>
