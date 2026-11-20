<x-filament-panels::page>
    @foreach ($this->temas() as $grupo => $preguntas)
        <x-filament::section :heading="$grupo">
            <div class="divide-y divide-gray-100 dark:divide-white/10">
                @foreach ($preguntas as [$pregunta, $respuesta, $url, $enlace])
                    <details class="group py-3">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 font-medium text-gray-950 dark:text-white">
                            {{ $pregunta }}
                            <x-filament::icon icon="heroicon-o-chevron-down" class="h-5 w-5 shrink-0 text-gray-400 transition group-open:rotate-180" />
                        </summary>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $respuesta }}</p>
                        @if ($url)
                            <x-filament::link :href="$url" class="mt-2" size="sm">{{ $enlace }} →</x-filament::link>
                        @endif
                    </details>
                @endforeach
            </div>
        </x-filament::section>
    @endforeach

    <x-filament::section>
        <p class="text-sm text-gray-600 dark:text-gray-300">¿No encuentras lo que buscas? Durante el piloto, tu contacto en Icarus te responde.</p>
    </x-filament::section>
</x-filament-panels::page>
