<?php

namespace Database\Seeders;

use App\Models\Ausencia;
use App\Models\Aviso;
use App\Models\Contacto;
use App\Models\Planta;
use App\Models\Pregunta;
use App\Models\Registro;
use App\Models\Residencia;
use App\Models\Residente;
use App\Models\Resumen;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/** Datos ficticios de Residencia Los Almendros. Ninguna persona es real. */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Residencia::create([
            'nombre' => 'Residencia Los Almendros',
            'responsable' => 'Marta Iglesias',
            'direccion' => 'Calle del Olmo 12, Getafe',
            'telefono' => '916 000 000',
            'email' => 'direccion@losalmendros.es',
            'cif' => 'B12345678',
            'plazas_autorizadas' => 20,
            'proxima_auditoria' => now()->addDays(100),
            'piloto_hasta' => now()->addMonths(2),
        ]);

        $p1 = Planta::create(['nombre' => 'Planta 1']);
        $p2 = Planta::create(['nombre' => 'Planta 2']);

        $marta = User::create(['name' => 'Marta Iglesias', 'email' => 'marta@losalmendros.es', 'password' => 'velia2026', 'rol' => 'direccion']);

        $aux = function (string $nombre, ?Planta $planta, string $turno, ?string $pin, bool $responsable = false, bool $provisional = false, ?string $codigo = null) {
            return User::create([
                'name' => $nombre, 'rol' => 'auxiliar', 'planta_id' => $planta?->id, 'turno_habitual' => $turno,
                'pin' => $pin ? Hash::make($pin) : null, 'responsable_turno' => $responsable,
                'pin_provisional' => $provisional, 'codigo_invitacion' => $codigo,
            ]);
        };
        $lucia = $aux('Lucía Martín', $p1, 'manana', '1234');
        $ana = $aux('Ana Torres', null, 'manana', '2468', true);
        $aux('Sonia Prieto', $p1, 'manana', '5310');

        // Enfermería y medicina: en Equipo y en «Mi turno» de las auxiliares; no entran en la app.
        foreach ([
            ['Elena Vidal', 'enfermeria', [1, 2, 3, 4, 5], '08:00', '15:00', 'Ext. 210'],
            ['Lucas Herrero', 'enfermeria', [1, 2, 3, 4, 5], '15:00', '22:00', 'Ext. 210'],
            ['Javier Molina', 'medicina', [2, 4], '10:00', '13:00', 'Ext. 215'],
        ] as [$n, $puesto, $dias, $desde, $hasta, $contacto]) {
            User::create(['name' => $n, 'rol' => 'sanitario', 'puesto' => $puesto, 'dias' => $dias, 'desde' => $desde, 'hasta' => $hasta, 'contacto' => $contacto]);
        }
        $rosa = $aux('Rosa Delgado', $p2, 'noche', '1357', true);
        $jorge = $aux('Jorge Sanz', $p1, 'noche', '9753');
        $aux('Marisol Vega', $p2, 'tarde', '8642', true);
        $aux('Inés Campos', null, 'tarde', '4827', false, true, 'ALM472');

        $datos = [
            ['Carmen Ruiz', $p1, '101', 'doble', 'Penicilina', 'Caídas; mareos al incorporarse', 'Andador; acompañar al levantarse', 'Blanda, sin sal', 'Hablarle despacio y de frente. Le gusta la radio por la mañana.'],
            ['Rosario Méndez', $p1, '101', 'doble', null, null, 'Autónoma', 'Normal', 'Prefiere que la llamen Rosi.'],
            ['Antonio Gil', $p1, '102', 'individual', null, 'Desorientación de noche', 'Autónomo con bastón', 'Diabética', 'Le tranquiliza que le expliquen lo que se va a hacer.'],
            ['Pilar Navarro', $p1, '103', 'individual', 'Látex', null, 'Silla de ruedas', 'Triturada', 'Muy reservada; respetar sus tiempos.'],
            ['Manuel Ortega', $p1, '104', 'individual', null, 'Caídas', 'Andador', 'Normal', 'Le gusta hablar de fútbol.'],
            ['Dolores Ramos', $p1, '105', 'individual', null, 'Úlceras por presión', 'Encamada; cambios posturales cada 3 h', 'Blanda', 'Hablarle en voz alta, oye poco.'],
            ['Francisco Moreno', $p1, '106', 'individual', null, null, 'Autónomo', 'Normal', null],
            ['Josefa Castillo', $p1, '107', 'doble', null, null, 'Andador', 'Sin gluten', 'Le gusta coser.'],
            ['Ramón Iglesias', $p2, '201', 'individual', null, 'Deshidratación', 'Autónomo', 'Normal', 'Recordarle beber agua.'],
            ['Teresa Blanco', $p2, '202', 'individual', null, null, 'Autónoma', 'Normal', null],
            ['Ángel Domínguez', $p2, '205', 'individual', null, 'Úlceras por presión', 'Silla de ruedas', 'Blanda', null],
        ];
        $res = [];
        foreach ($datos as $i => [$n, $pl, $hab, $tipo, $alerg, $riesgos, $mov, $dieta, $trato]) {
            $res[$n] = Residente::create([
                'nombre' => $n, 'planta_id' => $pl->id, 'habitacion' => $hab, 'tipo_habitacion' => $tipo,
                'fecha_nacimiento' => now()->subYears(80 + $i)->subDays($i * 17), 'fecha_ingreso' => now()->subMonths(6 + $i),
                'alergias' => $alerg, 'riesgos' => $riesgos, 'movilidad' => $mov, 'dieta' => $dieta, 'como_tratarle' => $trato,
                'plan_ultima_revision' => now()->subMonths(4), 'plan_proxima_revision' => now()->addMonths(2)->addDays($i),
            ]);
        }
        $res['Pilar Navarro']->update(['plan_proxima_revision' => now()->subDays(7)]);
        $res['Francisco Moreno']->update(['plan_proxima_revision' => now()->addDays(5)]);
        $res['Josefa Castillo']->update(['estado' => 'fuera']);
        $res['Ángel Domínguez']->update(['estado' => 'fuera']);

        Contacto::create(['residente_id' => $res['Carmen Ruiz']->id, 'nombre' => 'Elena Ruiz', 'relacion' => 'hija', 'telefono' => '617 919 317', 'email' => 'elena.ruiz@correo.es', 'emergencia' => true]);
        Contacto::create(['residente_id' => $res['Carmen Ruiz']->id, 'nombre' => 'Luis Ruiz', 'relacion' => 'hijo', 'telefono' => '625 838 634', 'canal' => 'whatsapp']);
        foreach (['Antonio Gil' => 'Marta Gil', 'Pilar Navarro' => 'José Navarro', 'Manuel Ortega' => 'Lucía Ortega', 'Teresa Blanco' => 'Pedro Blanco'] as $r => $c) {
            Contacto::create(['residente_id' => $res[$r]->id, 'nombre' => $c, 'relacion' => 'familiar', 'telefono' => '600 000 000', 'email' => 'familia@correo.es', 'emergencia' => true]);
        }

        Ausencia::create(['residente_id' => $res['Josefa Castillo']->id, 'tipo' => 'familia', 'sale' => now()->subDays(2), 'vuelve' => now(), 'registrada_por' => $lucia->id]);
        Ausencia::create(['residente_id' => $res['Ángel Domínguez']->id, 'tipo' => 'hospital', 'sale' => now()->subDays(1), 'registrada_por' => $ana->id]);

        $avisos = [
            ['revision_vencida', 'Revisión del plan personal vencida', 'Pilar Navarro', 1],
            ['patron', 'Rojez en zona sacra desde hace 11 días', 'Ángel Domínguez', 1],
            ['incidencia', 'Mareo al levantarse a las 5:00', 'Carmen Ruiz', 2],
            ['turno_con_motivo', 'Turno de noche cerrado con 2 pendientes · urgencia', null, 2],
            ['patron', '3 incidencias de hidratación en 7 días', 'Ramón Iglesias', 2],
            ['patron', 'Ha dormido mal 3 noches seguidas', 'Antonio Gil', 3],
        ];
        foreach ($avisos as [$tipo, $titulo, $r, $u]) {
            Aviso::create(['tipo' => $tipo, 'titulo' => $titulo, 'residente_id' => $r ? $res[$r]->id : null, 'urgencia' => $u]);
        }

        Pregunta::create(['residente_id' => $res['Dolores Ramos']->id, 'texto' => '¿La rojez del talón de Dolores ha mejorado desde ayer?']);

        Registro::create(['residente_id' => $res['Carmen Ruiz']->id, 'user_id' => $jorge->id, 'estado' => 'confirmado', 'incidencia_destacada' => true, 'tipo_incidencia' => 'Caídas y mareos', 'categorias' => ['Incidencias' => 'Se levantó mareada a las 5; ya está bien.'], 'created_at' => now()->subHours(4)]);
        Registro::create(['residente_id' => $res['Dolores Ramos']->id, 'user_id' => $rosa->id, 'estado' => 'confirmado', 'incidencia_destacada' => true, 'tipo_incidencia' => 'Piel y úlceras', 'categorias' => ['Incidencias' => 'Enrojecimiento en el talón derecho.'], 'created_at' => now()->subHours(6)]);
        Registro::create(['residente_id' => $res['Antonio Gil']->id, 'user_id' => $jorge->id, 'estado' => 'confirmado', 'categorias' => ['Descanso' => 'Ha dormido mal, se despertó varias veces.'], 'created_at' => now()->subHours(5)]);

        foreach (['Carmen Ruiz' => true, 'Antonio Gil' => false, 'Manuel Ortega' => false, 'Teresa Blanco' => false, 'Dolores Ramos' => true] as $r => $inc) {
            Resumen::create([
                'residente_id' => $res[$r]->id, 'semana_inicio' => now()->startOfWeek()->subWeek(), 'tiene_incidencias' => $inc,
                'texto' => $inc
                    ? explode(' ', $r)[0].' ha tenido una semana tranquila en general. Hubo una incidencia que el equipo está siguiendo de cerca.'
                    : explode(' ', $r)[0].' ha tenido una semana tranquila. Ha comido bien y ha participado en las actividades.',
            ]);
        }
    
        // Fotos ficticias de la demo (retratos de stock); en una residencia real las sube la dirección.
        foreach (\App\Models\User::whereIn('rol', ['auxiliar', 'sanitario', 'direccion'])->whereNull('foto')->get() as $u) {
            $u->update(['foto' => \App\Support\FotosDemo::para($u->name, $u->id)]);
        }
        $contador = ['m' => 0, 'h' => 0];
        foreach (Residente::whereNull('foto')->orderBy('id')->get() as $r) {
            $k = \App\Support\FotosDemo::esMujer($r->nombre) ? 'm' : 'h';
            $r->update(['foto' => \App\Support\FotosDemo::residente($r->nombre, $contador[$k]++)]);
        }
    }
}
