<ul class="divide-y divide-gray-200 text-sm dark:divide-white/10">
    @forelse ($turnos as $t)
        @php($h = (int) $t->inicio->format('G'))
        <li class="flex flex-wrap items-center justify-between gap-2 py-3">
            <span><strong>{{ $t->planta?->nombre ?? 'Todas las plantas' }} · {{ $h >= 7 && $h < 15 ? 'mañana' : ($h >= 15 && $h < 23 ? 'tarde' : 'noche') }} del {{ $t->inicio->format('d/m') }}</strong> · {{ mb_strtolower(\App\Models\Turno::MOTIVOS_CIERRE[$t->motivo_cierre] ?? $t->motivo_cierre) }}</span>
            <a class="text-primary-600 hover:underline" href="{{ \App\Filament\Direccion\Resources\AuxiliarResource::getUrl('view', ['record' => $t->user_id]) }}">{{ $t->user?->name }}</a>
        </li>
    @empty
        <li class="py-6 text-center text-gray-500">Todos los turnos se cerraron completos.</li>
    @endforelse
</ul>
