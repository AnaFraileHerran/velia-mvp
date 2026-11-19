<x-filament-panels::page>
    @if ($paso === 'grabar')
        {{-- Grabar: un botón enorme. Si el micrófono falla, pasa a escribir sin perder nada. --}}
        <div x-data="dictado()" class="flex flex-col items-center gap-6 py-6 text-center">
            <button type="button" x-on:click="alternar()"
                :class="grabando ? 'bg-danger-600 animate-pulse' : 'bg-primary-600'"
                class="flex h-36 w-36 items-center justify-center rounded-full text-white shadow-lg"
                :aria-label="grabando ? 'Parar y revisar' : 'Grabar'">
                <x-filament::icon icon="heroicon-s-microphone" class="h-14 w-14" />
            </button>
            <p class="text-lg font-semibold" x-text="grabando ? 'Grabando · ' + reloj + ' · toca para terminar' : 'Toca para grabar'"></p>
            <p class="max-w-sm text-sm text-gray-600 dark:text-gray-300">Habla con naturalidad. La IA ordena lo que digas y tú lo confirmas.</p>
            <p x-show="parcial" x-text="parcial" class="max-w-md text-sm italic text-gray-500"></p>
        </div>
        <div class="flex flex-col items-center gap-3">
            <x-filament::button color="gray" icon="heroicon-o-pencil" wire:click="$set('paso', 'escribir')">Escribir</x-filament::button>
            <x-filament::link tag="button" wire:click="sinNovedad">¿Turno tranquilo? Marca «Sin novedad»</x-filament::link>
        </div>
        <script>
            function dictado() {
                const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
                return {
                    grabando: false, parcial: '', finalTxt: '', inicio: 0, reloj: '00:00', rec: null, t: null,
                    alternar() { this.grabando ? this.parar() : this.empezar(); },
                    empezar() {
                        if (!SR) { this.$wire.set('paso', 'escribir'); return; }
                        this.rec = new SR(); this.rec.lang = 'es-ES'; this.rec.continuous = true; this.rec.interimResults = true;
                        this.rec.onresult = (e) => {
                            let p = '';
                            for (let i = e.resultIndex; i < e.results.length; i++) {
                                if (e.results[i].isFinal) this.finalTxt += e.results[i][0].transcript + '. ';
                                else p += e.results[i][0].transcript;
                            }
                            this.parcial = this.finalTxt + p;
                        };
                        this.rec.onerror = () => { this.grabando = false; clearInterval(this.t); this.$wire.set('paso', 'escribir'); };
                        this.rec.start(); this.grabando = true; this.inicio = Date.now();
                        this.t = setInterval(() => { const s = Math.floor((Date.now() - this.inicio) / 1000); this.reloj = String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0'); }, 500);
                    },
                    parar() {
                        this.rec && this.rec.stop(); this.grabando = false; clearInterval(this.t);
                        setTimeout(() => { this.$wire.set('texto', (this.finalTxt || this.parcial).trim()); this.$wire.ordenar(); }, 400);
                    },
                };
            }
        </script>

    @elseif ($paso === 'escribir')
        <div class="rounded-xl bg-warning-50 p-4 text-sm dark:bg-warning-400/10">
            <p class="font-semibold">Escribe el registro</p>
            <p>Si el micrófono no funciona o no hay cobertura, escríbelo aquí. La IA lo ordena igual.</p>
        </div>
        <form wire:submit="ordenar" class="space-y-3">
            <x-filament::input.wrapper>
                <textarea wire:model="texto" rows="6" class="block w-full border-0 bg-transparent p-3 text-base focus:ring-0" placeholder="Escribe con naturalidad…" autofocus></textarea>
            </x-filament::input.wrapper>
            <p class="text-xs text-gray-500">Puedes mencionar: alimentación e hidratación · higiene · movilidad y sujeción · descanso · ánimo · incidencias</p>
            <div class="flex flex-wrap gap-3">
                <x-filament::button type="submit" size="lg">Ordenar y revisar</x-filament::button>
                <x-filament::button color="gray" size="lg" wire:click="$set('paso', 'grabar')" icon="heroicon-o-microphone">Grabar</x-filament::button>
            </div>
        </form>
        <x-filament::link tag="button" wire:click="sinNovedad">¿Turno tranquilo? Marca «Sin novedad»</x-filament::link>

    @else
        {{-- Revisar: lo que la IA ha entendido, por categorías. Incidencia arriba si la hay. --}}
        <p class="text-sm text-gray-500">Generado por IA · revisa antes de confirmar.
            <x-filament::link tag="button" x-on:click="$refs.trans.classList.toggle('hidden')" size="sm">Ver transcripción</x-filament::link></p>
        <p x-ref="trans" class="hidden rounded-lg bg-gray-50 p-3 text-sm italic dark:bg-white/5">{{ $texto }}</p>

        <label @class(['flex items-start gap-3 rounded-xl border-2 p-4', 'border-danger-500 bg-danger-50 dark:bg-danger-400/10' => $incidencia, 'border-gray-200 dark:border-white/10' => ! $incidencia])>
            <input type="checkbox" wire:model.live="incidencia" class="mt-1 h-5 w-5 rounded">
            <span class="flex-1">
                <span class="block font-semibold">Incidencia destacada</span>
                <span class="block text-sm text-gray-600 dark:text-gray-300">Avisa a la dirección y queda como evidencia.</span>
                @if ($incidencia)
                    <select wire:model="tipoIncidencia" class="mt-2 w-full rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-white/5">
                        @foreach (\App\Filament\Turno\Pages\Registrar::TIPOS_INCIDENCIA as $tipo)<option value="{{ $tipo }}">{{ $tipo }}</option>@endforeach
                    </select>
                @endif
            </span>
        </label>

        <div class="space-y-3">
            @foreach ($categorias as $cat => $txt)
                <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-white/10 dark:bg-gray-900" x-data="{ editar: false }">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold">{{ $cat }}</p>
                        <div class="flex gap-3 text-sm">
                            <x-filament::link tag="button" x-on:click="editar = !editar" x-text="editar ? 'Listo' : 'Editar'">Editar</x-filament::link>
                            <x-filament::link tag="button" color="gray" wire:click="quitarCategoria('{{ $cat }}')">Quitar</x-filament::link>
                        </div>
                    </div>
                    <p x-show="!editar" class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $txt }}</p>
                    <textarea x-show="editar" wire:model="categorias.{{ $cat }}" rows="2" class="mt-2 w-full rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-white/5"></textarea>
                </div>
            @endforeach
        </div>

        <p class="text-xs text-gray-500">Nada se guarda como definitivo hasta que confirmes.</p>
        <div class="flex flex-col gap-3 sm:flex-row">
            <x-filament::button size="xl" wire:click="confirmar" class="sm:flex-1">Confirmar</x-filament::button>
            <x-filament::button size="xl" color="gray" wire:click="borrador">Guardar borrador</x-filament::button>
            <x-filament::button size="xl" color="gray" wire:click="$set('paso', 'grabar')" icon="heroicon-o-microphone">Volver a grabar</x-filament::button>
        </div>
    @endif
</x-filament-panels::page>
