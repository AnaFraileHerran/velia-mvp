<x-filament-widgets::widget>
    @php
        $semana = \App\Support\Semana::resumen();
        $pctTurnos = $semana['turnos'] ? round($semana['completos'] / $semana['turnos'] * 100) : 0;
        $dif = $semana['diferencia'];
        // Mismo dato, mismo nombre y misma cifra que en Calidad (26/11, acordado con Ana): una sola fuente.
        $ind = \App\Support\Indicadores::lista();
    @endphp
    {{--
        Cómo va tu residencia con Velia (Nueva versión, 25/11/2026). Dos grupos para que se lea la historia:
        el servicio (7 días, gris y verde) y lo que ha hecho Velia (30 días, cobalto). Cada cifra con su icono, una
        señal visual (barra o tendencia) y una línea. Lo que no se mide aún, honesto: «Ejemplo» o «—» (regla 11).
    --}}
    {{-- Sin cabecera de sección: los dos títulos de grupo ya cuentan la historia y así el Inicio cabe sin scroll --}}
    <x-filament::section compact aria-label="Cómo va tu residencia con Velia">

        <div class="velia-valor-grupos">
            <div class="velia-valor-grupo">
                <p class="velia-valor-titulo">Cómo va tu residencia · últimos 7 días</p>
                <div class="velia-valor-fila">
                    <div class="velia-kpi">
                        <span class="velia-kpi-icono velia-kpi-icono-verde"><x-filament::icon icon="heroicon-o-clipboard-document-check" class="h-5 w-5" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="velia-kpi-etiqueta">Turnos completos</p>
                            <p class="velia-kpi-cifra">{{ $semana['completos'] }}<span class="velia-kpi-de">/{{ $semana['turnos'] }}</span></p>
                            <span class="velia-kpi-barra" aria-hidden="true"><span style="width: {{ $pctTurnos }}%"></span></span>
                            <p class="velia-kpi-nota">@if ($semana['con_pendientes']){{ $semana['con_pendientes'] }} con pendientes · {{ $this->verTurnosAction }}@else Todos sin pendientes @endif</p>
                        </div>
                    </div>
                    <div class="velia-kpi">
                        <span class="velia-kpi-icono {{ $dif > 0 ? 'velia-kpi-icono-rojo' : 'velia-kpi-icono-gris' }}"><x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="velia-kpi-etiqueta">Incidencias</p>
                            <p class="velia-kpi-cifra">{{ $semana['incidencias'] }}
                                <span class="velia-tendencia {{ $dif > 0 ? 'velia-tendencia-mal' : ($dif < 0 ? 'velia-tendencia-bien' : '') }}">{{ $dif > 0 ? '↑ '.$dif : ($dif < 0 ? '↓ '.abs($dif) : '=') }}</span>
                            </p>
                            <p class="velia-kpi-nota">frente a la semana anterior @if ($semana['incidencias'])· {{ $this->verIncidenciasAction }}@endif</p>
                        </div>
                    </div>
                    <div class="velia-kpi">
                        <span class="velia-kpi-icono velia-kpi-icono-verde"><x-filament::icon icon="heroicon-o-calendar-days" class="h-5 w-5" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="velia-kpi-etiqueta">{{ $ind['planes']['nombre'] }}</p>
                            <p class="velia-kpi-cifra">{{ $ind['planes']['cifra'] }}<span class="velia-kpi-de">{{ $ind['planes']['de'] }}</span></p>
                            <span class="velia-kpi-barra" aria-hidden="true"><span style="width: {{ $ind['planes']['pct'] }}%"></span></span>
                            <p class="velia-kpi-nota"><a href="{{ \App\Filament\Direccion\Pages\Calidad::getUrl() }}" class="hover:underline">Ver todos los indicadores</a></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="velia-valor-grupo velia-valor-grupo-velia">
                <p class="velia-valor-titulo"><x-filament::icon icon="heroicon-o-sparkles" class="h-3.5 w-3.5" /> Lo que ha hecho Velia · últimos 30 días</p>
                <div class="velia-valor-fila">
                    <div class="velia-kpi">
                        <span class="velia-kpi-icono"><x-filament::icon icon="heroicon-o-pencil-square" class="h-5 w-5" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="velia-kpi-etiqueta">Registros</p>
                            <p class="velia-kpi-cifra">{{ $registros }}</p>
                            <p class="velia-kpi-nota" title="Cada registro confirmado cuenta como evidencia para la UNE 158101">{{ str_replace('.', ',', $porResidente) }} por residente y día</p>
                        </div>
                    </div>
                    <div class="velia-kpi">
                        <span class="velia-kpi-icono"><x-filament::icon icon="heroicon-o-envelope" class="h-5 w-5" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="velia-kpi-etiqueta">Resúmenes enviados</p>
                            <p class="velia-kpi-cifra">{{ $enviados }}</p>
                            <p class="velia-kpi-nota">a las familias, sin escribirlos</p>
                        </div>
                    </div>
                    <div class="velia-kpi">
                        <span class="velia-kpi-icono"><x-filament::icon icon="heroicon-o-clock" class="h-5 w-5" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="velia-kpi-etiqueta">Tiempo devuelto</p>
                            <p class="velia-kpi-cifra">≈ {{ $horas }} h <span class="velia-sugiere-ejemplo" title="Estimación: {{ \App\Filament\Direccion\Widgets\ValorVeliaH::MINUTOS_POR_REGISTRO }} minutos menos por registro frente al papel. Se medirá en el piloto.">Ejemplo</span></p>
                            <p class="velia-kpi-nota">al cuidado de los residentes</p>
                        </div>
                    </div>
                    <div class="velia-kpi velia-kpi-pendiente">
                        <span class="velia-kpi-icono velia-kpi-icono-gris"><x-filament::icon icon="heroicon-o-heart" class="h-5 w-5" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="velia-kpi-etiqueta">Satisfacción</p>
                            <p class="velia-kpi-cifra text-gray-400">—</p>
                            <p class="velia-kpi-nota">de las familias · aún no se mide</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
    <x-filament-actions::modals />
</x-filament-widgets::widget>
