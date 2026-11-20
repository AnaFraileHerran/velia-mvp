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

        return 'Próxima auditoría UNE 158101: '.$fecha->locale('es')->isoFormat('D MMM YYYY').' (en '.(int) now()->startOfDay()->diffInDays($fecha).' días)';
    }

    /** Apartados ordenados: primero los que piden algo. */
    public function apartados(): array
    {
        $ingresos = Residente::activos()->where('fecha_ingreso', '>=', now()->subMonths(3))->get();
        $ingresosCompletos = $ingresos->filter(fn ($record) => $record->alergias !== null && $record->contactos()->exists() && $record->plan_proxima_revision)->count();
        $turnos = Turno::whereNotNull('fin')->where('fin', '>=', now()->subDays(30));
        $turnosTotal = (clone $turnos)->count();
        $turnosCompletos = (clone $turnos)->whereNull('motivo_cierre')->count();
        $porAprobar = Resumen::where('estado', '!=', 'enviado')->count();

        $lista = [
            [
                'titulo' => 'Incorporación de personas usuarias',
                'dato' => "{$ingresosCompletos} de {$ingresos->count()} ingresos con ficha, contacto y plan",
                'ok' => $ingresosCompletos === $ingresos->count(),
                'accion' => ['Completa las fichas', ResidenteResource::getUrl()],
            ],
            [
                'titulo' => 'Comunicación con las familias',
                'dato' => Resumen::where('estado', 'enviado')->count().' resúmenes enviados · '.$porAprobar.' por aprobar',
                'ok' => $porAprobar === 0,
                'accion' => ['Revisar resúmenes', ResumenResource::getUrl()],
            ],
            [
                'titulo' => 'Registro en aplicación informática',
                'dato' => "{$turnosCompletos} de {$turnosTotal} turnos cerrados completos",
                'ok' => $turnosTotal === 0 || $turnosCompletos === $turnosTotal,
                'accion' => null,
            ],
            [
                'titulo' => 'Atención de personas usuarias',
                'dato' => Registro::where('estado', '!=', 'borrador')->count().' registros de turno · '.Correccion::count().' correcciones con motivo',
                'ok' => true,
                'accion' => null,
            ],
        ];
        usort($lista, fn ($a, $b) => $a['ok'] <=> $b['ok']);

        return $lista;
    }
}
