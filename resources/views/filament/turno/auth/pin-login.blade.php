{{-- A1 · Entrar con PIN (tablet 116:500 · móvil 118:305) --}}
<x-filament-panels::page.simple>
    <div class="space-y-6 text-center">
        <div>
            @if (! $esMovil && $residencia->fotos)
                <div class="mb-4 aspect-[16/7] overflow-hidden rounded-xl bg-gray-100"
                     x-data="{ i: 0, fotos: @js(collect($residencia->fotos)->map(fn ($f) => \Illuminate\Support\Facades\Storage::url($f))) }"
                     x-init="setInterval(() => i = (i + 1) % fotos.length, 6000)">
                    <img :src="fotos[i]" alt="" class="h-full w-full object-cover">
                </div>
            @endif
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $residencia->nombre }}</p>
            <p class="text-sm text-gray-500">{{ $residencia->lema }}</p>
        </div>

        <div>
            @if ($vinculada)
                <h1 class="text-2xl font-semibold text-gray-950 dark:text-white">Hola, {{ strtok($vinculada->name, ' ') }}</h1>
            @else
                <p class="text-sm text-gray-500">{{ ucfirst(now()->locale('es')->isoFormat('dddd, D [de] MMMM')) }}</p>
            @endif
            <h2 class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">Introduce tu PIN</h2>
            @unless ($vinculada)<p class="text-sm text-gray-500">Te reconoceremos al escribirlo.</p>@endunless
        </div>

        @include('filament.turno.partials.teclado', ['modelo' => 'pin'])

        @if ($error)
            <p class="text-sm font-medium text-danger-600" role="alert">{{ $error }}</p>
        @endif

        <div class="flex flex-col items-center gap-2 text-sm">
            @if ($esMovil)
                <p class="text-gray-500">¿Olvidaste tu PIN? Pídeselo a la responsable de turno.</p>
            @else
                <x-filament::link :href="route('filament.turno.restablecer-pin')">¿Olvidaste tu PIN?</x-filament::link>
            @endif
            <x-filament::link :href="route('filament.turno.primer-acceso')" color="gray">Es mi primer día</x-filament::link>
        </div>
    </div>
</x-filament-panels::page.simple>
