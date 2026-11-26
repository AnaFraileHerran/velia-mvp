<?php

namespace App\Support;

use App\Filament\Direccion\Resources\AuxiliarResource;
use App\Filament\Direccion\Resources\ResidenteResource;
use App\Filament\Direccion\Pages\Inicio;
use App\Models\Aviso;
use App\Models\Registro;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Buscador de la dirección (Nueva versión, 26/11/2026; comentario de Pablo y Antonio, patrón de Santa Plata).
 * Combina 1) búsqueda clásica por coincidencia (residentes, equipo, avisos, pantallas y tareas), 2) búsqueda por significado
 * sencilla (palabras de la residencia: «caídas», «fuera», «sin plan», «PIN»…) y 3) un resumen de una línea con lo encontrado.
 * Todo se calcula aquí, con los datos de la residencia: no se envía nada a ningún proveedor de IA (regla 10). El resumen es «Ejemplo»
 * hasta decidir si se usa un proveedor real con datos de residentes (decisión pendiente de Ana).
 */
class Buscador
{
    public const POR_GRUPO = 4;

    public static function buscar(string $q): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return ['resumen' => null, 'grupos' => []];
        }
        $n = fn (string $s) => mb_strtolower(Str::ascii($s));
        $nq = $n($q);
        $palabras = array_filter(explode(' ', $nq), fn ($p) => mb_strlen($p) > 1);
        $coincide = fn (string $texto) => collect($palabras)->every(fn ($p) => str_contains($n($texto), $p));
        $grupos = [];

        // 1 · Residentes (nombre, habitación o planta)
        $residentes = Residente::activos()->with('planta')->orderBy('nombre')->get()
            ->filter(fn ($r) => $coincide($r->nombre.' hab '.$r->habitacion.' '.($r->planta?->nombre ?? '')));
        // Por significado: «fuera», «hospital», «salida» → quién está fuera
        if (preg_match('/\b(fuera|hospital|salida|ausen)/', $nq)) {
            $residentes = $residentes->merge(Residente::where('estado', 'fuera')->get())->unique('id');
        }
        if (preg_match('/\b(sin plan|plan|revision|vencid)/', $nq)) {
            $residentes = $residentes->merge(Residente::activos()->whereDate('plan_proxima_revision', '<', today())->get())->unique('id');
        }
        if ($residentes->isNotEmpty()) {
            $grupos[] = ['nombre' => 'Residentes', 'total' => $residentes->count(),
                'items' => $residentes->take(self::POR_GRUPO)->map(fn ($r) => ['titulo' => $r->nombre, 'detalle' => ($r->planta?->nombre ?? '—').' · Hab. '.$r->habitacion.' · '.$r->etiquetaEstado(),
                    'url' => ResidenteResource::getUrl('view', ['record' => $r])])->values()->all(),
                'verTodos' => $residentes->count() > self::POR_GRUPO ? ResidenteResource::getUrl('index', ['tableSearch' => $q]) : null];
        }

        // 2 · Equipo
        $equipo = User::whereIn('rol', ['auxiliar', 'sanitario', 'otro'])->where('activo', true)->with('planta')->orderBy('name')->get()
            ->filter(fn ($u) => $coincide($u->name.' '.$u->etiquetaPuesto().' '.($u->planta?->nombre ?? '')));
        if ($equipo->isNotEmpty()) {
            $grupos[] = ['nombre' => 'Equipo', 'total' => $equipo->count(),
                'items' => $equipo->take(self::POR_GRUPO)->map(fn ($u) => ['titulo' => $u->name, 'detalle' => $u->rol === 'auxiliar' ? AuxiliarResource::lineaEstado($u) : $u->etiquetaPuesto(),
                    'url' => AuxiliarResource::getUrl('view', ['record' => $u])])->values()->all(),
                'verTodos' => $equipo->count() > self::POR_GRUPO ? AuxiliarResource::getUrl('index', ['tableSearch' => $q]) : null];
        }

        // 3 · Incidencias recientes (por significado: «caída», «piel», «úlcera», «no come»…)
        $tipo = match (true) {
            (bool) preg_match('/(caid|mare|golpe)/', $nq) => 'Caídas',
            (bool) preg_match('/(piel|ulcera|herida)/', $nq) => 'Piel',
            (bool) preg_match('/(come|comida|bebe|hidrata|aliment)/', $nq) => 'Alimentación',
            (bool) preg_match('/(conducta|agresiv|agita|nervios)/', $nq) => 'Conducta',
            (bool) preg_match('/incidencia/', $nq) => '',
            default => null,
        };
        $incidencias = collect();
        if ($tipo !== null) {
            $incidencias = Registro::with('residente')->where('incidencia_destacada', true)->where('created_at', '>=', now()->subDays(7))
                ->when($tipo !== '', fn ($w) => $w->where('tipo_incidencia', 'like', $tipo.'%'))->latest()->get();
            if ($incidencias->isNotEmpty()) {
                $grupos[] = ['nombre' => 'Incidencias de los últimos 7 días', 'total' => $incidencias->count(),
                    'items' => $incidencias->take(self::POR_GRUPO)->map(fn ($r) => ['titulo' => ($r->tipo_incidencia ?: 'Incidencia').' · '.$r->residente?->nombre,
                        'detalle' => $r->created_at->locale('es')->isoFormat('ddd D, HH:mm').' · '.Str::limit($r->resumenCorto(), 60),
                        'url' => ResidenteResource::getUrl('view', ['record' => $r->residente_id])])->values()->all(), 'verTodos' => null];
            }
        }

        // 4 · Avisos abiertos
        $avisos = Aviso::abiertos()->with('residente')->latest()->get()->filter(fn ($a) => $coincide($a->titulo.' '.($a->residente?->nombre ?? '')));
        if ($avisos->isNotEmpty()) {
            $grupos[] = ['nombre' => 'Avisos sin decidir', 'total' => $avisos->count(),
                'items' => $avisos->take(self::POR_GRUPO)->map(fn ($a) => ['titulo' => $a->titulo, 'detalle' => 'En Inicio', 'url' => Inicio::getUrl()])->values()->all(), 'verTodos' => null];
        }

        // 5 · Pantallas y tareas (catálogo de destinos)
        $destinos = collect(Destinos::fijos())->filter(fn ($d) => $coincide($d['titulo'].' '.$d['claves']));
        if (preg_match('/\b(pin|entrar|contrasena|clave)/', $nq)) {
            $destinos->push(['id' => 'pin', 'titulo' => 'Una auxiliar no puede entrar: abre su ficha en Equipo › «No puede entrar»', 'grupo' => 'Hacer', 'url' => AuxiliarResource::getUrl(), 'claves' => '']);
        }
        if ($destinos->isNotEmpty()) {
            $grupos[] = ['nombre' => 'Pantallas y tareas', 'total' => $destinos->count(),
                'items' => $destinos->take(self::POR_GRUPO)->map(fn ($d) => ['titulo' => $d['titulo'], 'detalle' => $d['grupo'], 'url' => $d['url']])->values()->all(), 'verTodos' => null];
        }

        return ['resumen' => self::resumen($q, $grupos, $residentes, $incidencias), 'grupos' => $grupos];
    }

    /** Una línea que cuenta lo encontrado, como el bloque de arriba del buscador de Santa Plata. */
    private static function resumen(string $q, array $grupos, $residentes, $incidencias): ?string
    {
        if (! $grupos) {
            return "No encuentro nada con «{$q}». Prueba con un nombre, una habitación o pregúntaselo a Velia.";
        }
        $formas = ['Residentes' => ['residente', 'residentes'], 'Equipo' => ['persona del equipo', 'personas del equipo'],
            'Incidencias de los últimos 7 días' => ['incidencia esta semana', 'incidencias esta semana'],
            'Avisos sin decidir' => ['aviso sin decidir', 'avisos sin decidir'], 'Pantallas y tareas' => ['pantalla o tarea', 'pantallas o tareas']];
        $partes = array_map(fn ($g) => $g['total'].' '.($formas[$g['nombre']][$g['total'] === 1 ? 0 : 1] ?? mb_strtolower($g['nombre'])), $grupos);
        $ultima = array_pop($partes);
        $txt = 'Para «'.$q.'»: '.($partes ? implode(', ', $partes).' y ' : '').$ultima.'.';
        if ($residentes->count() === 1) {
            $r = $residentes->first();
            $txt .= ' '.$r->nombre.' está '.mb_strtolower($r->etiquetaEstado()).($r->plan_proxima_revision?->isPast() ? ' y tiene el plan personal vencido' : '').'.';
        }
        if ($incidencias->count() > 1) {
            $txt .= ' La más reciente: '.$incidencias->first()->residente?->nombre.', '.$incidencias->first()->created_at->locale('es')->isoFormat('dddd').'.';
        }

        return $txt;
    }
}
