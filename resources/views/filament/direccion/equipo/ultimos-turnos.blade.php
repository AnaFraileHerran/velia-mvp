@php
    $turnos = $getRecord()->turnos()->whereNotNull('fin')->latest('inicio')->limit(5)->get();
    $nombre = ['manana' => 'mañana', 'tarde' => 'tarde', 'noche' => 'noche'];
@endphp
<ul class="divide-y divide-gray-100 text-sm dark:divide-white/10">
    @forelse ($turnos as $t)
        @php($h = (int) $t->inicio->format('G'))
        <li class="flex flex-wrap items-center justify-between gap-2 py-2">
            <span>{{ ucfirst($t->inicio->locale('es')->isoFormat('ddd D MMM')) }} · {{ $h < 15 && $h >= 7 ? 'mañana' : ($h >= 15 && $h < 23 ? 'tarde' : 'noche') }}{{ $t->planta ? ' · '.$t->planta->nombre : '' }}</span>
            @if ($t->motivo_cierre)
                <span class="text-gray-600 dark:text-gray-300">Cerrado con pendientes · {{ mb_strtolower(\App\Models\Turno::MOTIVOS_CIERRE[$t->motivo_cierre] ?? $t->motivo_cierre) }}</span>
            @else
                <span class="text-gray-500">Cerrado completo</span>
            @endif
        </li>
    @empty
        <li class="py-2 text-gray-500">Todavía no ha cerrado ningún turno.</li>
    @endforelse
</ul>
