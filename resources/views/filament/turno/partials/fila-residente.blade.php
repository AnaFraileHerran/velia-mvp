{{-- Fila de residente: toda la fila lleva a registrar (acción principal); «Sin novedad» a un toque si está por registrar; «i» abre cómo atender. --}}
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
    @if (($accionRapida ?? false) && $estado !== 'borrador' && $estado !== 'fuera')
        <span class="flex items-center border-s border-gray-100 dark:border-white/5" style="padding:0 8px">
            <button type="button" wire:click="sinNovedad({{ $r->id }})" wire:loading.attr="disabled"
                class="velia-sin-novedad"
                style="display:inline-flex;align-items:center;gap:6px;min-height:44px;padding:0 14px;border-radius:9999px;border:1px solid rgb(207 207 201);background:#fff;font-size:14px;font-weight:600;color:rgb(58 58 54);white-space:nowrap"
                aria-label="Sin novedad para {{ $r->nombre }}">
                <x-filament::icon icon="heroicon-o-check" style="width:16px;height:16px" />
                <span>Sin novedad</span>
            </button>
        </span>
    @endif
    <button type="button" wire:click="mountAction('comoAtender', { residente: {{ $r->id }} })"
        class="border-s border-gray-100 px-4 text-gray-500 dark:border-white/5" aria-label="Cómo atender a {{ $r->nombre }}">
        <x-filament::icon icon="heroicon-o-information-circle" class="h-6 w-6" />
    </button>
</li>
