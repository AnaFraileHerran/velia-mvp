{{-- Turno sin abrir: quién debería estar en esa planta y franja, cómo localizarla y acceso a su ficha («No puede entrar» está allí) --}}
<p class="text-sm text-gray-600 dark:text-gray-300">{{ $planta?->nombre }} · turno de {{ $franja['nombre'] }}, desde las {{ $franja['inicio']->format('H:i') }}</p>
<ul class="mt-3 divide-y divide-gray-200 dark:divide-white/10">
    @forelse ($personas as $u)
        <li class="flex flex-wrap items-center justify-between gap-3 py-3">
            <span class="flex items-center gap-3">
                <img src="{{ \App\Support\Avatar::url($u->foto, $u->name) }}" alt="" style="width:40px;height:40px;border-radius:9999px;object-fit:cover">
                <span>
                    <span class="block font-semibold text-gray-950 dark:text-white">{{ $u->name }}</span>
                    <span class="flex items-center gap-1 text-sm text-gray-700 dark:text-gray-300">
                        <x-filament::icon icon="heroicon-o-phone" style="width:14px;height:14px" />{{ $u->telefono ?: 'Sin contacto de trabajo' }}
                    </span>
                    <span class="block text-xs text-gray-500">{{ \App\Filament\Direccion\Resources\AuxiliarResource::estadoPin($u) }}</span>
                </span>
            </span>
            <x-filament::link :href="\App\Filament\Direccion\Resources\AuxiliarResource::getUrl('view', ['record' => $u])">Abrir ficha</x-filament::link>
        </li>
    @empty
        <li class="py-3 text-sm text-gray-600">No hay ninguna auxiliar asignada a esta planta en este turno. Revisa el Equipo.</li>
    @endforelse
</ul>
<div class="mt-3"><x-filament::link :href="\App\Filament\Direccion\Resources\AuxiliarResource::getUrl()">Ver todo el equipo</x-filament::link></div>
