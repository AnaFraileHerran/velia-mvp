{{-- Ayuda por sección (patrón de Holded, con el icono de Ayuda del menú en vez del libro): qué es esta pantalla, dos consejos y la pregunta lista para Velia. --}}
<x-filament::dropdown placement="bottom-end" width="sm" teleport>
    <x-slot name="trigger">
        <x-filament::button icon="heroicon-o-question-mark-circle" color="gray" outlined size="sm" tooltip="Qué es esta pantalla y cómo usarla">Ayuda</x-filament::button>
    </x-slot>

    <div class="velia-h-libro">
        <p class="velia-h-libro-titulo">{{ $ayuda['titulo'] }}</p>
        <p>{{ $ayuda['texto'] }}</p>
        <ul>
            @foreach ($ayuda['consejos'] as $c)
                <li>{{ $c }}</li>
            @endforeach
        </ul>
        <a href="{{ \App\Filament\Direccion\Pages\Ayuda::getUrl(['pregunta' => $ayuda['pregunta']]) }}" class="velia-h-libro-velia">
            <x-filament::icon icon="heroicon-o-sparkles" class="h-4 w-4" aria-hidden="true" />
            Pregúntale a Velia: «{{ $ayuda['pregunta'] }}»
        </a>
    </div>
</x-filament::dropdown>
