<x-filament-widgets::widget>
    @php($semana = \App\Support\Semana::resumen())
    <x-filament::section heading="Cómo va tu residencia con Velia" description="El servicio en los últimos 7 días y lo que Velia ha hecho en los últimos 30.">
        <dl class="velia-valor">
            <div>
                <dt>Turnos cerrados completos · 7 días</dt>
                <dd class="velia-valor-cifra">{{ $semana['completos'] }} de {{ $semana['turnos'] }}</dd>
                @if ($semana['con_pendientes'])
                    <dd class="flex flex-wrap items-center gap-2 text-sm text-gray-600">{{ $semana['con_pendientes'] }} con pendientes {{ $this->verTurnosAction }}</dd>
                @endif
            </div>
            <div>
                <dt>Incidencias · 7 días</dt>
                <dd class="velia-valor-cifra">{{ $semana['incidencias'] }}</dd>
                <dd class="flex flex-wrap items-center gap-2 text-sm text-gray-600">
                    {{ $semana['diferencia'] > 0 ? $semana['diferencia'].' más' : ($semana['diferencia'] < 0 ? abs($semana['diferencia']).' menos' : 'Igual') }} que la semana anterior
                    @if ($semana['incidencias']){{ $this->verIncidenciasAction }}@endif
                </dd>
            </div>
            <div>
                <dt>Registros del equipo · 30 días</dt>
                <dd class="velia-valor-cifra">{{ $registros }}</dd>
                <dd class="text-sm text-gray-600">{{ str_replace('.', ',', $porResidente) }} por residente y día · cuentan como evidencia UNE</dd>
            </div>
            <div>
                <dt>Resúmenes enviados a familias</dt>
                <dd class="velia-valor-cifra">{{ $enviados }}</dd>
                <dd class="text-sm text-gray-600">Sin escribirlos a mano</dd>
            </div>
            <div>
                <dt>Tiempo devuelto al cuidado · 30 días <span class="velia-sugiere-ejemplo" title="Estimación: {{ \App\Filament\Direccion\Widgets\ValorVeliaH::MINUTOS_POR_REGISTRO }} minutos menos por registro frente al papel. Se medirá en el piloto.">Ejemplo</span></dt>
                <dd class="velia-valor-cifra">≈ {{ $horas }} h</dd>
                <dd class="text-sm text-gray-600">Estimado, hasta medirlo en el piloto</dd>
            </div>
            <div>
                <dt>Satisfacción de las familias</dt>
                <dd class="velia-valor-cifra text-gray-400">Sin datos aún</dd>
                <dd class="text-sm text-gray-600">Aún no se mide: falta decidir cómo</dd>
            </div>
        </dl>
    </x-filament::section>
    <x-filament-actions::modals />
</x-filament-widgets::widget>
