@php
    $u = $getRecord();
    $asignados = \App\Models\Aviso::asignadosA($u->id)->with('residente')->get();
    $sinResponder = \App\Models\Pregunta::abiertas()->whereNull('respuesta')
        ->whereIn('residente_id', \App\Models\Residente::when($u->planta_id, fn ($q) => $q->where('planta_id', $u->planta_id))->pluck('id'))
        ->count();
@endphp
<ul class="divide-y divide-gray-100 text-sm dark:divide-white/10">
    @foreach ($asignados as $a)
        <li class="flex flex-wrap items-center justify-between gap-2 py-2">
            <span>{{ $a->titulo }}@if ($a->residente) · {{ $a->residente->nombre }}@endif</span>
            <x-filament::badge :color="$a->vencido() ? 'danger' : 'gray'">
                {{ $a->vencido() ? 'Vencido el '.$a->vence_el->format('d/m') : ($a->vence_el ? 'Para el '.$a->vence_el->format('d/m') : 'Sin plazo') }}
            </x-filament::badge>
        </li>
    @endforeach
    @if ($sinResponder)
        <li class="py-2">{{ $sinResponder }} {{ $sinResponder === 1 ? 'pregunta de la dirección sin responder' : 'preguntas de la dirección sin responder' }} en su planta</li>
    @endif
    @if ($asignados->isEmpty() && ! $sinResponder)
        <li class="py-2 text-gray-500">Nada pendiente.</li>
    @endif
</ul>
