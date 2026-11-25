{{-- Libro de ayuda por sección (patrón de Holded): qué es esta pantalla, dos consejos y la pregunta lista para Velia. --}}
<x-filament::dropdown placement="bottom-end" width="sm" teleport>
    <x-slot name="trigger">
        <x-filament::icon-button icon="heroicon-o-book-open" color="gray" size="lg" label="Ayuda de esta sección" tooltip="Ayuda de esta sección" />
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
