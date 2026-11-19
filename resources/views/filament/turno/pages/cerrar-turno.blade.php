<x-filament-panels::page>
    @php($pendientes = $this->pendientes())

    @if ($pendientes->isNotEmpty())
        <div class="rounded-xl bg-warning-50 p-4 dark:bg-warning-400/10">
            <p class="font-semibold">Quedan {{ $pendientes->count() }} {{ $pendientes->count() === 1 ? 'residente' : 'residentes' }} sin registrar</p>
            <p class="text-sm">Regístralos o cierra indicando el motivo.</p>
            <ul class="mt-3 divide-y divide-warning-200">
                @foreach ($pendientes as $p)
                    <li class="flex items-center justify-between gap-3 py-2 text-sm">
                        <span><span class="font-medium">{{ $p['r']->nombre }} · Hab. {{ $p['r']->habitacion }}</span><br>
                            <span class="text-gray-600">{{ $p['estado'] === 'borrador' ? 'Borrador sin confirmar' : 'Sin registro' }}</span></span>
                        <x-filament::button size="sm" tag="a" :href="\App\Filament\Turno\Pages\Registrar::getUrl(['residente' => $p['r']->id])">{{ $p['estado'] === 'borrador' ? 'Confirmar' : 'Registrar' }}</x-filament::button>
                    </li>
                @endforeach
            </ul>
        </div>

        <fieldset class="space-y-2">
            <legend class="mb-2 text-sm font-semibold">Motivo para cerrar con pendientes (obligatorio)</legend>
            @foreach (\App\Models\Turno::MOTIVOS_CIERRE as $k => $l)
                <label class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-gray-900">
                    <input type="radio" wire:model.live="motivo" value="{{ $k }}" class="h-5 w-5"> <span>{{ $l }}</span>
                </label>
            @endforeach
            @error('motivo')<p class="text-sm text-danger-600">{{ $message }}</p>@enderror
            @if ($motivo === 'otros')
                <x-filament::input.wrapper>
                    <x-filament::input wire:model="motivoTexto" placeholder="Cuéntalo en pocas palabras (obligatorio)" />
                </x-filament::input.wrapper>
                @error('motivoTexto')<p class="text-sm text-danger-600">{{ $message }}</p>@enderror
            @endif
        </fieldset>
    @else
        <p class="rounded-xl bg-success-50 p-4 text-sm font-medium text-success-700 dark:bg-success-400/10">Todos los residentes están registrados.</p>
    @endif

    <section>
        <h2 class="text-sm font-semibold">Relevo para el siguiente turno <span class="font-normal text-gray-500">· revisa antes de cerrar</span></h2>
        <ul class="mt-2 divide-y divide-gray-100 rounded-xl border border-gray-200 bg-white dark:divide-white/5 dark:border-white/10 dark:bg-gray-900">
            @forelse ($this->relevo() as $r)
                <li class="flex items-center justify-between gap-3 px-4 py-2 text-sm">
                    <span>{{ $r->residente->nombre }} · {{ $r->resumenCorto() }}</span>
                    <x-filament::link tag="button" color="gray" wire:click="quitar({{ $r->id }})">Quitar</x-filament::link>
                </li>
            @empty
                <li class="px-4 py-2 text-sm text-gray-500">Sin novedades que pasar.</li>
            @endforelse
        </ul>
        <x-filament::input.wrapper class="mt-3">
            <x-filament::input wire:model="nota" placeholder="Nota para la compañera (opcional)" />
        </x-filament::input.wrapper>
    </section>

    <x-filament::button size="xl" class="w-full" wire:click="cerrar">Cerrar el turno</x-filament::button>
</x-filament-panels::page>
