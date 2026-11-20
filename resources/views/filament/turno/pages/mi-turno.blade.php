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

    @if (\Filament\Facades\Filament::auth()->user()->responsable_turno)
        <x-filament::section heading="Como responsable de turno" icon="heroicon-o-star">
            <p class="text-sm text-gray-600 dark:text-gray-300">Restablece el PIN de una compañera. Si el micrófono de la tablet no funciona, revisa el permiso en los ajustes del navegador; mientras, se puede escribir.</p>
            <x-filament::button tag="a" :href="route('filament.turno.restablecer-pin')" color="gray" class="mt-3" icon="heroicon-o-key">Restablecer un PIN</x-filament::button>
        </x-filament::section>
    @endif

    {{-- Quién más está hoy: compañeras del turno y equipo sanitario, para saber a quién acudir --}}
    <x-filament::section heading="Hoy contigo" icon="heroicon-o-user-group">
        <div class="space-y-5">
            <div>
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                    {{ $companeras->count() === 1 ? '1 auxiliar más' : $companeras->count().' auxiliares más' }} · turno de {{ mb_strtolower(\Filament\Facades\Filament::auth()->user()->etiquetaTurno()) }}
                </p>
                @forelse ($companeras as $c)
                    <div class="flex items-center justify-between gap-3 border-t border-gray-100 py-2.5 first:border-t-0 dark:border-white/10">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-gray-950 dark:text-white">{{ $c['user']->name }}</p>
                            <p class="text-sm text-gray-500">{{ $c['user']->planta?->nombre ?? 'Todas las plantas' }}@if ($c['user']->responsable_turno) · Responsable de turno @endif</p>
                        </div>
                        @if ($c['enTurno'])
                            <x-filament::badge color="success">En turno</x-filament::badge>
                        @else
                            <x-filament::badge color="gray">Aún no ha entrado</x-filament::badge>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Hoy no hay más auxiliares en tu turno y planta.</p>
                @endforelse
            </div>

            @if (count($equipo))
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Equipo sanitario y de apoyo</p>
                    @foreach ($equipo as $p)
                        <div class="flex items-center justify-between gap-3 border-t border-gray-100 py-2.5 first:border-t-0 dark:border-white/10">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-gray-950 dark:text-white">{{ $p['nombre'] }} <span class="font-normal text-gray-500">· {{ $p['puesto'] }}</span></p>
                                <p @class(['text-sm', 'font-medium text-success-700' => $p['estado'] === 'aqui', 'text-gray-500' => $p['estado'] !== 'aqui'])>{{ $p['texto'] }}</p>
                            </div>
                            @if ($p['contacto'] && $p['estado'] === 'aqui')
                                <span class="shrink-0 rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700 dark:bg-white/10 dark:text-gray-200">{{ $p['contacto'] }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            <p class="text-xs text-gray-500">Si es urgente y no hay nadie de enfermería o medicina en la residencia, llama al 112.</p>
        </div>
    </x-filament::section>

    <div class="space-y-2 text-center">
        <x-filament::button color="gray" wire:click="cambiarDePersona" icon="heroicon-o-arrows-right-left">Cambiar de persona</x-filament::button>
        <p class="text-xs text-gray-500">Cierra tu sesión en la tablet sin cerrar el turno. Si nadie la toca en 5 minutos, se bloquea sola.</p>
    </div>
</x-filament-panels::page>
