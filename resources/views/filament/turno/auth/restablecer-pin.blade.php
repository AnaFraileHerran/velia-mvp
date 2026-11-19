{{-- A0 · Restablecer PIN (responsable de turno) --}}
<x-filament-panels::page.simple>
    <div class="space-y-6">
        @if (! $responsableId)
            <div class="text-center">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Responsable de turno</p>
                <h1 class="mt-2 text-xl font-semibold text-gray-950 dark:text-white">Escribe tu PIN para continuar</h1>
            </div>
            @include('filament.turno.partials.teclado', ['modelo' => 'pin'])
            <div class="text-center"><x-filament::link :href="filament()->getLoginUrl()" color="gray">Volver</x-filament::link></div>

        @elseif ($resultado)
            {{-- Lo más importante, grande: el PIN que hay que decirle --}}
            <div class="rounded-2xl bg-primary-50 p-6 text-center dark:bg-primary-400/10">
                <p class="text-sm font-medium">PIN provisional para {{ $para }}</p>
                <p class="mt-2 font-mono text-5xl font-bold tracking-[0.4em] text-gray-950 dark:text-white">{{ $resultado }}</p>
                <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">Díselo en persona. {{ $para }} creará su PIN nuevo al entrar.</p>
            </div>
            <p class="text-center text-xs text-gray-500">Queda registrado quién lo restableció y a qué hora. La dirección lo ve en Equipo.</p>
            <x-filament::button tag="a" :href="filament()->getUrl()" class="w-full" size="xl" color="gray">Hecho</x-filament::button>

        @else
            <div>
                <h1 class="text-xl font-semibold text-gray-950 dark:text-white">Restablecer el PIN de una compañera</h1>
                <p class="text-sm text-gray-500">Elige a quién. Le daremos un PIN provisional y creará el suyo al entrar.</p>
            </div>
            <ul class="space-y-2">
                @foreach ($this->companeras() as $c)
                    <li>
                        <button type="button" wire:click="restablecer({{ $c->id }})" wire:confirm="¿Dar un PIN provisional a {{ $c->name }}?"
                            class="flex w-full items-center justify-between rounded-xl border border-gray-200 px-4 py-3 text-start active:bg-gray-50 dark:border-white/10">
                            <span>
                                <span class="block font-semibold text-gray-950 dark:text-white">{{ $c->name }}</span>
                                <span class="block text-sm text-gray-500">{{ $c->planta?->nombre ?? 'Todas las plantas' }} · {{ $c->etiquetaTurno() }}</span>
                            </span>
                            <span aria-hidden="true">›</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($error)<p class="text-center text-sm font-medium text-danger-600" role="alert">{{ $error }}</p>@endif
    </div>
</x-filament-panels::page.simple>
