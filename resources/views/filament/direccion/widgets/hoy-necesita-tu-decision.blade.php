<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Hoy necesita tu decisión · {{ min($this->getTotal(), \App\Filament\Direccion\Widgets\HoyNecesitaTuDecision::MAXIMO) }}</x-slot>
        <x-slot name="description">Ordenado por urgencia. Cada acción quita el aviso de la lista.</x-slot>

        <ul class="divide-y divide-gray-200 dark:divide-white/10">
            @forelse ($this->getAvisos() as $aviso)
                <li class="flex flex-col gap-3 py-3 sm:flex-row sm:items-center">
                    <div class="flex-1">
                        <x-filament::badge :color="$aviso->urgencia === 1 ? 'danger' : 'warning'" class="mb-1 w-fit">
                            {{ \App\Models\Aviso::TIPOS[$aviso->tipo] ?? $aviso->tipo }}
                        </x-filament::badge>
                        <p class="text-sm font-semibold text-gray-950 dark:text-white">
                            {{ $aviso->titulo }}@if ($aviso->residente) · {{ $aviso->residente->nombre }}@endif
                        </p>
                        @if ($aviso->residente)
                            <p class="text-xs text-gray-500">{{ $aviso->residente->planta?->nombre }} · Hab. {{ $aviso->residente->habitacion }}</p>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($aviso->tipo === 'revision_vencida')
                            {{ ($this->asignarAction)(['aviso' => $aviso->id]) }}
                        @else
                            {{ ($this->revisadoAction)(['aviso' => $aviso->id]) }}
                            {{ ($this->preguntarAction)(['aviso' => $aviso->id]) }}
                        @endif
                        {{ ($this->cerrarAction)(['aviso' => $aviso->id]) }}
                    </div>
                </li>
            @empty
                <li class="py-6 text-center text-sm text-gray-500">Nada pendiente. Buen trabajo.</li>
            @endforelse
        </ul>

        @if (! $this->verTodos && $this->getTotal() > \App\Filament\Direccion\Widgets\HoyNecesitaTuDecision::MAXIMO)
            <div class="pt-3">
                <x-filament::link tag="button" wire:click="$set('verTodos', true)">
                    Ver {{ $this->getTotal() - \App\Filament\Direccion\Widgets\HoyNecesitaTuDecision::MAXIMO }} aviso(s) más
                </x-filament::link>
            </div>
        @endif
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
