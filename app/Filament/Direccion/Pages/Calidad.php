<?php

namespace App\Filament\Direccion\Pages;

use App\Filament\Direccion\Concerns\MigasDePan;
use App\Support\PendientesAuditoria;
use App\Filament\Direccion\Resources\ResidenteResource;
use App\Filament\Direccion\Resources\ResumenResource;
use App\Models\Correccion;
use App\Models\Registro;
use App\Models\Residencia;
use App\Models\Residente;
use App\Models\Resumen;
use App\Models\Turno;
use Filament\Pages\Page;
use App\Models\SugerenciaIa;
use App\Support\Ia\Sugerencias;

/**
 * 4 · Calidad y auditoría (wireframe «V2 · Calidad y auditoría»).
 * Jerarquía para la dirección: 1) cuánto falta para la auditoría, 2) qué apartado
 * necesita algo de ella hoy (con la acción al lado), 3) indicadores («Cómo va la semana» está en Inicio) (Ejemplo, H2).
 */
class Calidad extends Page
{
    use MigasDePan;

    protected static ?string $navigationLabel = 'Calidad y auditoría';

    protected static ?string $title = 'Calidad y auditoría';

    protected static ?string $slug = 'calidad';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.direccion.pages.calidad';

    public function getSubheading(): ?string
    {
        $fecha = Residencia::actual()->proxima_auditoria;
        if (! $fecha) {
            return 'Añade la fecha de la próxima auditoría en Mi residencia.';
        }

        $n = PendientesAuditoria::resumen()['total'];

        return 'Auditoría UNE 158101: '.$fecha->locale('es')->isoFormat('D MMM YYYY').' · en '.(int) now()->startOfDay()->diffInDays($fecha).' días · '.($n ? $n.' '.($n === 1 ? 'pendiente' : 'pendientes') : 'todo al día con lo que mide Velia');
    }

    /** «Velia te sugiere» por dónde empezar. Se oculta hasta mañana si la directora dice «Ahora no». */
    public function sugerencia(): ?array
    {
        if (session('velia.calidad.sin_sugerencia') === today()->toDateString()) {
            return null;
        }

        return Sugerencias::porDondeEmpezar(PendientesAuditoria::items());
    }

    public function seguirSugerencia(): void
    {
        if ($s = $this->sugerencia()) {
            SugerenciaIa::anotar('calidad.empezar', $s['texto'], 'aceptada');
            $this->redirect($s['url']);
        }
    }

    public function descartarSugerencia(): void
    {
        if ($s = $this->sugerencia()) {
            SugerenciaIa::anotar('calidad.empezar', $s['texto'], 'descartada');
        }
        session(['velia.calidad.sin_sugerencia' => today()->toDateString()]);
    }

    /** Nueva versión (26/11/2026): vista con una sola lista por apartado, sugerencias en chips e indicadores compartidos con el Inicio. */
    public function getView(): string
    {
        return \App\Support\VersionH::activa() ? 'filament.direccion-h.calidad' : $this->view;
    }

    /**
     * Una sola lista (comentario de Pablo, acordado con Ana): cada apartado de la norma con su estado, lo que falta
     * (los mismos pendientes que el Inicio) y el botón para resolverlo. Abiertos solo los que tienen algo pendiente.
     */
    public function apartadosConPendientes(): array
    {
        $items = collect(PendientesAuditoria::items())->groupBy('apartado');

        return array_map(fn ($a) => $a + ['pendientes' => $items->get($a['clave'], collect())->values()->all()], $this->apartados());
    }

    /** Preguntas sugeridas a Velia según lo que falta (chips que abren el asistente con esa pregunta). */
    public function preguntasSugeridas(): array
    {
        $lista = [];
        foreach (PendientesAuditoria::items() as $i) {
            $lista[] = match ($i['apartado']) {
                'plan' => '¿Qué pide la norma del plan personal y cada cuánto se revisa?',
                'sanitaria' => '¿Qué tengo que anotar de las sujeciones?',
                'familias' => '¿Por qué hace falta un contacto de emergencia?',
                'ingreso' => '¿Qué es la valoración inicial y cuándo toca?',
                default => '¿Qué me falta para la auditoría?',
            };
        }
        $lista[] = '¿Qué me falta para la auditoría?';
        $lista[] = '¿Qué mirará la certificadora el primer día?';

        return array_slice(array_values(array_unique($lista)), 0, 3);
    }

