{{--
    Barra inferior de 3 pestañas (Relevo · Residentes · Mi turno), fija abajo para el pulgar.
    Bloqueo: si nadie toca la pantalla en 5 minutos, se cierra la sesión (el turno sigue abierto).
--}}
@php
    $t = \App\Support\TurnoActual::make();
    $nRelevo = $t->novedades()->count() + $t->preguntas()->count();
    $nPend = $t->porRegistrar();
    $tabs = [
        [\App\Filament\Turno\Pages\Relevo::class, 'Relevo', 'heroicon-o-clipboard-document-list', $nRelevo],
        [\App\Filament\Turno\Pages\Residentes::class, 'Residentes', 'heroicon-o-users', $nPend],
        [\App\Filament\Turno\Pages\MiTurno::class, 'Mi turno', 'heroicon-o-user-circle', null],
    ];
@endphp
<nav class="fixed inset-x-0 bottom-0 z-30 border-t border-gray-200 bg-white pb-[env(safe-area-inset-bottom)] dark:border-white/10 dark:bg-gray-900" aria-label="Navegación del turno">
    <ul class="mx-auto grid max-w-3xl grid-cols-3">
        @foreach ($tabs as [$page, $label, $icon, $n])
            @php($activa = request()->routeIs($page::getRouteName()))
            <li>
                <a href="{{ $page::getUrl() }}" @if ($activa) aria-current="page" @endif
                   @class(['flex flex-col items-center gap-1 py-2 text-xs font-medium', 'text-primary-600' => $activa, 'text-gray-500' => ! $activa])>
                    <span class="relative">
                        <x-filament::icon :icon="$icon" class="h-6 w-6" />
                        @if ($n)<span class="absolute -end-3 -top-1 rounded-full bg-primary-600 px-1.5 text-[10px] text-white">{{ $n }}</span>@endif
                    </span>
                    {{ $label }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
<form id="velia-bloqueo" method="POST" action="{{ filament()->getLogoutUrl() }}" class="hidden">@csrf</form>
<script>
    (() => {
        let t;
        const reiniciar = () => { clearTimeout(t); t = setTimeout(() => document.getElementById('velia-bloqueo').submit(), 5 * 60 * 1000); };
        ['pointerdown', 'keydown', 'scroll'].forEach(e => window.addEventListener(e, reiniciar, { passive: true }));
        reiniciar();
    })();
</script>
