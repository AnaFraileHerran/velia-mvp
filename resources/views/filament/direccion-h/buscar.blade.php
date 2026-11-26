{{-- Flujo nuevo (comentarios de Pablo y Antonio): un solo buscador en la cabecera, en lugar de «Ir a…» y «Buscar».
     Encuentra pantallas, residentes, personas y tareas, y si es una pregunta, Velia responde. ⌘K desde cualquier sitio. --}}
<button type="button" class="velia-h-buscar" x-data x-on:click="$dispatch('velia-ir-a')" aria-label="Buscar o preguntar (Comando K)">
    <x-filament::icon icon="heroicon-o-magnifying-glass" class="h-4 w-4 shrink-0" aria-hidden="true" />
    <span class="truncate">Buscar o preguntar a Velia</span>
    <kbd>⌘K</kbd>
</button>
