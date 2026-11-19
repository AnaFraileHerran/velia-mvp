<x-filament-panels::page>
    {{-- Lo que falta antes de cerrar, en grande --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ([[$registrados.' de '.$total, 'registrados', 'gray'], [$borradores, 'borrador por confirmar', $borradores ? 'warning' : 'gray'], [$pendientes, 'pendiente', $pendientes ? 'warning' : 'gray'], [$fuera, 'fuera', 'gray']] as [$v, $l, $c])
            <a href="{{ \App\Filament\Turno\Pages\Residentes::getUrl() }}" @class(['rounded-xl border bg-white p-4 dark:bg-gray-900', 'border-warning-400' => $c === 'warning', 'border-gray-200 dark:border-white/10' => $c !== 'warning'])>
                <p class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $v }}</p>
                <p class="text-sm text-gray-500">{{ $l }}</p>
            </a>
        @endforeach
    </div>

    <x-filament::button tag="a" :href="\App\Filament\Turno\Pages\CerrarTurno::getUrl()" size="xl" class="w-full">Cerrar el turno</x-filament::button>

    @if (auth()->user()->responsable_turno)
        <x-filament::section heading="Como responsable de turno" icon="heroicon-o-star">
            <p class="text-sm text-gray-600 dark:text-gray-300">Restablece el PIN de una compañera. Si el micrófono de la tablet no funciona, revisa el permiso en los ajustes del navegador; mientras, se puede escribir.</p>
            <x-filament::button tag="a" :href="route('filament.turno.restablecer-pin')" color="gray" class="mt-3" icon="heroicon-o-key">Restablecer un PIN</x-filament::button>
        </x-filament::section>
    @endif

    <div class="space-y-2 text-center">
        <x-filament::button color="gray" wire:click="cambiarDePersona" icon="heroicon-o-arrows-right-left">Cambiar de persona</x-filament::button>
        <p class="text-xs text-gray-500">Cierra tu sesión en la tablet sin cerrar el turno. Si nadie la toca en 5 minutos, se bloquea sola.</p>
    </div>
</x-filament-panels::page>
