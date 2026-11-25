<x-filament-widgets::widget class="velia-alto">
    <x-filament::section>
        <x-slot name="heading">Próximamente</x-slot>
        <x-slot name="description">Lo que vence hasta el {{ $hasta->locale('es')->isoFormat('D [de] MMMM') }}, por importancia.</x-slot>
        <x-slot name="afterHeader">
            {{-- Horizonte: la dirección planifica por semana, mes o trimestre --}}
            <div class="velia-segmentado" role="group" aria-label="Horizonte">
                @foreach (\App\Filament\Direccion\Widgets\ProximamenteH::HORIZONTES as $clave => [$etiqueta])
                    <button type="button" wire:click="verHorizonte('{{ $clave }}')" aria-pressed="{{ $horizonte === $clave ? 'true' : 'false' }}">{{ $etiqueta }}</button>
                @endforeach
            </div>
        </x-slot>

        @if ($hito)
            {{-- Hito fijo: la auditoría, con su nivel de importancia --}}
            <a href="{{ $hito['url'] }}" class="velia-hito velia-nivel-{{ $hito['nivel'] }}">
                <x-filament::icon icon="heroicon-o-shield-check" class="h-5 w-5 shrink-0" aria-hidden="true" />
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-gray-950">{{ $hito['titulo'] }}</span>
                    <span class="block text-xs text-gray-600">{{ $hito['detalle'] }}</span>
                </span>
                <span class="velia-nivel">{{ ucfirst($hito['nivel']) }}</span>
            </a>
        @endif

        @if ($hayPreguntas)
            <p class="velia-prox-preguntas">
                <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="h-4 w-4 shrink-0 text-gray-500" aria-hidden="true" />
                {{ $this->preguntasAction }}
            </p>
        @endif

        <ul class="velia-prox">
            @forelse ($items as $i)
                <li>
                    <span class="velia-punto velia-nivel-{{ $i['nivel'] }}" title="Importancia {{ $i['nivel'] }}" aria-label="Importancia {{ $i['nivel'] }}"></span>
                    <a href="{{ $i['url'] }}" class="min-w-0 flex-1 text-sm text-gray-950 hover:underline">{{ $i['titulo'] }}</a>
                    <span class="shrink-0 text-xs text-gray-500">{{ $i['fecha'] ? ($i['fecha']->isToday() ? 'hoy' : $i['fecha']->locale('es')->isoFormat('D MMM')) : 'sin fecha' }}</span>
                </li>
            @empty
                <li class="text-sm text-gray-500">Nada más en este horizonte.</li>
            @endforelse
        </ul>
    </x-filament::section>
    <x-filament-actions::modals />
</x-filament-widgets::widget>
