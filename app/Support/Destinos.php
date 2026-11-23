<?php

namespace App\Support;

use App\Filament\Direccion\Pages\Ayuda;
use App\Filament\Direccion\Pages\Calidad;
use App\Filament\Direccion\Pages\Inicio;
use App\Filament\Direccion\Pages\MiResidencia;
use App\Filament\Direccion\Pages\Ocupacion;
use App\Filament\Direccion\Resources\AusenciaResource;
use App\Filament\Direccion\Resources\AuxiliarResource;
use App\Filament\Direccion\Resources\ResidenteResource;
use App\Filament\Direccion\Resources\ResumenResource;
use App\Models\Aviso;
use App\Models\Residente;
use App\Models\Resumen;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Navegación contextual de la dirección (23/11/2026): un solo catálogo de destinos para la paleta «Ir a…» (⌘K),
 * el asistente de Ayuda y los enlaces «Relacionado» de las fichas. Si cambia una pantalla, se cambia aquí y llega a los tres.
 * Cada destino: id, título, grupo, url, palabras con las que la directora lo buscaría.
 */
class Destinos
{
    /** @return array<int, array{id: string, titulo: string, grupo: string, url: string, claves: string}> */
    public static function fijos(): array
    {
        $filtro = fn (string $f) => ResidenteResource::getUrl('index', ['tableFilters' => [$f => ['isActive' => true]]]);

        return [
            ['id' => 'inicio', 'titulo' => 'Inicio · Hoy necesita tu decisión', 'grupo' => 'Ir a', 'url' => Inicio::getUrl(), 'claves' => 'inicio avisos hoy decidir pendiente portada'],
            ['id' => 'equipo', 'titulo' => 'Equipo', 'grupo' => 'Ir a', 'url' => AuxiliarResource::getUrl(), 'claves' => 'equipo auxiliares tecnicos cuidados personal trabajadoras'],
            ['id' => 'equipo-nueva', 'titulo' => 'Dar de alta a una auxiliar', 'grupo' => 'Hacer', 'url' => AuxiliarResource::getUrl('create'), 'claves' => 'alta nueva auxiliar añadir contratar pin codigo movil'],
            ['id' => 'equipo-sanitarios', 'titulo' => 'Enfermería y medicina', 'grupo' => 'Ir a', 'url' => AuxiliarResource::getUrl('index', ['activeTab' => 'sanitarios']), 'claves' => 'enfermeria enfermera medico medicina sanitarios horario'],
            ['id' => 'residentes', 'titulo' => 'Residentes', 'grupo' => 'Ir a', 'url' => ResidenteResource::getUrl(), 'claves' => 'residentes mayores fichas habitaciones'],
            ['id' => 'residente-nuevo', 'titulo' => 'Dar de alta a un residente', 'grupo' => 'Hacer', 'url' => ResidenteResource::getUrl('create'), 'claves' => 'alta nuevo residente ingreso añadir dictar ficha'],
            ['id' => 'planes-vencidos', 'titulo' => 'Planes personales vencidos', 'grupo' => 'Pendiente', 'url' => $filtro('plan_vencido'), 'claves' => 'plan personal vencido revision pai seis meses'],
            ['id' => 'sin-referencia', 'titulo' => 'Residentes sin profesional de referencia', 'grupo' => 'Pendiente', 'url' => $filtro('sin_referencia'), 'claves' => 'profesional referencia responsable plan asignar'],
            ['id' => 'sujecion', 'titulo' => 'Residentes sin anotar la sujeción', 'grupo' => 'Pendiente', 'url' => $filtro('sujecion_sin_anotar'), 'claves' => 'sujecion sujeciones barandilla cinturon'],
            ['id' => 'sin-emergencia', 'titulo' => 'Residentes sin contacto de emergencia', 'grupo' => 'Pendiente', 'url' => $filtro('sin_emergencia'), 'claves' => 'contacto emergencia familia telefono'],
            ['id' => 'ausencias', 'titulo' => 'Ausencias (fuera del centro)', 'grupo' => 'Ir a', 'url' => AusenciaResource::getUrl(), 'claves' => 'ausencia fuera hospital salida vuelve'],
            ['id' => 'calidad', 'titulo' => 'Calidad y auditoría', 'grupo' => 'Ir a', 'url' => Calidad::getUrl(), 'claves' => 'calidad auditoria une 158101 norma certificacion falta indicadores'],
            ['id' => 'familias', 'titulo' => 'Familias · resúmenes semanales', 'grupo' => 'Ir a', 'url' => ResumenResource::getUrl(), 'claves' => 'familias resumen semanal enviar aprobar email whatsapp'],
            ['id' => 'ocupacion', 'titulo' => 'Residencia y ocupación', 'grupo' => 'Ir a', 'url' => Ocupacion::getUrl(), 'claves' => 'ocupacion plazas libres habitaciones'],
            ['id' => 'mi-residencia', 'titulo' => 'Mi residencia', 'grupo' => 'Ir a', 'url' => MiResidencia::getUrl(), 'claves' => 'mi residencia datos plantas fecha auditoria piloto'],
            ['id' => 'ayuda', 'titulo' => 'Ayuda · Pregúntale a Velia', 'grupo' => 'Ir a', 'url' => Ayuda::getUrl(), 'claves' => 'ayuda dudas asistente preguntar voz'],
        ];
    }

