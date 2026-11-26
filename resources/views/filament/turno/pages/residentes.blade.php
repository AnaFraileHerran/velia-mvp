<x-filament-panels::page>
    <div class="flex flex-wrap items-center gap-3">
        @unless ($esMovil)
            <x-filament::tabs>
                <x-filament::tabs.item :active="$vista === 'lista'" wire:click="$set('vista', 'lista')">Lista</x-filament::tabs.item>
                <x-filament::tabs.item :active="$vista === 'plano'" wire:click="$set('vista', 'plano')">Plano de planta</x-filament::tabs.item>
            </x-filament::tabs>
        @endunless
        <x-filament::input.wrapper class="min-w-0 flex-1" prefix-icon="heroicon-o-magnifying-glass">
            <x-filament::input type="search" wire:model.live.debounce.250ms="buscar" placeholder="Buscar por nombre o habitación" />
        </x-filament::input.wrapper>
    </div>

    @if ($vista === 'plano')
        {{-- Plano: una tarjeta por habitación. Borde grueso = incidencia · borde oscuro = pendiente --}}
        @php
            $todos = $g['por_registrar']->concat($g['registrados'])->concat($g['fuera']);
            $porHab = $todos->groupBy('habitacion')->sortKeys();
        @endphp
        <div class="grid grid-cols-3 gap-3 lg:grid-cols-5">
            @foreach ($porHab as $hab => $rs)
                @php
                    $estados = $rs->map(fn ($r) => $t->estado($r));
                    $incidencia = $rs->contains(fn ($r) => $r->registros->where('incidencia_destacada', true)->where('created_at', '>=', now()->subHours(12))->isNotEmpty());
                    $pendiente = $estados->contains(fn ($e) => in_array($e, ['pendiente', 'borrador']));
                    $fuera = $estados->filter(fn ($e) => $e === 'fuera')->count();
                @endphp
                <a href="{{ $rs->count() === 1 ? \App\Filament\Turno\Pages\Registrar::getUrl(['residente' => $rs->first()->id]) : '#' }}"
                   @if ($rs->count() > 1) wire:click.prevent="$set('vista', 'lista'); $set('buscar', '{{ $hab }}')" @endif
                   @class(['rounded-xl bg-white p-3 dark:bg-gray-900',
                           'border-4 border-danger-500' => $incidencia,
                           'border-2 border-gray-900 dark:border-white' => ! $incidencia && $pendiente,
                           'border border-gray-200 dark:border-white/10' => ! $incidencia && ! $pendiente])>
                    <p class="font-semibold">Hab. {{ $hab }}</p>
                    <p class="text-sm text-gray-500">
                        {{ $rs->count() }} ·
                        {{ $incidencia ? 'Incidencia' : ($pendiente ? 'Pendiente' : ($fuera ? $fuera.' fuera' : 'Al día')) }}
                    </p>
                </a>
            @endforeach
        </div>
        <p class="text-xs text-gray-500">Borde rojo grueso: incidencia · borde oscuro: pendiente de registrar.</p>
    @else
        <section>
            <h2 class="mb-2 text-sm font-semibold">Por registrar · {{ $g['por_registrar']->count() }}</h2>
            <ul class="divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-200 bg-white dark:divide-white/5 dark:border-white/10 dark:bg-gray-900">
                @forelse ($g['por_registrar'] as $r)
                    @include('filament.turno.partials.fila-residente', ['r' => $r, 'estado' => $t->estado($r), 'accionRapida' => true])
                @empty
                    <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                        <span class="text-sm font-medium text-success-700">Todo registrado en este turno.</span>
                        <x-filament::button tag="a" :href="\App\Filament\Turno\Pages\CerrarTurno::getUrl()" icon="heroicon-o-lock-closed">Cerrar el turno</x-filament::button>
                    </li>
                @endforelse
            </ul>
        </section>
        @foreach (['registrados' => 'Registrados', 'fuera' => 'Fuera'] as $k => $titulo)
            <details class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900" @if ($buscar !== '') open @endif>
                <summary class="cursor-pointer px-4 py-3 text-sm font-semibold">{{ $titulo }} · {{ $g[$k]->count() }}</summary>
                <ul class="divide-y divide-gray-100 border-t border-gray-100 dark:divide-white/5 dark:border-white/5">
                    @foreach ($g[$k] as $r)
                        @include('filament.turno.partials.fila-residente', ['r' => $r, 'estado' => $t->estado($r)])
                    @endforeach
                </ul>
            </details>
        @endforeach
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
