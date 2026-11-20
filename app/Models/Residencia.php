<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Residencia extends Model
{
    protected $fillable = [
        'nombre', 'lema', 'responsable', 'direccion', 'telefono', 'email', 'cif',
        'plazas_autorizadas', 'proxima_auditoria', 'piloto_hasta', 'fotos', 'equipo',
    ];

    protected function casts(): array
    {
        return ['proxima_auditoria' => 'date', 'piloto_hasta' => 'date', 'fotos' => 'array', 'equipo' => 'array'];
    }

    public const PUESTOS = [
        'enfermeria' => 'Enfermería',
        'medicina' => 'Medicina',
        'fisioterapia' => 'Fisioterapia',
        'psicologia' => 'Psicología',
        'trabajo_social' => 'Trabajo social',
        'otro' => 'Otro',
    ];

    public const DIAS = [1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sábado', 7 => 'domingo'];

    /**
     * Equipo sanitario y de apoyo con su estado ahora: en la residencia, viene hoy más tarde,
     * ya se ha ido o cuándo vuelve. Primero quien está, para saber a quién llamar.
     */
    public function equipoHoy(?\Carbon\CarbonInterface $ahora = null): array
    {
        $ahora ??= now();
        $hoy = $ahora->isoWeekday();
        $hora = $ahora->format('H:i');

        return User::sanitarios()->where('activo', true)->orderBy('name')->get()->map(function (User $p) use ($hoy, $hora, $ahora) {
            $dias = array_map('intval', $p->dias ?? []);
            $desde = $p->desde;
            $hasta = $p->hasta;
            $viene = in_array($hoy, $dias, true);

            if ($viene && $desde && $hasta && $hora >= $desde && $hora < $hasta) {
                [$orden, $estado, $texto] = [0, 'aqui', 'En la residencia hasta las '.$hasta];
            } elseif ($viene && $desde && $hora < $desde) {
                [$orden, $estado, $texto] = [1, 'luego', 'Hoy de '.$desde.' a '.$hasta];
            } else {
                [$orden, $estado, $texto] = [2, 'fuera', $this->proximoDia($dias, $ahora, $desde)];
            }

            return [
                'nombre' => $p->name,
                'puesto' => $p->etiquetaPuesto(),
                'contacto' => $p->contacto,
                'foto' => \App\Support\Avatar::url($p->foto, $p->name),
                'estado' => $estado,
                'texto' => $texto,
                'orden' => $orden,
            ];
        })->sortBy('orden')->values()->all();
    }

    private function proximoDia(array $dias, \Carbon\CarbonInterface $ahora, ?string $desde): string
    {
        if ($dias === []) {
            return 'Sin horario fijo';
        }
        for ($i = 1; $i <= 7; $i++) {
            $d = $ahora->copy()->addDays($i);
            if (in_array($d->isoWeekday(), $dias, true)) {
                $cuando = $i === 1 ? 'mañana' : 'el '.self::DIAS[$d->isoWeekday()];

                return 'Hoy no está · vuelve '.$cuando.($desde ? ' a las '.$desde : '');
            }
        }

        return 'Hoy no está';
    }

    /** Solo hay una residencia por instalación en el MVP. */
    public static function actual(): self
    {
        return static::query()->firstOrCreate([], ['nombre' => 'Residencia Los Almendros']);
    }
}
