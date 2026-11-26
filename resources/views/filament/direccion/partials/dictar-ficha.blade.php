{{-- Nuevo residente · «Cuéntaselo a Velia»: dicta o escribe lo que sabes y Velia rellena la ficha. Tú lo revisas antes de guardar. --}}
<div x-data="veliaDictado()" class="space-y-3">
    <p class="text-sm text-gray-600">Por ejemplo: «Es alérgica a la penicilina. Camina con andador. Dieta blanda sin sal. No usa sujeción. Le gusta que la llamen Carmencita.»</p>
    <x-filament::input.wrapper>
        <textarea x-ref="destino" rows="4" class="block w-full border-0 bg-transparent p-3 text-base focus:ring-0" placeholder="Escribe o dicta lo que sabes de la persona…" aria-label="Lo que sabes de la persona"></textarea>
    </x-filament::input.wrapper>
    <p x-show="error" x-text="error" class="text-sm text-danger-700"></p>
    <div class="flex flex-wrap items-center gap-3">
        <template x-if="soportado">
            <x-filament::button type="button" color="gray" icon="heroicon-o-microphone" x-on:click="alternar()">
                <span x-text="grabando ? 'Parar' : 'Dictar'">Dictar</span>
            </x-filament::button>
        </template>
        <x-filament::button type="button" icon="heroicon-o-light-bulb" x-on:click="$wire.rellenarConVelia($refs.destino.value)">Rellenar la ficha</x-filament::button>
        <span class="text-xs text-gray-500">Revisa cada campo antes de guardar. Al dictar, el navegador envía tu voz a Google o Apple para pasarla a texto.</span>
    </div>
</div>
