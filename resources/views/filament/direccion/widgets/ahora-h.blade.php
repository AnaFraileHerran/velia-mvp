<x-filament-widgets::widget>
    @php($inicio = $franja['inicio']->format('H:i'))
    {{-- Ahora (flujo nuevo): una fila de tarjetas pequeñas. Lo normal en gris; lo que falla, como pista ámbar dentro de su planta. --}}
    <section class="velia-ahora" aria-label="Ahora en el centro">
        <div class="velia-ahora-titulo">
            <p class="text-sm font-semibold text-gray-950">Ahora</p>
            <p class="text-xs text-gray-500">Turno de {{ $franja['nombre'] }}</p>
        </div>

        <div class="velia-ahora-rejilla">
            @foreach ($plantas as $p)
                <div @class(['velia-ahora-item', 'velia-ahora-item-aviso' => $p['aviso']])>
                    {{-- Se pulsa la planta y se abre lo que pasa ahora en ella (panel lateral) --}}
                    <button type="button" class="velia-ahora-planta" wire:click="mountAction('verPlanta', { planta: {{ $p['id'] }} })" aria-label="Ver {{ $p['planta'] }} ahora">
                        {{ $p['planta'] }}
                        <x-filament::icon icon="heroicon-m-chevron-right" class="h-3.5 w-3.5" aria-hidden="true" />
                    </button>
                    @if ($p['personas']->isNotEmpty())
                        <div class="flex items-center gap-1">
                            @foreach ($p['personas']->take(3) as $u)
                                <a href="{{ \App\Filament\Direccion\Resources\AuxiliarResource::getUrl('view', ['record' => $u]) }}" title="{{ $u->name }} · turno abierto" class="velia-ahora-avatar">
                                    <img src="{{ \App\Support\Avatar::url($u->foto, $u->name) }}" alt="{{ $u->name }}">
                                </a>
                            @endforeach
                            <span class="text-sm font-medium text-gray-950 truncate">{{ $p['personas']->map(fn ($u) => strtok($u->name, ' '))->join(', ') }}</span>
                        </div>
                    @elseif ($p['aviso'])
                        {{-- La pista: se pulsa y se ve quién debería estar y cómo localizarla --}}
                        <div class="flex flex-wrap items-center gap-x-2">
                            {{ ($this->quienFaltaAction)(['planta' => $p['id']]) }}
                            {{ ($this->noProcedeAction)(['aviso' => $p['aviso']->id]) }}
                        </div>
                    @else
                        <p class="text-sm text-gray-500">Sin turno abierto</p>
                    @endif
                    <button type="button" class="text-left text-xs text-gray-500 hover:text-gray-700" wire:click="mountAction('verPlanta', { planta: {{ $p['id'] }} })">
                        <span class="velia-ahora-barra" aria-hidden="true"><span style="width: {{ $p['total'] ? round($p['registrados'] / $p['total'] * 100) : 0 }}%"></span></span>
                        {{ $p['registrados'] }} de {{ $p['total'] }} registrados
                    </button>
                </div>
            @endforeach

            @foreach ($sanitarios as $sx)
                <div class="velia-ahora-item">
                    <p class="velia-ahora-etiqueta">{{ $sx['puesto'] }}</p>
                    <p class="text-sm {{ $sx['ahora'] ? 'font-medium text-gray-950' : 'text-gray-500' }} truncate">
                        @if ($sx['ficha'])<a class="hover:underline" href="{{ $sx['ficha'] }}">{{ $sx['texto'] }}</a>@else{{ $sx['texto'] }}@endif
                    </p>
                </div>
            @endforeach

            <div class="velia-ahora-item">
                <p class="velia-ahora-etiqueta"><a class="hover:underline" href="{{ \App\Filament\Direccion\Resources\AusenciaResource::getUrl() }}">Fuera · {{ $fuera->count() }}</a></p>
                @forelse ($fuera->take(2) as $a)
                    <p class="text-sm truncate">
                        <a class="font-medium hover:underline" href="{{ \App\Filament\Direccion\Resources\ResidenteResource::getUrl('view', ['record' => $a->residente_id]) }}">{{ $a->residente?->nombre }}</a>
                        <span class="text-gray-500">· {{ $a->vuelve ? ($a->vuelve->isToday() ? 'vuelve hoy' : 'vuelve el '.$a->vuelve->format('d/m')) : 'sin fecha' }}</span>
                    </p>
                @empty
                    <p class="text-sm text-gray-500">Nadie</p>
                @endforelse
                @if ($fuera->count() > 2)
                    <p class="text-xs text-gray-500">y {{ $fuera->count() - 2 }} más</p>
                @endif
            </div>
        </div>
    </section>
    <x-filament-actions::modals />
</x-filament-widgets::widget>
