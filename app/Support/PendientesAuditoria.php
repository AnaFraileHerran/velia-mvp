<?php

namespace App\Support;

use App\Filament\Direccion\Pages\Inicio;
use App\Filament\Direccion\Resources\ResidenteResource;
use App\Models\Aviso;
use App\Models\Residente;

/**
 * Lo que falta para la auditoría y Velia ya sabe medir (23/11/2026). Una sola fuente para el Inicio y Calidad,
 * para que las dos pantallas digan lo mismo. No inventa «apartados» de la UNE: solo lo que está en los datos.
 */
class PendientesAuditoria
{
    /** @return array<int, array{texto: string, n: int, accion: string, url: string, apartado: string}> */
    public static function items(): array
    {
        $activos = Residente::activos();
        $f = fn (string $filtro) => ResidenteResource::getUrl('index', ['tableFilters' => [$filtro => ['isActive' => true]]]);

        $inicial = (clone $activos)->whereNotNull('fecha_ingreso')->where('fecha_ingreso', '<=', now()->subMonth())->whereNull('plan_ultima_revision')->count();
        $vencidos = (clone $activos)->whereDate('plan_proxima_revision', '<', today())->get();
        $sinRef = (clone $activos)->whereNull('plan_responsable_id')->count();
        $sinEmergencia = (clone $activos)->whereDoesntHave('contactos', fn ($q) => $q->where('emergencia', true))->count();
        $sujecion = (clone $activos)->where('sujecion_anotada', false)->count();
        $avisos = Aviso::abiertos()->whereIn('tipo', ['patron', 'incidencia', 'revision_vencida'])->count();

        $lista = [
            ['n' => $inicial, 'texto' => 'sin valoración inicial pasado el primer mes', 'accion' => 'Revisar', 'url' => ResidenteResource::getUrl(), 'apartado' => 'ingreso'],
            ['n' => $vencidos->count(), 'texto' => $vencidos->count() === 1 ? 'plan personal vencido · '.$vencidos->first()->nombre : 'planes personales vencidos', 'accion' => $vencidos->count() === 1 ? 'Abrir ficha' : 'Ver cuáles',
                'url' => $vencidos->count() === 1 ? ResidenteResource::getUrl('view', ['record' => $vencidos->first()]) : $f('plan_vencido'), 'apartado' => 'plan'],
            ['n' => $sinRef, 'texto' => $sinRef === 1 ? 'residente sin profesional de referencia' : 'residentes sin profesional de referencia', 'accion' => 'Asignar', 'url' => $f('sin_referencia'), 'apartado' => 'plan'],
            ['n' => $sujecion, 'texto' => $sujecion === 1 ? 'residente sin anotar si usa sujeción' : 'residentes sin anotar si usan sujeción', 'accion' => 'Completar', 'url' => $f('sujecion_sin_anotar'), 'apartado' => 'sanitaria'],
            ['n' => $sinEmergencia, 'texto' => $sinEmergencia === 1 ? 'residente sin contacto de emergencia' : 'residentes sin contacto de emergencia', 'accion' => 'Completar', 'url' => $f('sin_emergencia'), 'apartado' => 'familias'],
            ['n' => $avisos, 'texto' => $avisos === 1 ? 'aviso de cuidado sin revisar' : 'avisos de cuidado sin revisar', 'accion' => 'Ir a Inicio', 'url' => Inicio::getUrl(), 'apartado' => 'cuidados'],
        ];

        return array_values(array_filter($lista, fn ($i) => $i['n'] > 0));
    }

    public static function resumen(): array
    {
        $items = self::items();

        return ['items' => $items, 'total' => array_sum(array_column($items, 'n'))];
    }

    public static function porApartado(string $apartado): int
    {
        return array_sum(array_column(array_filter(self::items(), fn ($i) => $i['apartado'] === $apartado), 'n'));
    }
}