    /** Destinos fijos + cada residente y cada persona del equipo (en una residencia de menos de 50 plazas caben en la paleta). */
    public static function paraPaleta(): array
    {
        $lista = self::fijos();
        foreach (Residente::activos()->orderBy('nombre')->get(['id', 'nombre', 'habitacion']) as $r) {
            $lista[] = ['id' => 'residente-'.$r->id, 'titulo' => $r->nombre, 'grupo' => 'Residentes', 'url' => ResidenteResource::getUrl('view', ['record' => $r]), 'claves' => 'hab '.$r->habitacion.' habitacion'];
        }
        foreach (User::whereIn('rol', ['auxiliar', 'sanitario'])->where('activo', true)->orderBy('name')->get(['id', 'name', 'rol']) as $u) {
            $lista[] = ['id' => 'equipo-'.$u->id, 'titulo' => $u->name, 'grupo' => 'Equipo', 'url' => AuxiliarResource::getUrl('view', ['record' => $u]), 'claves' => $u->rol];
        }

        return $lista;
    }

    public static function porId(string $id): ?array
    {
        return collect(self::fijos())->firstWhere('id', $id);
    }

    /** Búsqueda tolerante (sin tildes ni mayúsculas): devuelve los destinos que mejor encajan con la frase. */
    public static function buscar(string $frase, int $max = 3): array
    {
        $palabras = array_filter(explode(' ', self::normalizar($frase)), fn ($p) => mb_strlen($p) > 3);
        if (! $palabras) {
            return [];
        }

        return collect(self::paraPaleta())
            ->map(function ($d) use ($palabras) {
                $texto = self::normalizar($d['titulo'].' '.$d['claves']);
                $d['puntos'] = collect($palabras)->filter(fn ($p) => str_contains($texto, mb_substr($p, 0, 5)))->count();

                return $d;
            })
            ->filter(fn ($d) => $d['puntos'] > 0)
            ->sortByDesc('puntos')->take($max)->values()->all();
    }

    /** «Relacionado» en la ficha del residente: lo que la directora suele buscar a continuación, con el dato al lado. */
    public static function relacionadosResidente(Residente $r): array
    {
        $avisos = Aviso::abiertos()->where('residente_id', $r->id)->count();
        $resumen = Resumen::where('residente_id', $r->id)->latest('semana_inicio')->first();
        $fuera = $r->ausencias()->where('ha_vuelto', false)->exists();
        $lista = [
            ['titulo' => $avisos ? $avisos.' '.Str::plural('aviso', $avisos).' sin decidir' : 'Sin avisos pendientes', 'url' => Inicio::getUrl(), 'icono' => 'heroicon-o-bell-alert', 'destacar' => $avisos > 0],
            ['titulo' => 'Historial de registros', 'url' => ResidenteResource::getUrl('view', ['record' => $r]).'?activeRelationManager=0', 'icono' => 'heroicon-o-clock', 'destacar' => false],
            ['titulo' => $r->planResponsable ? 'Profesional de referencia: '.$r->planResponsable->name : 'Falta profesional de referencia', 'url' => $r->planResponsable ? AuxiliarResource::getUrl('view', ['record' => $r->planResponsable]) : Calidad::getUrl(), 'icono' => 'heroicon-o-user', 'destacar' => ! $r->planResponsable],
            ['titulo' => $resumen ? 'Resumen para la familia · '.ResumenResource::etiqueta($resumen) : 'Aún no hay resumen para la familia', 'url' => ResumenResource::getUrl(), 'icono' => 'heroicon-o-envelope', 'destacar' => false],
            ['titulo' => $fuera ? 'Ahora fuera del centro' : 'Ausencias', 'url' => AusenciaResource::getUrl(), 'icono' => 'heroicon-o-arrow-right-start-on-rectangle', 'destacar' => $fuera],
            ['titulo' => 'Qué falta para la auditoría', 'url' => Calidad::getUrl(), 'icono' => 'heroicon-o-shield-check', 'destacar' => false],
        ];

        return $lista;
    }

    public static function normalizar(string $s): string
    {
        return trim(preg_replace('/[^a-z0-9 ]/', ' ', Str::lower(Str::ascii($s))));
    }
}
