<x-filament-panels::page>
    {{-- Asistente IA de Ayuda (23/11/2026): responde y lleva al sitio. Voz: dictar la pregunta y escuchar la respuesta. --}}
    <x-filament::section icon="heroicon-o-chat-bubble-left-right">
        <x-slot name="heading">
            Pregúntale a Velia
            @if (\App\Support\Ia\Asistente::esSimulado())
                <span class="velia-sugiere-ejemplo" title="Durante el piloto, las respuestas están preparadas con la ayuda de Velia.">Ejemplo</span>
            @endif
        </x-slot>
        <x-slot name="description">Escribe o dicta tu duda. Velia te responde en pocas palabras y te lleva a donde se resuelve.</x-slot>

        <div x-data="{ leerSiempre: false }" x-on:velia-respuesta.window="leerSiempre && veliaLeer($event.detail.texto)" class="space-y-4">
            @if ($conversacion)
                {{-- Nueva versión (comentario de Pablo en AYU-02): la conversación vive en un contenedor con altura máxima y se queda
                     anclada al último mensaje, para no empujar el resto de la página; burbujas de ~65 caracteres de ancho. --}}
                <div class="velia-chat-hilo flex flex-col gap-3" aria-live="polite" x-data x-init="$el.scrollTop = $el.scrollHeight" x-effect="$el.scrollTop = $el.scrollHeight">
                    @foreach ($conversacion as $m)
                        @if ($m['de'] === 'yo')
                            <p class="velia-chat-burbuja velia-chat-yo">{{ $m['texto'] }}</p>
                        @else
                            <div class="velia-chat-burbuja velia-chat-velia">
                                <p>{{ $m['texto'] }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    @foreach ($m['enlaces'] as $e)
                                        <x-filament::button tag="a" :href="$e['url']" size="sm" icon="heroicon-o-arrow-right" icon-position="after">{{ $e['titulo'] }}</x-filament::button>
                                    @endforeach
                                    <x-filament::link tag="button" size="sm" color="gray" icon="heroicon-o-speaker-wave" data-texto="{{ $m['texto'] }}" x-on:click="veliaLeer($el.dataset.texto)">Escuchar</x-filament::link>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
            {{-- Sugerencias encima del campo, también con conversación (antes desaparecían al preguntar) --}}
            @if (! $conversacion || \App\Support\VersionH::activa())
                <div class="flex flex-wrap gap-2">
                    @foreach ($this->ejemplos() as $ej)
                        @if (\App\Support\VersionH::activa())
                            <button type="button" class="velia-chip" wire:click="preguntarEjemplo({{ $loop->index }})">{{ $ej }}</button>
                        @else
                            <x-filament::button size="sm" color="gray" wire:click="preguntarEjemplo({{ $loop->index }})">{{ $ej }}</x-filament::button>
                        @endif
                    @endforeach
                </div>
            @endif

            <form wire:submit="preguntar" x-data="veliaDictado()" class="space-y-2">
                <div class="flex items-end gap-2">
                    <x-filament::input.wrapper class="flex-1">
                        <textarea x-ref="destino" wire:model="pregunta" rows="{{ \App\Support\VersionH::activa() ? 1 : 2 }}" class="velia-chat-campo block w-full resize-none border-0 bg-transparent p-3 text-base focus:ring-0"
                            x-on:input="if (@js(\App\Support\VersionH::activa())) { $el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 5 * 24 + 24) + 'px' }"
                            placeholder="Por ejemplo: ¿qué tengo que revisar hoy?" aria-label="Tu pregunta"
                            x-on:keydown.enter.prevent="$wire.set('pregunta', $refs.destino.value).then(() => $wire.preguntar())"></textarea>
                    </x-filament::input.wrapper>
                    <template x-if="soportado">
                        <x-filament::button type="button" size="lg" color="gray" icon="heroicon-o-microphone" x-on:click="alternar()" x-bind:aria-pressed="grabando" ::class="grabando && 'animate-pulse'">
                            <span x-text="grabando ? 'Parar' : 'Dictar'">Dictar</span>
                        </x-filament::button>
                    </template>
                    <x-filament::button type="submit" size="lg" x-on:click="grabando && parar()">Preguntar</x-filament::button>
                </div>
                <p x-show="error" x-text="error" class="text-sm text-danger-700"></p>
                <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
                    <label class="flex items-center gap-2"><input type="checkbox" x-model="leerSiempre" class="rounded"> Leer las respuestas en voz alta</label>
                    <span>Al dictar, el navegador envía tu voz a Google o Apple para pasarla a texto. No dictes datos de residentes.</span>
                </div>
            </form>
        </div>
    </x-filament::section>

    <p class="text-sm font-semibold text-gray-700">Dudas frecuentes</p>
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
