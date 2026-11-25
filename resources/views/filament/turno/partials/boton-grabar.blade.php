{{--
    Botón flotante de micrófono (25/11/2026, comentario de Antonio en A1-02): desde cualquier pantalla del turno,
    eliges a quién y empieza a grabar. Encima de la barra inferior, a la derecha (pulgar), en móvil y en tablet.
    No sustituye a «Escribir»: si el micrófono falla, Registrar pasa a escribir como siempre (regla 3).
--}}
@php
    $t = \App\Support\TurnoActual::make();
    $lista = $t->residentes()->reject(fn ($r) => $r->estado === 'fuera')
        ->map(fn ($r) => ['r' => $r, 'estado' => $t->estado($r)])
        ->sortBy(fn ($x) => $x['estado'] === 'registrado' ? 1 : 0)->values();
@endphp
@unless (request()->routeIs(\App\Filament\Turno\Pages\Registrar::getRouteName()))
    <div x-data="{ abierto: false }" x-on:keydown.escape.window="abierto = false">
        <button type="button" class="velia-fab" x-on:click="abierto = true" aria-label="Grabar un registro" aria-haspopup="dialog">
            <x-filament::icon icon="heroicon-s-microphone" class="h-7 w-7" aria-hidden="true" />
        </button>

        <template x-teleport="body">
            <div x-show="abierto" x-cloak class="velia-hoja-fondo" x-on:click.self="abierto = false">
                <div class="velia-hoja" role="dialog" aria-modal="true" aria-label="¿A quién vas a registrar?">
                    <div class="flex items-center justify-between gap-3 px-4 pt-4">
                        <p class="text-base font-semibold text-gray-950">¿A quién vas a registrar?</p>
                        <button type="button" class="text-sm text-gray-500 underline" x-on:click="abierto = false">Cerrar</button>
                    </div>
                    <p class="px-4 pb-2 text-xs text-gray-500">Toca su nombre y empieza a hablar. Primero, quien aún no tiene registro en este turno.</p>
                    <ul class="velia-hoja-lista">
                        @foreach ($lista as ['r' => $r, 'estado' => $estado])
                            <li>
                                <a href="{{ \App\Filament\Turno\Pages\Registrar::getUrl(['residente' => $r->id, 'grabar' => 1]) }}" class="velia-hoja-item">
                                    <img src="{{ \App\Support\Avatar::url($r->foto, $r->nombre) }}" alt="" class="h-10 w-10 shrink-0 rounded-full object-cover">
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-gray-950">{{ $r->nombre }}</span>
                                        <span class="block text-xs text-gray-500">Hab. {{ $r->habitacion }} · {{ ['pendiente' => 'Por registrar', 'borrador' => 'Borrador sin confirmar', 'registrado' => 'Ya registrado'][$estado] ?? '' }}</span>
                                    </span>
                                    <x-filament::icon icon="heroicon-o-microphone" class="h-5 w-5 shrink-0 text-primary-600" aria-hidden="true" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </template>
    </div>
@endunless