    public function pendientes(): array
    {
        return PendientesAuditoria::resumen();
    }

    /**
     * Apartados que comprueba la certificadora (Anexo I del esquema ENAC RDE-37, ver research/une-158101-requisitos-para-mvp.md).
     * Estado: «al_dia», «falta» (con cuántos), «no_mide» (Velia aún no lo registra) o «otro» (lo lleva otro: programa de gestión o consultora).
     */
    public function apartados(): array
    {
        $P = fn (string $a) => PendientesAuditoria::porApartado($a);
        $turnos = Turno::whereNotNull('fin')->where('fin', '>=', now()->subDays(30));
        $tTotal = (clone $turnos)->count();
        $tCompletos = (clone $turnos)->whereNull('motivo_cierre')->count();
        $porAprobar = Resumen::where('estado', '!=', 'enviado')->count();
        $caidas = Registro::where('incidencia_destacada', true)->where('tipo_incidencia', 'like', 'Caídas%')->where('created_at', '>=', now()->subDays(30))->count();
        $piel = Registro::where('incidencia_destacada', true)->where('tipo_incidencia', 'like', 'Piel%')->where('created_at', '>=', now()->subDays(30))->count();
        $bajas = Residente::where('estado', 'baja')->count();
        $estado = fn (int $n) => $n > 0 ? ['falta', $n] : ['al_dia', 0];

        return [
            ['n' => '4', 'clave' => 'ingreso', 'titulo' => 'Ingreso', 'pide' => 'Valoración inicial en el primer mes', 'dato' => 'Fecha de ingreso y plan inicial de cada residente', 'estado' => $estado($P('ingreso'))],
            ['n' => '5.2–5.3', 'clave' => 'plan', 'titulo' => 'Plan personal', 'pide' => 'Revisión cada 6 meses y profesional de referencia', 'dato' => 'Fechas de revisión y profesional de referencia. «Plan comunicado a la familia»: Velia aún no lo registra', 'estado' => $estado($P('plan'))],
            ['n' => '5.4', 'clave' => 'cuidados', 'titulo' => 'Cuidados personales', 'pide' => 'Registro de cuidados e incidencias', 'dato' => "{$tCompletos} de {$tTotal} turnos cerrados completos en los últimos 30 días", 'estado' => $estado($P('cuidados'))],
            ['n' => '5.5', 'clave' => 'sanitaria', 'titulo' => 'Atención sanitaria', 'pide' => 'Caídas, úlceras, dietas y sujeciones', 'dato' => "{$caidas} caídas y {$piel} incidencias de piel registradas en 30 días. Medicación y controles: los lleva tu programa de gestión", 'estado' => $estado($P('sanitaria'))],
            ['n' => '5.6', 'clave' => 'psicosocial', 'titulo' => 'Atención psicosocial', 'pide' => 'Registro de actividades del último año', 'dato' => 'Velia aún no registra las actividades', 'estado' => ['no_mide', 0]],
            ['n' => '6', 'clave' => 'baja', 'titulo' => 'Baja', 'pide' => 'Baja con motivo', 'dato' => $bajas ? "{$bajas} bajas, todas con motivo" : 'Sin bajas registradas', 'estado' => ['al_dia', 0]],
            ['n' => 'Orden', 'clave' => 'familias', 'titulo' => 'Comunicación con las familias', 'pide' => 'Canal telemático permanente', 'dato' => Resumen::where('estado', 'enviado')->count().' resúmenes enviados · '.$porAprobar.' por aprobar', 'estado' => $estado($P('familias'))],
            ['n' => '10', 'clave' => 'quejas', 'titulo' => 'Quejas y sugerencias', 'pide' => 'Registro y respuesta', 'dato' => 'Velia aún no registra quejas y sugerencias', 'estado' => ['no_mide', 0]],
            ['n' => '7 · 9', 'clave' => 'otro', 'titulo' => 'Personal e instalaciones', 'pide' => 'Formación, mantenimiento, cocina, limpieza', 'dato' => 'Lo lleva tu consultora o tu programa de gestión', 'estado' => ['otro', 0]],
        ];
    }
}
