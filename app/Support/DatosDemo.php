<?php

namespace App\Support;

use App\Models\Registro;
use App\Models\Residencia;
use App\Models\Residente;
use App\Models\Turno;
use App\Models\User;

use Illuminate\Support\Carbon;

/**
 * Datos ficticios para que «Cómo va la semana» tenga contenido en la demo (Residencia Los Almendros):
 * 14 días de turnos cerrados y registros, con incidencias, más los registros de hoy (~80 % de los residentes).
 * Solo en local y solo si faltan: se rellena una vez al día al abrir Velia. Nunca en una residencia real.
 */
class DatosDemo
{
    private const TEXTOS = [
        ['Ha desayunado todo y ha bebido bien. Paseo por el pasillo con andador.', ['Alimentación e hidratación' => 'Desayuno completo, buena hidratación.', 'Movilidad' => 'Paseo con andador.']],
        ['Ducha sin incidencias. Algo cansada, ha descansado tras la comida.', ['Higiene' => 'Ducha sin incidencias.', 'Descanso' => 'Siesta tras la comida.']],
        ['Ha participado en el taller de memoria y estaba de buen ánimo.', ['Ánimo' => 'Buen ánimo, participa en el taller.']],
        ['Ha comido la mitad del segundo plato. Se le ofrece agua cada hora.', ['Alimentación e hidratación' => 'Come la mitad del segundo; agua cada hora.']],
    ];

    private const INCIDENCIAS = [
        ['Caídas y mareos', 'Mareo al levantarse de la cama. Se sienta y se recupera; se avisa a enfermería.'],
        ['Piel y úlceras', 'Rojez en el talón derecho. Se cambia de postura cada dos horas.'],
        ['Ánimo y conducta', 'Muy inquieta por la tarde; pregunta por su hija. Se la acompaña.'],
    ];

    public static function asegurar(): void
    {
        if (! app()->environment('local') || Residencia::actual()?->nombre !== 'Residencia Los Almendros') {
            return;
        }
        $marcaHoy = storage_path('app/demo-semana-'.today()->toDateString());
        if (file_exists($marcaHoy)) {
            return; // ya se rellenó hoy
        }
        @touch($marcaHoy);

        $auxiliares = User::auxiliares()->where('activo', true)->whereNotNull('turno_habitual')->get();
        $residentes = Residente::activos()->where('estado', 'en_centro')->orderBy('id')->get();
        if ($auxiliares->isEmpty() || $residentes->isEmpty()) {
            return;
        }

        $marcaHistorial = storage_path('app/demo-semana-historial');
        $yaHayHistorial = file_exists($marcaHistorial);
        @touch($marcaHistorial);
        $dias = $yaHayHistorial ? [0] : range(14, 0);

        foreach ($dias as $hace) {
            $dia = today()->subDays($hace);
            if ($hace > 0) {
                self::turnosDelDia($dia, $auxiliares, $hace);
            }
            // Registros: el día de hoy, el 80 % de los residentes; días pasados, todos.
            $deHoy = $hace === 0 ? $residentes->take((int) ceil($residentes->count() * 0.8)) : $residentes;
            foreach ($deHoy as $i => $r) {
                $hora = $hace === 0 ? now()->copy()->subMinutes(20 + $i * 7) : $dia->copy()->setTime(9 + ($i % 8), ($i * 13) % 60);
                $incidencia = self::hayIncidencia($hace, $i);
                $t = self::TEXTOS[($i + $hace) % count(self::TEXTOS)];
                $inc = self::INCIDENCIAS[($i + $hace) % count(self::INCIDENCIAS)];
                $reg = Registro::create([
                    'residente_id' => $r->id,
                    'user_id' => $auxiliares[($i + $hace) % $auxiliares->count()]->id,
                    'texto_original' => $incidencia ? $inc[1] : (($i + $hace) % 3 === 0 ? null : $t[0]),
                    'categorias' => $incidencia ? ['Incidencias' => $inc[1]] : (($i + $hace) % 3 === 0 ? [] : $t[1]),
                    'estado' => $incidencia || ($i + $hace) % 3 !== 0 ? 'confirmado' : 'sin_novedad',
                    'incidencia_destacada' => $incidencia,
                    'tipo_incidencia' => $incidencia ? $inc[0] : null,
                ]);
                $reg->forceFill(['created_at' => $hora, 'updated_at' => $hora])->saveQuietly();
            }
        }
    }

    /** Tres turnos cerrados al día; 1 de cada 6, con pendientes y motivo (sobre todo carga de trabajo). */
    private static function turnosDelDia(Carbon $dia, $auxiliares, int $hace): void
    {
        foreach ([['manana', 7, 15], ['tarde', 15, 23], ['noche', 23, 31]] as $n => [$turno, $de, $a]) {
            $u = $auxiliares->firstWhere('turno_habitual', $turno) ?? $auxiliares[$n % $auxiliares->count()];
            $conPendientes = ($hace * 3 + $n) % 6 === 0;
            Turno::create([
                'user_id' => $u->id,
                'planta_id' => $u->planta_id,
                'inicio' => $dia->copy()->addHours($de),
                'fin' => $dia->copy()->addHours($a),
                'motivo_cierre' => $conPendientes ? (($hace % 4 === 0) ? 'urgencia' : 'carga') : null,
                'nota_relevo' => $conPendientes ? 'Quedan residentes sin registrar; ver motivo.' : 'Turno tranquilo.',
            ]);
        }
    }

    /** Incidencias: 6 la semana anterior y 4 esta, repartidas. */
    private static function hayIncidencia(int $hace, int $i): bool
    {
        $esta = [1 => 0, 3 => 2, 5 => 4, 6 => 1];
        $anterior = [8 => 3, 9 => 0, 10 => 5, 11 => 2, 12 => 6, 13 => 1];

        return ($esta[$hace] ?? $anterior[$hace] ?? -1) === $i;
    }
}
