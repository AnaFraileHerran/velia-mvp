@props(['texto', 'porque' => null])
{{-- Flujo nuevo (25/11/2026, comentario de Pablo): «Velia sugiere» en una sola línea. Es opcional: no debe ocupar más que el aviso.
     El porqué se abre solo si se pide. Sigue siendo «la IA propone y la persona decide»: aceptar, cambiar o descartar. --}}
<div {{ $attributes->merge(['class' => 'velia-sugiere-linea']) }} x-data="{ abierto: false }" role="note" aria-label="Sugerencia de Velia">
    <div class="velia-sugiere-linea-fila">
        <x-filament::icon icon="heroicon-o-light-bulb" class="h-4 w-4 shrink-0" aria-hidden="true" />
        <p class="min-w-0 flex-1 truncate" :class="abierto && 'velia-sin-truncar'"><span class="font-semibold">Velia sugiere:</span> {{ $texto }}</p>
        @if ($porque)
            <button type="button" class="velia-sugiere-linea-porque" x-on:click="abierto = ! abierto" x-text="abierto ? 'Menos' : '¿Por qué?'"></button>
        @endif
        {{ $slot }}
    </div>
    @if ($porque)
        <p x-show="abierto" x-cloak class="velia-sugiere-linea-detalle">
            {{ $porque }}
            @if (\App\Support\Ia\Asistente::esSimulado())<span class="velia-sugiere-ejemplo">Ejemplo</span>@endif
        </p>
    @endif
</div>
