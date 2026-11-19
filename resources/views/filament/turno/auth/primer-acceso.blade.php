{{-- A00 · Primer acceso --}}
<x-filament-panels::page.simple>
    <div class="space-y-6">
        @if ($paso === 1)
            <div class="text-center">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $residencia->nombre }}</p>
                <h1 class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">Tu primer día en Velia</h1>
            </div>

            @if ($esMovil)
                {{-- Móvil personal: código, nunca la lista de compañeras --}}
                <p class="text-center text-sm text-gray-600 dark:text-gray-300">Escribe el código que te ha dado la dirección o la responsable de turno. Así este móvil queda vinculado a ti.</p>
                <form wire:submit="vincularConCodigo" class="space-y-4">
                    <x-filament::input.wrapper>
                        <x-filament::input wire:model="codigo" maxlength="6" autocapitalize="characters" autocomplete="one-time-code"
                            class="text-center text-2xl tracking-[0.5em] uppercase" placeholder="ABC123" aria-label="Código de 6 caracteres" />
                    </x-filament::input.wrapper>
                    <x-filament::button type="submit" class="w-full" size="xl">Continuar</x-filament::button>
                </form>
            @elseif (! $elegida)
                <p class="text-center text-sm text-gray-600 dark:text-gray-300">Elige tu nombre y escribe el PIN provisional que te ha dado la dirección o la responsable de turno.</p>
                <ul class="space-y-2">
                    @forelse ($this->pendientes() as $p)
                        <li>
                            <button type="button" wire:click="$set('elegida', {{ $p->id }})"
                                class="flex w-full items-center justify-between rounded-xl border border-gray-200 px-4 py-3 text-start active:bg-gray-50 dark:border-white/10">
                                <span>
                                    <span class="block font-semibold text-gray-950 dark:text-white">{{ $p->name }}</span>
                                    <span class="block text-sm text-gray-500">{{ $p->planta?->nombre ?? 'Todas las plantas' }} · {{ $p->etiquetaTurno() }}</span>
                                </span>
                                <span aria-hidden="true">›</span>
                            </button>
                        </li>
                    @empty
                        <li class="text-center text-sm text-gray-500">No hay nadie pendiente de primer acceso. Pide a la dirección que te dé de alta en Equipo.</li>
                    @endforelse
                </ul>
            @else
                <p class="text-center text-sm font-medium">{{ \App\Models\User::find($elegida)?->name }} · PIN provisional</p>
                @include('filament.turno.partials.teclado', ['modelo' => 'pin'])
                <div class="text-center"><x-filament::link tag="button" wire:click="$set('elegida', null)" color="gray">No soy yo</x-filament::link></div>
            @endif

        @elseif ($paso === 2)
            <div class="text-center">
                <p class="text-sm text-gray-500">Hola, {{ strtok($user->name, ' ') }} · {{ $user->planta?->nombre ?? 'Todas las plantas' }} · {{ $user->etiquetaTurno() }}</p>
                <h1 class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $confirmando ? 'Repite tu PIN' : 'Crea tu PIN personal' }}</h1>
                <p class="text-sm text-gray-500">4 cifras que solo sepas tú.</p>
            </div>
            @include('filament.turno.partials.teclado', ['modelo' => $confirmando ? 'repetido' : 'nuevo'])

        @else
            {{-- Paso 3 · micrófono: comprobar no bloquea --}}
            <div x-data="pruebaMicro()" class="space-y-4 text-center">
                <h1 class="text-2xl font-semibold text-gray-950 dark:text-white">Probamos el micrófono</h1>
                <p class="text-sm text-gray-600 dark:text-gray-300">Di «hola» en voz alta. Así sabemos que podrás grabar los registros.</p>
                <x-filament::button size="xl" icon="heroicon-o-microphone" x-on:click="probar()" x-show="estado === 'inicio'">Probar</x-filament::button>
                <p x-show="estado === 'escuchando'" class="text-sm">Escuchando…</p>
                <p x-show="estado === 'ok'" class="font-semibold text-success-600">✓ Micrófono listo</p>
                <div x-show="estado === 'error'" class="rounded-xl bg-warning-50 p-4 text-start text-sm dark:bg-warning-400/10">
                    <p class="font-semibold">No podemos usar el micrófono</p>
                    <p>Pide a la responsable de turno que lo active. Mientras, puedes escribir los registros.</p>
                </div>
                <x-filament::button size="xl" class="w-full" wire:click="terminar" x-show="estado === 'ok' || estado === 'error'">Empezar mi turno</x-filament::button>
            </div>
            <script>
                function pruebaMicro() {
                    return {
                        estado: 'inicio',
                        async probar() {
                            this.estado = 'escuchando';
                            try {
                                const s = await navigator.mediaDevices.getUserMedia({ audio: true });
                                setTimeout(() => { s.getTracks().forEach(t => t.stop()); this.estado = 'ok'; }, 1200);
                            } catch (e) { this.estado = 'error'; }
                        },
                    };
                }
            </script>
        @endif

        @if ($error)<p class="text-center text-sm font-medium text-danger-600" role="alert">{{ $error }}</p>@endif
    </div>
</x-filament-panels::page.simple>
