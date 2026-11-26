{{-- Paso 2 del alta: qué ha rellenado Velia, para revisarlo campo a campo (regla 10: la IA propone y la persona confirma). --}}
@php($p = $getLivewire()->propuestaVelia ?? [])
@if ($p)
    <div class="velia-rellenado">
        <x-filament::icon icon="heroicon-o-sparkles" class="h-5 w-5 shrink-0" />
        <p class="text-sm"><span class="font-semibold">Velia ha rellenado {{ count($p) }} {{ count($p) === 1 ? 'campo' : 'campos' }}:</span>
            {{ collect(array_keys($p))->map(fn ($k) => ['alergias' => 'alergias', 'riesgos' => 'riesgos', 'movilidad' => 'movilidad', 'sujecion' => 'sujeción', 'dieta' => 'dieta', 'como_tratarle' => 'cómo tratarle', 'notas' => 'notas', 'habitacion' => 'habitación'][$k] ?? $k)->join(', ') }}.
            Revísalos y completa lo que falte.</p>
    </div>
@else
    <p class="text-sm text-gray-500">Rellena la ficha a mano o vuelve al paso anterior para contárselo a Velia.</p>
@endif
