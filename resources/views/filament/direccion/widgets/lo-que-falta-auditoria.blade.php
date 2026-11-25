<x-filament-widgets::widget>
    <x-filament::section
        heading="Lo que falta para la auditoría"
        :description="$fecha ? 'Próxima auditoría: '.$fecha.'. Solo lo que Velia puede medir con tus registros.' : 'Aún no has fijado la fecha de la próxima auditoría.'"
    >
        <x-slot name="afterHeader">
            <x-filament::link :href="$calidad" icon="heroicon-o-shield-check">Ver Calidad y auditoría</x-filament::link>
        </x-slot>

        @if ($items)
            <ul class="velia-h-lista">
                @foreach ($items as $i)
                    <li>
                        <span><span class="font-semibold text-gray-950">{{ $i['n'] }}</span> {{ $i['texto'] }}</span>
                        <x-filament::link :href="$i['url']" style="white-space:nowrap;flex-shrink:0">{{ $i['accion'] }}</x-filament::link>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-gray-600">Todo al día con lo que mide Velia.</p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
