{{-- Lista de preguntas al turno dentro del modal de «Esta semana». Respondidas arriba: son las que piden algo a la dirección. --}}
<ul class="divide-y divide-gray-200 dark:divide-white/10">
    @forelse ($preguntas as $p)
        <li class="flex flex-col gap-2 py-3">
            <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $p->texto }}</p>
            <p class="text-xs text-gray-500">{{ $p->residente?->nombre }}@if ($p->residente) · Hab. {{ $p->residente->habitacion }}@endif · preguntado {{ $p->created_at->locale('es')->diffForHumans() }}</p>
            @if ($p->respuesta)
                <div style="border-radius:10px;padding:10px 12px;background:rgb(240 243 250)">
                    <p class="text-sm">{{ $p->respuesta }}</p>
                    <p class="text-xs text-gray-500">{{ $p->autora?->name ?? 'El turno' }} · {{ $p->updated_at->format('H:i') }}</p>
                </div>
                <div><x-filament::button size="sm" icon="heroicon-o-check" wire:click="resolverPregunta({{ $p->id }})">Dar por resuelta</x-filament::button></div>
            @else
                <div class="flex flex-wrap items-center gap-3">
                    <x-filament::badge color="gray">Esperando respuesta del turno</x-filament::badge>
                    <x-filament::link tag="button" color="primary" wire:click="retirarPregunta({{ $p->id }})">Retirar pregunta</x-filament::link>
                </div>
            @endif
        </li>
    @empty
        <li class="py-6 text-center text-sm text-gray-500">No hay preguntas abiertas.</li>
    @endforelse
</ul>
