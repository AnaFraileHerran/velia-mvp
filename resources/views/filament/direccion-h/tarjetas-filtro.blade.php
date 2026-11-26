{{--
    Tarjetas de cifras que filtran la tabla (Nueva versión, 26/11/2026, comentario de Pablo en RES-01).
    Sustituyen a las pestañas y al subtítulo: cada cifra se dice una sola vez y al pulsarla filtra la lista.
    Cambian la propiedad «activeTab» de la página (la misma que usaban las pestañas), así que el filtro y la URL (?tab=) siguen igual.
--}}
<div class="velia-tarjetas-filtro" role="tablist" aria-label="Filtrar la lista">
    @foreach ($tarjetas as $clave => $t)
        <button type="button" role="tab"
            wire:click="$set('activeTab', @js($clave))"
            x-bind:aria-selected="($wire.activeTab ?? 'todos') === @js($clave)"
            x-bind:class="{ 'is-activa': ($wire.activeTab ?? 'todos') === @js($clave) }"
            class="velia-tarjeta-filtro velia-tarjeta-{{ $t['tono'] }}">
            <span class="velia-tarjeta-filtro-icono"><x-filament::icon :icon="$t['icono']" class="h-5 w-5" /></span>
            <span class="min-w-0">
                <span class="velia-tarjeta-filtro-etiqueta">{{ $t['etiqueta'] }}</span>
                <span class="velia-tarjeta-filtro-cifra">{{ $t['cifra'] }}@if (! empty($t['de']))<span class="velia-tarjeta-filtro-de">{{ $t['de'] }}</span>@endif</span>
                <span class="velia-tarjeta-filtro-nota">{{ $t['nota'] }}</span>
            </span>
        </button>
    @endforeach
</div>
@if (! empty($secundario))
    <p class="velia-tarjetas-filtro-secundario">
        <button type="button" wire:click="$set('activeTab', @js($secundario[0]))" x-bind:class="{ 'is-activa': $wire.activeTab === @js($secundario[0]) }">{{ $secundario[1] }}</button>
    </p>
@endif
