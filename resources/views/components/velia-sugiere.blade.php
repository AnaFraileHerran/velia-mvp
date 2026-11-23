@props(['texto', 'porque' => null])
{{-- «Velia te sugiere»: la IA propone y la persona decide (aceptar, cambiar o descartar). Sin chispas ni degradados: un recuadro sobrio con borde de acción. --}}
<div {{ $attributes->merge(['class' => 'velia-sugiere']) }} role="note" aria-label="Sugerencia de Velia">
    <p class="velia-sugiere-cabecera">
        <x-filament::icon icon="heroicon-o-light-bulb" style="width:16px;height:16px" aria-hidden="true" />
        Velia te sugiere
        @if (\App\Support\Ia\Asistente::esSimulado())
            <span class="velia-sugiere-ejemplo" title="Sugerencia hecha con reglas sobre tus datos. Durante el piloto es un ejemplo.">Ejemplo</span>
        @endif
    </p>
    <p class="velia-sugiere-texto">{{ $texto }}</p>
    @if ($porque)
        <p class="velia-sugiere-porque">{{ $porque }}</p>
    @endif
    @if (trim($slot) !== '')
        <div class="velia-sugiere-acciones">{{ $slot }}</div>
    @endif
</div>
