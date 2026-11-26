{{-- Panel lateral de una planta (Nueva versión): quién está, a quién falta registrar e incidencias de hoy. --}}
@php($n = $residentes->where('estado', 'registrado')->count())
@php($total = $residentes->where('estado', '!=', 'fuera')->count())
<div class="flex flex-col gap-5 text-sm">
    <div class="grid grid-cols-3 gap-2">
        <div class="velia-mini"><span class="velia-mini-cifra">{{ $n }}/{{ $total }}</span><span>registrados</span></div>
        <div class="velia-mini"><span class="velia-mini-cifra">{{ $personas->count() }}</span><span>{{ $personas->count() === 1 ? 'persona en turno' : 'personas en turno' }}</span></div>
        <div class="velia-mini {{ $incidencias->count() ? 'velia-mini-alerta' : '' }}"><span class="velia-mini-cifra">{{ $incidencias->count() }}</span><span>incidencias hoy</span></div>
    </div>

    <section>
        <p class="mb-2 font-semibold text-gray-950">En turno</p>
        @forelse ($personas as $u)
            <a href="{{ \App\Filament\Direccion\Resources\AuxiliarResource::getUrl('view', ['record' => $u]) }}" class="flex items-center gap-2 rounded-lg px-1 py-1.5 hover:bg-gray-50">
                <img src="{{ \App\Support\Avatar::url($u->foto, $u->name) }}" alt="" class="h-7 w-7 rounded-full object-cover">
                <span class="font-medium text-gray-950">{{ $u->name }}</span>
                @if ($u->responsable_turno)<span class="text-xs text-gray-500">· responsable de turno</span>@endif
            </a>
        @empty
            <p class="{{ $sinTurno ? 'font-semibold text-warning-700' : 'text-gray-500' }}">{{ $sinTurno ? 'Nadie ha abierto el turno.' : 'Nadie en turno ahora.' }}</p>
        @endforelse
    </section>

    @if ($incidencias->isNotEmpty())
        <section>
            <p class="mb-2 font-semibold text-gray-950">Incidencias de hoy</p>
            <ul class="flex flex-col gap-2">
                @foreach ($incidencias as $i)
                    <li class="rounded-lg bg-danger-50 px-3 py-2">
                        <span class="font-semibold text-danger-700">{{ $i->tipo_incidencia }}</span> · {{ $i->residente?->nombre }}
                        <span class="block text-xs text-gray-600">{{ $i->resumenCorto() }} · {{ $i->created_at->format('H:i') }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section>
        <p class="mb-2 font-semibold text-gray-950">Residentes</p>
        <ul class="flex flex-col">
            @foreach ($residentes as ['r' => $r, 'estado' => $e])
                <li>
                    <a href="{{ \App\Filament\Direccion\Resources\ResidenteResource::getUrl('view', ['record' => $r]) }}" class="flex items-center gap-2 rounded-lg px-1 py-1.5 hover:bg-gray-50">
                        <img src="{{ \App\Support\Avatar::url($r->foto, $r->nombre) }}" alt="" class="h-7 w-7 rounded-full object-cover">
                        <span class="min-w-0 flex-1 truncate text-gray-950">{{ $r->nombre }} <span class="text-gray-500">· Hab. {{ $r->habitacion }}</span></span>
                        <span class="velia-estado velia-estado-{{ $e }}">{{ ['pendiente' => 'Sin registrar', 'registrado' => 'Registrado', 'fuera' => 'Fuera'][$e] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    <x-filament::link :href="$urlResidentes" icon="heroicon-o-arrow-right">Ver los residentes de {{ $planta?->nombre }}</x-filament::link>
</div>
