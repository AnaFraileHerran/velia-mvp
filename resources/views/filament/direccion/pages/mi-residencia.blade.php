<x-filament-panels::page>
    @php($r = \App\Models\Residencia::actual())

    <form wire:submit="guardar" class="space-y-6">
        {{ $this->form }}
        <x-filament::button type="submit">Guardar cambios</x-filament::button>
    </form>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-filament::section heading="Plantas">
            <ul class="space-y-1 text-sm">
                @foreach ($this->plantas() as $p)
                    <li><span class="font-medium">{{ $p->nombre }}</span> · {{ $p->residentes_count }} residentes · {{ $p->auxiliares_count }} auxiliares</li>
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
        </x-filament::section>
    </div>
</x-filament-panels::page>
