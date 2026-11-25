@if (\App\Support\VersionH::activa())
{{-- Nueva versión (25/11/2026): una línea por aviso para que el Inicio quepa sin scroll a 1440 × 900.
     «Velia sugiere» queda detrás de la bombilla: está si la quieres, no ocupa si no (comentario de Pablo). --}}
<x-filament-widgets::widget class="velia-alto">
    <x-filament::section compact>
        <x-slot name="heading">Hoy necesita tu decisión · {{ min($this->getTotal(), \App\Filament\Direccion\Widgets\HoyNecesitaTuDecision::MAXIMO) }}</x-slot>
        <x-slot name="afterHeader"><span class="text-xs text-gray-500">Por urgencia · queda anotado con tu nombre</span></x-slot>

        <ul class="velia-avisos-h">
            @forelse ($this->getAvisos() as $aviso)
                @php($s = $this->sugerencia($aviso))
                <li x-data="{ velia: false }">
                    <div class="velia-aviso-h">
                        <span class="velia-punto {{ $aviso->urgencia <= 1 ? 'velia-nivel-alta' : 'velia-nivel-media' }}" aria-hidden="true"></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-gray-950" title="{{ $aviso->titulo }}{{ $aviso->residente ? ' · '.$aviso->residente->nombre : '' }}">
                                {{ $aviso->titulo }}@if ($aviso->residente) · <a href="{{ \App\Filament\Direccion\Resources\ResidenteResource::getUrl('view', ['record' => $aviso->residente]) }}" class="underline decoration-gray-300 underline-offset-2 hover:text-primary-700">{{ $aviso->residente->nombre }}</a>@endif
                            </p>
                            <p class="truncate text-xs text-gray-500">
                                <span class="{{ $aviso->urgencia <= 1 ? 'text-danger-700' : 'text-warning-700' }} font-semibold">{{ \App\Models\Aviso::TIPOS[$aviso->tipo] ?? $aviso->tipo }}</span>
                                @if ($aviso->vencido()) · <span class="font-semibold text-danger-700">vencido el {{ $aviso->vence_el->format('d/m') }} · {{ $aviso->responsable?->name }}</span>@endif
                                @if ($aviso->residente) · {{ $aviso->residente->planta?->nombre }} · Hab. {{ $aviso->residente->habitacion }}@endif
                            </p>
                        </div>
                        <div class="velia-aviso-h-acciones">
                            @if ($s)
                                <button type="button" class="velia-bombilla" x-on:click="velia = ! velia" :aria-expanded="velia" aria-label="Ver lo que sugiere Velia" title="Velia sugiere">
                                    <x-filament::icon icon="heroicon-o-light-bulb" class="h-4 w-4" aria-hidden="true" />
                                </button>
                            @endif
                            @if ($aviso->tipo === 'turno_sin_abrir')
                                {{ ($this->quienFaltaAction)(['planta' => (int) $aviso->detalle]) }}
                            @elseif ($aviso->tipo === 'revision_vencida')
                                {{ ($this->asignarAction)(['aviso' => $aviso->id]) }}
                            @elseif (in_array($aviso->tipo, ['equipo', 'familia', 'organizacion']))
                                {{ ($this->revisadoAction)(['aviso' => $aviso->id]) }}
                                {{ ($this->asignarAction)(['aviso' => $aviso->id]) }}
                            @else
                                {{ ($this->revisadoAction)(['aviso' => $aviso->id]) }}
                                {{ ($this->preguntarAction)(['aviso' => $aviso->id]) }}
                            @endif
                            {{ ($this->noProcedeAction)(['aviso' => $aviso->id]) }}
                        </div>
                    </div>
                    @if ($s)
                        <div x-show="velia" x-cloak>
                            <x-velia-sugiere-linea :texto="$s['texto']" :porque="$s['porque']">
                                <x-filament::link tag="button" size="sm" wire:click="aceptarSugerencia({{ $aviso->id }})">Aceptar</x-filament::link>
                                <x-filament::link tag="button" color="gray" size="sm" wire:click="descartarSugerencia({{ $aviso->id }})" aria-label="Descartar la sugerencia">No</x-filament::link>
                            </x-velia-sugiere-linea>
                        </div>
                    @endif
                </li>
            @empty
                <li class="py-6 text-center text-sm text-gray-500">Nada pendiente. Buen trabajo.</li>
            @endforelse
        </ul>

        @if (! $this->verTodos && $this->getTotal() > \App\Filament\Direccion\Widgets\HoyNecesitaTuDecision::MAXIMO)
            <div class="pt-2">
                <x-filament::link tag="button" size="sm" wire:click="$set('verTodos', true)">Ver {{ $this->getTotal() - \App\Filament\Direccion\Widgets\HoyNecesitaTuDecision::MAXIMO }} aviso(s) más</x-filament::link>
            </div>
        @endif
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
@else
<x-filament-widgets::widget class="velia-alto">
    <x-filament::section>
        <x-slot name="heading">Hoy necesita tu decisión · {{ min($this->getTotal(), \App\Filament\Direccion\Widgets\HoyNecesitaTuDecision::MAXIMO) }}</x-slot>
        <x-slot name="description">Ordenado por urgencia. Lo que decidas queda anotado con tu nombre y la fecha.</x-slot>

        <ul class="divide-y divide-gray-200 dark:divide-white/10">
            @forelse ($this->getAvisos() as $aviso)
                <li class="py-3">
                    <div class="velia-aviso">
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
                            {{ $aviso->titulo }}@if ($aviso->residente) · <a href="{{ \App\Filament\Direccion\Resources\ResidenteResource::getUrl('view', ['record' => $aviso->residente]) }}" class="underline decoration-gray-300 underline-offset-2 hover:text-primary-700" title="Abrir su ficha">{{ $aviso->residente->nombre }}</a>@endif
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
                        @elseif (in_array($aviso->tipo, ['equipo', 'familia', 'organizacion']))
                            {{-- Decisiones de dirección: se encargan a alguien con plazo o se dan por revisadas --}}
                            {{ ($this->revisadoAction)(['aviso' => $aviso->id]) }}
                            {{ ($this->asignarAction)(['aviso' => $aviso->id]) }}
                        @else
                            {{ ($this->revisadoAction)(['aviso' => $aviso->id]) }}
                            {{ ($this->preguntarAction)(['aviso' => $aviso->id]) }}
                        @endif
                        {{ ($this->noProcedeAction)(['aviso' => $aviso->id]) }}
                    </div>
                    </div>
                    @if (($s = $this->sugerencia($aviso)) && \App\Support\VersionH::activa())
                        <x-velia-sugiere-linea :texto="$s['texto']" :porque="$s['porque']">
                            <x-filament::link tag="button" size="sm" wire:click="aceptarSugerencia({{ $aviso->id }})">Aceptar</x-filament::link>
                            <x-filament::link tag="button" color="gray" size="sm" wire:click="descartarSugerencia({{ $aviso->id }})" aria-label="Descartar la sugerencia">No</x-filament::link>
                        </x-velia-sugiere-linea>
                    @elseif ($s)
                        <x-velia-sugiere :texto="$s['texto']" :porque="$s['porque']">
                            <x-filament::button size="sm" wire:click="aceptarSugerencia({{ $aviso->id }})" icon="heroicon-o-check">Aceptar</x-filament::button>
                            <span class="text-xs text-gray-500">o cámbialo con «{{ $s['tipo'] === 'asignar' ? 'Asignar' : 'Preguntar al turno' }}»</span>
                            <x-filament::link tag="button" color="gray" size="sm" wire:click="descartarSugerencia({{ $aviso->id }})">No, gracias</x-filament::link>
                        </x-velia-sugiere>
                    @endif
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
@endif
