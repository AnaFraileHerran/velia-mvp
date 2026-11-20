<x-filament-widgets::widget>
    <div class="flex flex-col gap-6">
        <x-filament::section heading="Esta semana">
            <ul class="flex flex-col gap-3 text-sm">
                <li class="flex items-center justify-between gap-2">
                    <span>{{ $resumenesPorRevisar }} resúmenes para las familias por revisar</span>
                    <x-filament::link :href="\App\Filament\Direccion\Resources\ResumenResource::getUrl()">Ir a Familias</x-filament::link>
                </li>
                @foreach ($revisiones as $r)
                    <li class="flex items-center justify-between gap-2">
                        <span>Revisión del plan personal de {{ $r->nombre }} · vence el {{ $r->plan_proxima_revision->locale('es')->isoFormat('D MMM') }}</span>
                        <x-filament::link :href="\App\Filament\Direccion\Resources\ResidenteResource::getUrl('view', ['record' => $r])">Ver</x-filament::link>
                    </li>
                @endforeach
                @if ($preguntas)
                    <li>{{ $preguntas }} pregunta(s) al turno esperando respuesta</li>
                @endif
            </ul>
        </x-filament::section>

        <x-filament::section heading="Hoy en la residencia">
            <ul class="flex flex-col gap-2 text-sm text-gray-700 dark:text-gray-300">
                <li>En turno: {{ $enTurno->isEmpty() ? 'nadie ha abierto turno todavía' : $enTurno->map(fn ($t) => $t->user->name)->join(', ') }}</li>
                <li>Fuera: {{ $fuera }}@if ($vuelvenHoy->isNotEmpty()) · vuelve hoy: {{ $vuelvenHoy->map(fn ($a) => $a->residente->nombre)->join(', ') }}@endif</li>
            </ul>
        </x-filament::section>
    </div>
</x-filament-widgets::widget>
