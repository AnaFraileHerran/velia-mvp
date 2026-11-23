<?php

namespace App\Filament\Direccion\Pages;

use App\Filament\Direccion\Resources\ResidenteResource;
use App\Filament\Direccion\Resources\ResumenResource;
use App\Models\Correccion;
use App\Models\Registro;
use App\Models\Residencia;
use App\Models\Residente;
use App\Models\Resumen;
use App\Models\Turno;
use Filament\Pages\Page;

/**
 * 4 · Calidad y auditoría (wireframe «V2 · Calidad y auditoría»).
 * Jerarquía para la dirección: 1) cuánto falta para la auditoría, 2) qué apartado
 * necesita algo de ella hoy (con la acción al lado), 3) indicadores («Cómo va la semana» está en Inicio) (Ejemplo, H2).
 */
class Calidad extends Page
{
    use \App\Filament\Direccion\Concerns\MigasDePan;

    protected static ?string $navigationLabel = 'Calidad y auditoría';

    protected static ?string $title = 'Calidad y auditoría';

    protected static ?string $slug = 'calidad';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.direccion.pages.calidad';

    public function getSubheading(): ?string
    {
        $fecha = Residencia::actual()->proxima_auditoria;
        if (! $fecha) {
            return 'Añade la fecha de la próxima auditoría en Mi residencia.';
        }

        $n = \App\Support\PendientesAuditoria::resumen()['total'];

        return 'Auditoría UNE 158101: '.$fecha->locale('es')->isoFormat('D MMM YYYY').' · en '.(int) now()->startOfDay()->diffInDays($fecha).' días · '.($n ? $n.' '.($n === 1 ? 'pendiente' : 'pendientes') : 'todo al día con lo que mide Velia');
    }

    public function pendientes(): array
    {
        return \App\Support\PendientesAuditoria::resumen();
    }

    /**
     * Apartados que comprueba la certificadora (Anexo I del esquema ENAC RDE-37, ver research/une-158101-requisitos-para-mvp.md).
     * Estado: «al_dia», «falta» (con cuántos), «no_mide» (Velia aún no lo registra) o «otro» (lo lleva otro: programa de gestión o consultora).
     */
    public function apartados(): array
    {
        $P = fn (string $a) => \App\Support\PendientesAuditoria::porApartado($a);
        $turnos = Turno::whereNotNull('fin')->where('fin', '>=', now()->subDays(30));
        $tTotal = (clone $turnos)->count();
        $tCompletos = (clone $turnos)->whereNull('motivo_cierre')->count();
        $porAprobar = Resumen::where('estado', '!=', 'enviado')->count();
        $caidas = Registro::where('incidencia_destacada', true)->where('tipo_incidencia', 'like', 'Caídas%')->where('created_at', '>=', now()->subDays(30))->count();
        $piel = Registro::where('incidencia_destacada', true)->where('tipo_incidencia', 'like', 'Piel%')->where('created_at', '>=', now()->subDays(30))->count();
        $bajas = Residente::where('estado', 'baja')->count();
        $estado = fn (int $n) => $n > 0 ? ['falta', $n] : ['al_dia', 0];

        return [
            ['n' => '4', 'titulo' => 'Ingreso', 'pide' => 'Valoración inicial en el primer mes', 'dato' => 'Fecha de ingreso y plan inicial de cada residente', 'estado' => $estado($P('ingreso'))],
            ['n' => '5.2–5.3', 'titulo' => 'Plan personal', 'pide' => 'Revisión cada 6 meses y profesional de referencia', 'dato' => 'Fechas de revisión y profesional de referencia. «Plan comunicado a la familia»: Velia aún no lo registra', 'estado' => $estado($P('plan'))],
            ['n' => '5.4', 'titulo' => 'Cuidados personales', 'pide' => 'Registro de cuidados e incidencias', 'dato' => "{$tCompletos} de {$tTotal} turnos cerrados completos en los últimos 30 días", 'estado' => $estado($P('cuidados'))],
            ['n' => '5.5', 'titulo' => 'Atención sanitaria', 'pide' => 'Caídas, úlceras, dietas y sujeciones', 'dato' => "{$caidas} caídas y {$piel} incidencias de piel registradas en 30 días. Medicación y controles: los lleva tu programa de gestión", 'estado' => $estado($P('sanitaria'))],
            ['n' => '5.6', 'titulo' => 'Atención psicosocial', 'pide' => 'Registro de actividades del último año', 'dato' => 'Velia aún no registra las actividades', 'estado' => ['no_mide', 0]],
            ['n' => '6', 'titulo' => 'Baja', 'pide' => 'Baja con motivo', 'dato' => $bajas ? "{$bajas} bajas, todas con motivo" : 'Sin bajas registradas', 'estado' => ['al_dia', 0]],
            ['n' => 'Orden', 'titulo' => 'Comunicación con las familias', 'pide' => 'Canal telemático permanente', 'dato' => Resumen::where('estado', 'enviado')->count().' resúmenes enviados · '.$porAprobar.' por aprobar', 'estado' => $estado($P('familias'))],
            ['n' => '10', 'titulo' => 'Quejas y sugerencias', 'pide' => 'Registro y respuesta', 'dato' => 'Velia aún no registra quejas y sugerencias', 'estado' => ['no_mide', 0]],
            ['n' => '7 · 9', 'titulo' => 'Personal e instalaciones', 'pide' => 'Formación, mantenimiento, cocina, limpieza', 'dato' => 'Lo lleva tu consultora o tu programa de gestión', 'estado' => ['otro', 0]],
        ];
    }
}
