<ul class="divide-y divide-gray-200 text-sm dark:divide-white/10">
    @forelse ($incidencias as $i)
        <li class="flex flex-col gap-1 py-3">
            <div class="flex flex-wrap items-center gap-2">
                <x-filament::badge color="danger">{{ $i->tipo_incidencia ?: 'Incidencia' }}</x-filament::badge>
                <a class="font-semibold text-primary-600 hover:underline" href="{{ \App\Filament\Direccion\Resources\ResidenteResource::getUrl('view', ['record' => $i->residente_id]) }}">{{ $i->residente?->nombre }}</a>
                <span class="text-gray-500">· {{ ucfirst($i->created_at->locale('es')->isoFormat('ddd D MMM, HH:mm')) }}</span>
            </div>
            <p class="text-gray-700 dark:text-gray-300">{{ $i->resumenCorto() }}</p>
            <p class="text-xs text-gray-500">Registrado por {{ $i->user?->name }}</p>
        </li>
    @empty
        <li class="py-6 text-center text-gray-500">Sin incidencias esta semana.</li>
    @endforelse
</ul>
