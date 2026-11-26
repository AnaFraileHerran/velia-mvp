{{-- Ayuda de la pantalla (patrón de Holded): un «?» pegado al título, con el mismo icono que «Ayuda» del menú.
     Qué es esta pantalla, dos consejos y la pregunta lista para Velia. --}}
<div class="velia-ayuda-titulo">
<x-filament::dropdown placement="bottom-start" width="sm" teleport>
    <x-slot name="trigger">
        <x-filament::icon-button icon="heroicon-o-question-mark-circle" color="gray" size="lg" label="Ayuda de esta pantalla" tooltip="¿Qué es esta pantalla?" />
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
</div>
