<x-filament-widgets::widget class="velia-alto">
    <x-filament::section>
        <x-slot name="heading">Hoy necesita tu decisión · {{ min($this->getTotal(), \App\Filament\Direccion\Widgets\HoyNecesitaTuDecision::MAXIMO) }}</x-slot>
        <x-slot name="description">Ordenado por urgencia. Lo que decidas queda anotado con tu nombre y la fecha.</x-slot>

        <ul class="divide-y divide-gray-200 dark:divide-white/10">
            @forelse ($this->getAvisos() as $aviso)
                <li class="velia-aviso py-3">
                    <div class="flex-1">
                        {{-- Tipo como etiqueta de texto: la forma de píldora queda solo para lo que se pulsa --}}
                        <p class="mb-1 flex items-center gap-1 text-xs font-semibold {{ $aviso->urgencia <= 1 ? 'text-danger-700' : 'text-warning-700' }}">
                            <span style="width:8px;height:8px;border-radius:9999px;background:currentColor;display:inline-block" aria-hidden="true"></span>
                            {{ \App\Models\Aviso::TIPOS[$aviso->tipo] ?? $aviso->tipo }}
                        </p>
                        @if ($aviso->vencido())
                            <p class="text-xs font-semibold text-danger-700">Vencido el {{ $aviso->vence_el->format('d/m') }} sin hacer · asignado a {{ $aviso->responsable?->name }}</p>
                        @endif
                        <p class="text-sm font-semibold text-gray-950 dark:text-white">
                            {{ $aviso->titulo }}@if ($aviso->residente) · {{ $aviso->residente->nombre }}@endif
                        </p>
                        @if ($aviso->residente)
                            <p class="text-xs text-gray-500">{{ $aviso->residente->planta?->nombre }} · Hab. {{ $aviso->residente->habitacion }}</p>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($aviso->tipo === 'turno_sin_abrir')
                            {{ ($this->quienFaltaAction)(['planta' => (int) $aviso->detalle]) }}
                        @elseif ($aviso->tipo === 'revision_vencida')
                            {{ ($this->asignarAction)(['aviso' => $aviso->id]) }}
                        @else
                            {{ ($this->revisadoAction)(['aviso' => $aviso->id]) }}
                            {{ ($this->preguntarAction)(['aviso' => $aviso->id]) }}
                        @endif
                        {{ ($this->noProcedeAction)(['aviso' => $aviso->id]) }}
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
