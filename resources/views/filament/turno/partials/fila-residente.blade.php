{{-- Fila de residente: toda la fila lleva a registrar (acción principal); «i» abre cómo atender. --}}
<li class="flex items-stretch">
    <a href="{{ \App\Filament\Turno\Pages\Registrar::getUrl(['residente' => $r->id]) }}"
       class="flex min-w-0 flex-1 items-center justify-between gap-3 px-4 py-3 active:bg-gray-50 dark:active:bg-white/5">
        <span class="flex min-w-0 items-center gap-3">
            <img src="{{ \App\Support\Avatar::url($r->foto, $r->nombre) }}" alt="" style="width:44px;height:44px;border-radius:9999px;object-fit:cover;flex-shrink:0">
            <span class="min-w-0">
            <span class="block font-semibold text-gray-950 dark:text-white">{{ $r->nombre }} · Hab. {{ $r->habitacion }}</span>
            <span @class(['block text-sm', 'text-warning-700 font-medium' => $estado === 'borrador', 'text-gray-500' => $estado !== 'borrador'])>
                {{ $estado === 'fuera' ? $r->etiquetaEstado() : \App\Filament\Turno\Pages\Residentes::lineaEstado($r, $estado) }}
            </span>
            </span>
        </span>
        <span aria-hidden="true" class="text-gray-400">›</span>
    </a>
    <button type="button" wire:click="mountAction('comoAtender', { residente: {{ $r->id }} })"
        class="border-s border-gray-100 px-4 text-gray-500 dark:border-white/5" aria-label="Cómo atender a {{ $r->nombre }}">
        <x-filament::icon icon="heroicon-o-information-circle" class="h-6 w-6" />
    </button>
</li>
