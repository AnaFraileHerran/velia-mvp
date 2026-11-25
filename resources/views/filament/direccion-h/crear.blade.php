{{-- «+ Crear» global (patrón de Holded): lo que se da de alta con frecuencia, desde cualquier pantalla. --}}
@php
    use App\Filament\Direccion\Resources\AusenciaResource;
    use App\Filament\Direccion\Resources\AuxiliarResource;
    use App\Filament\Direccion\Resources\ResidenteResource;
@endphp
<x-filament::dropdown placement="bottom-end" width="xs" teleport>
    <x-slot name="trigger">
        <x-filament::button icon="heroicon-m-plus" size="sm" class="velia-h-crear">Crear</x-filament::button>
    </x-slot>

    <x-filament::dropdown.header icon="heroicon-o-plus-circle">¿Qué quieres crear?</x-filament::dropdown.header>

    <x-filament::dropdown.list>
        <x-filament::dropdown.list.item tag="a" :href="ResidenteResource::getUrl('create')" icon="heroicon-o-user-plus">
            Residente nuevo
        </x-filament::dropdown.list.item>
        <x-filament::dropdown.list.item tag="a" :href="AusenciaResource::getUrl('index', ['action' => 'create'])" icon="heroicon-o-arrow-right-start-on-rectangle">
            Salida o ausencia
        </x-filament::dropdown.list.item>
    </x-filament::dropdown.list>

    <x-filament::dropdown.list>
        <x-filament::dropdown.list.item tag="a" :href="AuxiliarResource::getUrl('create')" icon="heroicon-o-identification">
            Auxiliar
        </x-filament::dropdown.list.item>
        <x-filament::dropdown.list.item tag="a" :href="AuxiliarResource::getUrl('create', ['rol' => 'sanitario'])" icon="heroicon-o-heart">
            Enfermería o medicina
        </x-filament::dropdown.list.item>
    </x-filament::dropdown.list>
</x-filament::dropdown>
