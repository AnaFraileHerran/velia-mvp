{{-- Inicio con pestañas (Holded une Inicio y Tableros): Hoy · Esta semana · Auditoría, con contador. Widgets fijos, no configurables. --}}
<x-filament::tabs label="Partes del inicio" class="velia-h-pestanas">
    @foreach ($pestanas as $clave => $p)
        <x-filament::tabs.item
            :active="$activa === $clave"
            :icon="$p['icono']"
            :badge="$p['n'] ?: null"
            :badge-color="$p['color']"
            wire:click="$set('pestana', '{{ $clave }}')"
        >
            {{ $p['etiqueta'] }}
        </x-filament::tabs.item>
    @endforeach
</x-filament::tabs>
