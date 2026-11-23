<x-filament-widgets::widget>
    @php($avisos = $this->getAvisos())
    @if ($avisos->isNotEmpty())
        <div class="flex flex-col gap-2">
            @foreach ($avisos as $a)
                @php([$planta] = explode(' · ', $a->titulo))
                <div role="alert" class="flex flex-wrap items-center justify-between gap-3"
                     style="background:rgb(254 242 242);border:1px solid rgb(254 202 202);border-radius:12px;padding:12px 16px">
                    <p class="flex items-center gap-2 text-sm font-semibold" style="color:rgb(153 27 27)">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" style="width:20px;height:20px;flex-shrink:0" />
                        {{ $planta }} sin turno desde las {{ \App\Support\Franja::actual()['inicio']->format('H:i') }}
                        <span class="font-normal">· nadie ha abierto el turno de {{ \App\Support\Franja::actual()['nombre'] }}</span>
                    </p>
                    <div class="flex items-center gap-3">
                        {{ ($this->quienFaltaAction)(['planta' => (int) $a->detalle]) }}
                        {{ ($this->noProcedeAction)(['aviso' => $a->id]) }}
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    <x-filament-actions::modals />
</x-filament-widgets::widget>
