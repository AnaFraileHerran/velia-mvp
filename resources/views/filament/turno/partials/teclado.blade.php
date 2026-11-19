{{--
    Teclado de PIN. Uso: @include('filament.turno.partials.teclado', ['modelo' => 'pin'])
    Escribe en la propiedad Livewire indicada; al llegar a 4 cifras el componente decide.
--}}
<div x-data="{ v: $wire.entangle('{{ $modelo }}').live }" class="mx-auto w-full max-w-xs">
    <div class="mb-5 flex justify-center gap-3" aria-live="polite" aria-label="Cifras escritas">
        @for ($i = 0; $i < 4; $i++)
            <span class="h-4 w-4 rounded-full border-2 border-gray-400"
                  :class="(v || '').length > {{ $i }} ? 'bg-gray-900 border-gray-900 dark:bg-white dark:border-white' : ''"></span>
        @endfor
    </div>
    <div class="grid grid-cols-3 gap-3">
        @foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9] as $n)
            <button type="button" class="velia-tecla rounded-xl bg-gray-100 font-semibold text-gray-950 active:bg-gray-200 dark:bg-white/10 dark:text-white"
                    x-on:click="if ((v || '').length < 4) v = (v || '') + '{{ $n }}'">{{ $n }}</button>
        @endforeach
        <span></span>
        <button type="button" class="velia-tecla rounded-xl bg-gray-100 font-semibold text-gray-950 active:bg-gray-200 dark:bg-white/10 dark:text-white"
                x-on:click="if ((v || '').length < 4) v = (v || '') + '0'">0</button>
        <button type="button" class="velia-tecla rounded-xl text-gray-700 dark:text-gray-300" aria-label="Borrar"
                x-on:click="v = (v || '').slice(0, -1)">⌫</button>
    </div>
</div>
