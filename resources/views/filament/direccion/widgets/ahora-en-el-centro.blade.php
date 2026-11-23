<x-filament-widgets::widget>
    {{-- Banda «Ahora»: solo estado, una celda por cosa, tres líneas como máximo. La acción sobre lo que falla está en la alerta de arriba. --}}
    <div class="velia-banda" role="region" aria-label="Ahora en el centro">
        <div class="velia-banda-celda velia-banda-titulo">
            <p class="text-sm font-semibold text-gray-950 dark:text-white">Ahora</p>
            <p class="text-xs text-gray-500">Turno de {{ $franja['nombre'] }}</p>
        </div>

        @foreach ($plantas as $p)
            <div class="velia-banda-celda">
                <p class="text-xs font-semibold text-gray-500">{{ $p['planta'] }}</p>
                @forelse ($p['personas'] as $u)
                    <a href="{{ \App\Filament\Direccion\Resources\AuxiliarResource::getUrl('view', ['record' => $u]) }}" class="flex items-center gap-2 hover:underline">
                        <span style="position:relative;display:inline-block;flex-shrink:0" aria-hidden="true">
                            <img src="{{ \App\Support\Avatar::url($u->foto, $u->name) }}" alt="" style="width:24px;height:24px;border-radius:9999px;object-fit:cover">
                            <span style="position:absolute;right:-1px;bottom:-1px;width:9px;height:9px;border-radius:9999px;background:#16a34a;border:2px solid #fff"></span>
                        </span>
                        <span class="text-sm font-medium text-gray-950 dark:text-white">{{ strtok($u->name, ' ') }}</span>
                        <span class="sr-only">tiene el turno abierto</span>
                    </a>
                @empty
                    <p class="text-sm font-semibold text-danger-700">Sin turno</p>
                @endforelse
                <p class="text-xs text-gray-500">{{ $p['registrados'] }} de {{ $p['total'] }} registrados</p>
            </div>
        @endforeach

        @foreach ($sanitarios as $sx)
            <div class="velia-banda-celda">
                <p class="text-xs font-semibold text-gray-500">{{ $sx['puesto'] }} <span class="font-normal">· horario</span></p>
                <p class="text-sm {{ $sx['ahora'] ? 'font-medium text-gray-950 dark:text-white' : 'text-gray-500' }}">
                    @if ($sx['ficha'])<a class="hover:underline" href="{{ $sx['ficha'] }}">{{ $sx['texto'] }}</a>@else{{ $sx['texto'] }}@endif
                </p>
            </div>
        @endforeach

        <div class="velia-banda-celda">
            <p class="text-xs font-semibold text-gray-500"><a class="hover:underline" href="{{ \App\Filament\Direccion\Resources\AusenciaResource::getUrl() }}">Fuera · {{ $fuera->count() }}</a></p>
            @forelse ($fuera as $a)
                <p class="text-sm">
                    <a class="font-medium hover:underline" href="{{ \App\Filament\Direccion\Resources\ResidenteResource::getUrl('view', ['record' => $a->residente_id]) }}">{{ $a->residente?->nombre }}</a>
                    <span class="text-gray-500">· {{ $a->vuelve ? ($a->vuelve->isToday() ? 'vuelve hoy' : 'vuelve el '.$a->vuelve->format('d/m')) : 'sin fecha' }}</span>
                </p>
            @empty
                <p class="text-sm text-gray-500">Nadie</p>
            @endforelse
        </div>
    </div>
</x-filament-widgets::widget>
