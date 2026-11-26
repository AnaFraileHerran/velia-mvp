<x-filament-panels::page>
    @php($r = \App\Models\Residencia::actual())

    @if (\App\Support\VersionH::activa())
        {{-- 26/11 (Ana): una pestaña por residencia, arriba. Si hay otra, se añade aquí (alta preparada con ICARUS). --}}
        <x-filament::tabs label="Residencias">
            <x-filament::tabs.item :active="true" icon="heroicon-m-building-office">{{ $r->nombre }}</x-filament::tabs.item>
            <x-filament::tabs.item icon="heroicon-m-plus" wire:click="mountAction('solicitarResidencia')">Añadir otra residencia</x-filament::tabs.item>
        </x-filament::tabs>
    @endif

    @if (\App\Support\VersionH::activa() && ! $editando)
        {{-- Nueva versión: modo vista con lo útil; «Editar» arriba abre el formulario --}}
        <div class="grid gap-4 lg:grid-cols-3">
            <x-filament::section heading="Identidad y contacto" description="Aparece al pie de cada resumen para las familias." class="lg:col-span-2">
                <dl class="velia-datos">
                    @foreach (['Nombre' => $r->nombre, 'Persona responsable' => $r->responsable, 'Dirección' => $r->direccion, 'Teléfono' => $r->telefono, 'Email' => $r->email, 'CIF' => $r->cif, 'Plazas autorizadas' => $r->plazas_autorizadas] as $etq => $val)
                        <div><dt>{{ $etq }}</dt><dd>{{ $val ?: '—' }}</dd></div>
                    @endforeach
                </dl>
            </x-filament::section>
            <div class="space-y-4">
                <x-filament::section heading="Próxima auditoría">
                    <p class="text-lg font-semibold">{{ $r->proxima_auditoria?->locale('es')->isoFormat('D [de] MMMM [de] YYYY') ?? 'Sin fecha' }}</p>
                    @if ($r->proxima_auditoria)<p class="text-sm text-gray-500">en {{ (int) now()->startOfDay()->diffInDays($r->proxima_auditoria) }} días</p>@endif
                </x-filament::section>
                <x-filament::section heading="Pantalla de acceso">
                    <p class="text-sm text-gray-600">{{ count($r->fotos ?? []) }} {{ count($r->fotos ?? []) === 1 ? 'foto' : 'fotos' }} de la residencia en la tablet de las auxiliares.</p>
                </x-filament::section>
            </div>
        </div>
    @else
        <form wire:submit="guardar" class="space-y-6">
            {{ $this->form }}
            <div class="flex gap-3">
                <x-filament::button type="submit">Guardar cambios</x-filament::button>
                @if (\App\Support\VersionH::activa())<x-filament::button color="gray" wire:click="cancelar">Cancelar</x-filament::button>@endif
            </div>
        </form>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        <x-filament::section heading="Plantas">
            <ul class="space-y-1 text-sm">
                @foreach ($this->plantas() as $p)
                    <li class="flex items-center justify-between gap-2">
                        <span><span class="font-medium">{{ $p->nombre }}</span> · {{ $p->residentes_count }} residentes · {{ $p->auxiliares_count }} auxiliares</span>
                        <x-filament::link :href="\App\Filament\Direccion\Resources\ResidenteResource::getUrl('index', ['tableFilters' => ['planta_id' => ['value' => $p->id]]])">Ver residentes</x-filament::link>
                    </li>
                @endforeach
            </ul>
        </x-filament::section>

        <x-filament::section heading="Plan y facturación">
            <p class="text-sm font-medium">Piloto gratuito @if ($r->piloto_hasta) hasta el {{ $r->piloto_hasta->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }} @endif</p>
            <p class="mt-1 text-sm text-gray-500">Al terminar: condiciones de residencia fundadora. Nunca pasarás a pagar sin aceptarlo.</p>
        </x-filament::section>

        <x-filament::section heading="Lo que Velia te ayuda a cumplir">
            <ul class="space-y-1 text-sm">
                <li>Revisión del plan personal cada 6 meses</li>
                <li>Comunicación con las familias</li>
                <li>Registro en aplicación informática</li>
            </ul>
            <p class="mt-2 text-xs text-gray-500">Orden 2680/2024 de la Comunidad de Madrid</p>
            <x-filament::link :href="\App\Filament\Direccion\Pages\Calidad::getUrl()" class="mt-3">Ver cómo vas en Calidad y auditoría</x-filament::link>
        </x-filament::section>

@if (! \App\Support\VersionH::activa())
                <x-filament::section heading="¿Tienes otra residencia?">
            <p class="text-sm text-gray-600 dark:text-gray-300">Puedes llevar varias residencias con Velia. Cada una tiene sus residentes, su equipo y su auditoría. Solicita el alta y la preparamos contigo.</p>
            <div class="mt-3">{{ $this->solicitarResidenciaAction }}</div>
        </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
