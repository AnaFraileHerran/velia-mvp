<x-filament-panels::page>
    @php
        $preguntas = $t->preguntas();
        $novedades = $t->novedades();
        $vuelven = $t->vuelvenHoy();
        $notas = $t->notasCompaneras();
        $sin = $t->sinNovedades();
        $porRegistrar = $t->porRegistrar();
    @endphp

    {{-- Resumen en una línea: lo que hay de un vistazo --}}
    <div class="flex flex-wrap gap-2">
        <x-filament::badge color="gray">{{ $novedades->count() }} {{ $novedades->count() === 1 ? 'novedad' : 'novedades' }}</x-filament::badge>
        @if ($preguntas->isNotEmpty())<x-filament::badge color="warning">{{ $preguntas->count() }} {{ $preguntas->count() === 1 ? 'pregunta' : 'preguntas' }}</x-filament::badge>@endif
        @if ($vuelven->isNotEmpty())<x-filament::badge color="info">{{ $vuelven->count() }} vuelve hoy</x-filament::badge>@endif
        <a href="{{ \App\Filament\Turno\Pages\Residentes::getUrl() }}"><x-filament::badge color="primary">{{ $porRegistrar }} por registrar</x-filament::badge></a>
    </div>

    @if (session('velia_consejos'))
        <x-filament::section heading="Tres cosas para empezar" icon="heroicon-o-light-bulb">
            <ol class="list-decimal space-y-1 ps-5 text-sm">
                <li>Lee el relevo: lo importante del turno anterior está aquí.</li>
                <li>Registra desde «Residentes»: habla y la IA lo ordena. Tú confirmas.</li>
                <li>Al acabar, cierra el turno en «Mi turno».</li>
            </ol>
        </x-filament::section>
    @endif

    {{-- 1. Preguntas de la dirección: esperan respuesta --}}
    @foreach ($preguntas as $p)
        <div class="rounded-xl border-2 border-warning-400 bg-warning-50 p-4 dark:bg-warning-400/10">
            <p class="text-xs font-semibold uppercase tracking-wide text-warning-700">Pregunta de la dirección</p>
            <p class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $p->texto }}</p>
            <p class="text-sm text-gray-500">{{ $p->residente->nombre }} · Hab. {{ $p->residente->habitacion }} · {{ $p->created_at->format('H:i') }}</p>
            <form wire:submit="responder({{ $p->id }})" class="mt-3 flex gap-2">
                <x-filament::input.wrapper class="flex-1">
                    <x-filament::input wire:model="respuestas.{{ $p->id }}" placeholder="Tu respuesta" />
                </x-filament::input.wrapper>
                <x-filament::button type="submit">Responder</x-filament::button>
            </form>
        </div>
    @endforeach

    {{-- 2. Con novedades: incidencias arriba --}}
    <section>
        <h2 class="mb-2 text-sm font-semibold text-gray-950 dark:text-white">Con novedades <span class="font-normal text-gray-500">· toca para ver cómo atenderle</span></h2>
        <ul class="divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-200 bg-white dark:divide-white/5 dark:border-white/10 dark:bg-gray-900">
            @forelse ($novedades as $n)
                <li>
                    <button type="button" wire:click="mountAction('comoAtender', { residente: {{ $n->residente_id }} })"
                        class="flex w-full items-start justify-between gap-3 px-4 py-3 text-start active:bg-gray-50 dark:active:bg-white/5">
                        <span class="min-w-0">
                            <span class="block font-semibold text-gray-950 dark:text-white">
                                @if ($n->incidencia_destacada)<span class="me-1 inline-block h-2 w-2 rounded-full bg-danger-500" title="Incidencia"></span>@endif
                                {{ $n->residente->nombre }} · Hab. {{ $n->residente->habitacion }}
                            </span>
                            <span class="block text-sm text-gray-700 dark:text-gray-300">{{ $n->resumenCorto() }}</span>
                            <span class="block text-xs text-gray-500">{{ $n->user?->name ? strtok($n->user->name, ' ').' '.mb_substr(explode(' ', $n->user->name)[1] ?? '', 0, 1).'.' : '' }} · {{ $n->created_at->format('H:i') }}</span>
                        </span>
                        <span aria-hidden="true" class="text-gray-400">›</span>
                    </button>
                </li>
            @empty
                <li class="px-4 py-3 text-sm text-gray-500">El turno anterior no dejó novedades.</li>
            @endforelse
        </ul>
    </section>

    {{-- 3. Lo secundario, plegado --}}
    <div class="space-y-2">
        @if ($vuelven->isNotEmpty())
            <details class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-gray-900" open>
                <summary class="cursor-pointer text-sm font-semibold">Vuelve hoy · {{ $vuelven->count() }}</summary>
                <ul class="mt-2 space-y-2">
                    @foreach ($vuelven as $a)
                        <li class="flex items-center justify-between gap-3 text-sm">
                            <span>{{ $a->residente->nombre }} · Hab. {{ $a->residente->habitacion }}</span>
                            <x-filament::button size="sm" color="gray" wire:click="haVuelto({{ $a->id }})">Ha vuelto</x-filament::button>
                        </li>
                    @endforeach
                </ul>
            </details>
        @endif
        <details class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-gray-900">
            <summary class="cursor-pointer text-sm font-semibold">Notas de compañeras · {{ $notas->count() }}</summary>
            <ul class="mt-2 space-y-2 text-sm">
                @forelse ($notas as $tn)
                    <li><span class="text-gray-500">{{ strtok($tn->user->name, ' ') }} · {{ $tn->fin->format('H:i') }}:</span> {{ $tn->nota_relevo }}</li>
                @empty
                    <li class="text-gray-500">Ninguna.</li>
                @endforelse
            </ul>
        </details>
        <details class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-gray-900">
            <summary class="cursor-pointer text-sm font-semibold">Sin novedades · {{ $sin->count() }}</summary>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $sin->map(fn ($r) => $r->nombre.' ('.$r->habitacion.')')->join(' · ') }}</p>
        </details>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
