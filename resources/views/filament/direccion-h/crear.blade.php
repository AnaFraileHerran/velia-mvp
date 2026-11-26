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
        {{-- 26/11: Ausencias deja de ser una sección; la salida se registra desde Residentes --}}
        <x-filament::dropdown.list.item tag="a" :href="ResidenteResource::getUrl('index', ['action' => 'registrarSalida'])" icon="heroicon-o-arrow-right-start-on-rectangle">
            Salida de un residente
        </x-filament::dropdown.list.item>
    </x-filament::dropdown.list>

    {{-- 26/11 (comentario de Pablo en EQ-01): un solo «Añadir persona»; el rol se elige al empezar --}}
    <x-filament::dropdown.list>
        <x-filament::dropdown.list.item tag="a" :href="AuxiliarResource::getUrl('index', ['action' => 'anadirPersona'])" icon="heroicon-o-identification">
            Persona del equipo
        </x-filament::dropdown.list.item>
    </x-filament::dropdown.list>
</x-filament::dropdown>
