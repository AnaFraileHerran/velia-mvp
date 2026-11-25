<?php

namespace App\Filament\Direccion\Widgets;

use App\Filament\Direccion\Pages\Calidad;
use App\Filament\Direccion\Resources\AusenciaResource;
use App\Filament\Direccion\Resources\ResidenteResource;
use App\Filament\Direccion\Resources\ResumenResource;
use App\Models\Ausencia;
use App\Models\Residencia;
use App\Models\Residente;
use App\Models\Resumen;
use App\Support\PendientesAuditoria;
use Carbon\Carbon;
use App\Models\Pregunta;

/**
 * Flujo nuevo (versión H, 25/11/2026, comentarios de Pablo en INI-01): la dirección trabaja con previsión
 * semanal, mensual o trimestral, no solo con el día. «Próximamente» junta lo que vence en el horizonte elegido
 * con una escala de importancia (alta, media, baja). La auditoría sale de la cabecera y se queda aquí como hito fijo.
 * Hereda de EstaSemana las preguntas al turno (leer respuestas), que siguen arriba de la lista.
 */
class ProximamenteH extends EstaSemana
{
    protected string $view = 'filament.direccion.widgets.proximamente-h';

    protected int|string|array $columnSpan = ['xl' => 1];

    public string $horizonte = 'semana';

    public const HORIZONTES = ['semana' => ['Semana', 7], 'mes' => ['Mes', 30], 'trimestre' => ['Trimestre', 90]];

    public function verHorizonte(string $h): void
    {
        $this->horizonte = array_key_exists($h, self::HORIZONTES) ? $h : 'semana';
    }

    protected function getViewData(): array
    {
        $hasta = today()->addDays(self::HORIZONTES[$this->horizonte][1]);

        return ['hito' => $this->hito(), 'items' => $this->items($hasta), 'hasta' => $hasta,
            'hayPreguntas' => Pregunta::abiertas()->exists()];
    }

    /** La auditoría: siempre visible si hay fecha; su importancia sube según se acerca y según lo que falta. */
    private function hito(): ?array
    {
        $fecha = Residencia::actual()?->proxima_auditoria;
        if (! $fecha) {
            return ['titulo' => 'Fija la fecha de la próxima auditoría', 'detalle' => 'Sin fecha no podemos avisarte con tiempo.', 'url' => Calidad::getUrl(), 'nivel' => 'media', 'dias' => null];
        }
        $dias = (int) today()->diffInDays($fecha, false);
        $pendientes = PendientesAuditoria::resumen()['total'];

        return [
            'titulo' => 'Auditoría UNE 158101 · '.$fecha->locale('es')->isoFormat('D MMM YYYY'),
            'detalle' => ($dias >= 0 ? 'Faltan '.$dias.' días' : 'Fecha pasada').' · '.($pendientes ? $pendientes.' '.($pendientes === 1 ? 'cosa pendiente' : 'cosas pendientes') : 'todo al día'),
            'url' => Calidad::getUrl(),
            'nivel' => $dias <= 30 && $pendientes ? 'alta' : ($pendientes ? 'media' : 'baja'),
            'dias' => $dias,
        ];
    }

    /** @return array<int, array{titulo: string, fecha: ?Carbon, nivel: string, url: string}> */
    private function items(Carbon $hasta): array
    {
        $lista = [];
        $activos = Residente::activos();

        foreach ((clone $activos)->whereNotNull('plan_proxima_revision')->whereDate('plan_proxima_revision', '<=', $hasta)->get() as $r) {
            $vencido = $r->plan_proxima_revision->isPast() && ! $r->plan_proxima_revision->isToday();
            $lista[] = ['titulo' => ($vencido ? 'Plan personal vencido · ' : 'Revisión del plan · ').$r->nombre, 'fecha' => $r->plan_proxima_revision,
                'nivel' => $vencido ? 'alta' : 'media', 'url' => ResidenteResource::getUrl('view', ['record' => $r])];
        }

        foreach ((clone $activos)->whereNotNull('fecha_ingreso')->whereNull('plan_ultima_revision')->get() as $r) {
            $limite = $r->fecha_ingreso->copy()->addMonth();
            if ($limite->lte($hasta)) {
                $lista[] = ['titulo' => 'Valoración inicial · '.$r->nombre, 'fecha' => $limite, 'nivel' => $limite->isPast() ? 'alta' : 'media',
                    'url' => ResidenteResource::getUrl('view', ['record' => $r])];
            }
        }

        $porRevisar = Resumen::where('estado', 'por_revisar')->count();
        if ($porRevisar) {
            $lista[] = ['titulo' => $porRevisar.' '.($porRevisar === 1 ? 'resumen' : 'resúmenes').' para las familias por revisar', 'fecha' => today()->endOfWeek(),
                'nivel' => 'media', 'url' => ResumenResource::getUrl()];
        }

        foreach (Ausencia::where('ha_vuelto', false)->whereDate('sale', '>', today())->whereDate('sale', '<=', $hasta)->with('residente')->get() as $a) {
            $lista[] = ['titulo' => 'Salida prevista · '.$a->residente?->nombre, 'fecha' => $a->sale, 'nivel' => 'baja', 'url' => AusenciaResource::getUrl()];
        }

        $sinEmergencia = (clone $activos)->whereDoesntHave('contactos', fn ($q) => $q->where('emergencia', true))->count();
        if ($sinEmergencia) {
            $lista[] = ['titulo' => $sinEmergencia.' '.($sinEmergencia === 1 ? 'residente' : 'residentes').' sin contacto de emergencia', 'fecha' => null, 'nivel' => 'media',
                'url' => ResidenteResource::getUrl('index', ['tableFilters' => ['sin_emergencia' => ['isActive' => true]]])];
        }

        $peso = ['alta' => 0, 'media' => 1, 'baja' => 2];
        usort($lista, fn ($a, $b) => [$peso[$a['nivel']], $a['fecha']?->timestamp ?? PHP_INT_MAX] <=> [$peso[$b['nivel']], $b['fecha']?->timestamp ?? PHP_INT_MAX]);

        return $lista;
    }
}
