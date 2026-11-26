<?php

namespace App\Http\Controllers\Auxiliar;

use App\Http\Controllers\Controller;
use App\Models\Ausencia;
use App\Models\Aviso;
use App\Models\Pregunta;
use App\Models\Registro;
use App\Models\Residencia;
use App\Models\Residente;
use App\Models\SugerenciaIa;
use App\Models\Turno;
use App\Models\User;
use App\Support\Avatar;
use App\Support\Dispositivo;
use App\Support\Ia\Sugerencias;
use App\Support\OrdenarRegistro;
use App\Support\TurnoActual;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

/**
 * App de la auxiliar con shadcn-vue (25/11/2026), en paralelo a /turno (Filament) hasta validarla.
 * Flujo del 26/11 (comentarios de Pablo y Antonio, decisiones 1-3 de Ana):
 * entrar con PIN · Mi planta (Resumen y Residentes en una sola lista) · ficha del residente (página, no ventana) ·
 * Registrar (grabar, o escribir con un formulario guiado → revisar → confirmar) · Mi turno · Antes de irte (opcional).
 * No se abre ni se cierra turno: se sabe por el PIN y la hora. «Sin novedad» se marca en bloque al final, no uno a uno.
 */
class AuxiliarController extends Controller
{
    /** Respuestas rápidas del formulario guiado: lo que más se escribe en cada categoría. [SUPOSICIÓN] hasta verlo en el piloto. */
    public const RESPUESTAS_RAPIDAS = [
        'Alimentación e hidratación' => ['Ha comido todo', 'Ha comido la mitad', 'Casi no ha comido', 'Ha bebido bien', 'Bebe poco'],
        'Higiene' => ['Ducha', 'Aseo en cama', 'Cambio de pañal', 'Sin incidencias'],
        'Movilidad' => ['Ha caminado', 'En silla de ruedas', 'Cambios posturales', 'En cama'],
        'Sujeción' => ['No se ha usado', 'Se ha usado con prescripción'],
        'Descanso' => ['Ha dormido bien', 'Se ha despertado', 'Ha hecho siesta'],
        'Ánimo' => ['Tranquilo', 'Alegre', 'Triste', 'Inquieto'],
        'Incidencias' => ['Caída', 'Herida o piel', 'Fiebre', 'Se ha negado a comer'],
    ];

    private function yo(): User
    {
        return auth('turno')->user();
    }

    private function t(): TurnoActual
    {
        return TurnoActual::para($this->yo());
    }

    /** Lo que la auxiliar ve de un residente: su estado en el turno y la ficha «Cómo atender». */
    private function residente(Residente $r, ?string $estado = null): array
    {
        $c = $r->contactoEmergencia();
        $ultimo = $r->registros->first();

        return [
            'id' => $r->id, 'nombre' => $r->nombre, 'corto' => strtok($r->nombre, ' '), 'habitacion' => $r->habitacion,
            'planta' => $r->planta?->nombre, 'foto' => Avatar::url($r->foto, $r->nombre), 'estado' => $estado,
            'ultimo' => $ultimo ? ['texto' => $ultimo->resumenCorto(), 'hora' => $ultimo->created_at->format('H:i'), 'incidencia' => (bool) $ultimo->incidencia_destacada] : null,
            'fuera' => $r->estado === 'fuera',
            'atender' => [
                'alergias' => $r->alergias, 'riesgos' => $r->riesgos, 'movilidad' => $r->movilidad,
                'sujecion' => $r->sujecion ? 'Sí. Registra cada uso.' : 'No indicada.', 'dieta' => $r->dieta, 'comoTratarle' => $r->como_tratarle,
                'contacto' => $c ? ['nombre' => $c->nombre, 'relacion' => $c->relacion, 'telefono' => $c->telefono] : null,
            ],
        ];
    }

    private function aviso(string $texto, string $tipo = 'ok'): void
    {
        session()->flash('aviso', ['texto' => $texto, 'tipo' => $tipo]);
    }

    // ---------- Entrar con PIN (regla 3: nada bloquea; si se olvida, la responsable de turno lo restablece) ----------

    public function entrar(): Response|RedirectResponse
    {
        if (auth('turno')->check()) {
            return redirect()->route('auxiliar.planta');
        }
        $r = Residencia::actual();
        $v = Dispositivo::usuarioVinculado();

        return Inertia::render('Entrar', [
            'lema' => $r?->lema,
            'vinculada' => $v ? strtok($v->name, ' ') : null,
            'esMovil' => Dispositivo::esMovil(),
            'fecha' => ucfirst(now()->locale('es')->isoFormat('dddd, D [de] MMMM')),
            'rutas' => ['olvide' => route('filament.turno.restablecer-pin'), 'primerDia' => route('filament.turno.primer-acceso')],
        ]);
    }

    public function comprobarPin(Request $request): RedirectResponse
    {
        $pin = (string) $request->input('pin');
        $clave = 'pin:'.$request->ip();
        if (RateLimiter::tooManyAttempts($clave, 10)) {
            return back()->withErrors(['pin' => 'Demasiados intentos. Espera un minuto o pide ayuda a la responsable de turno.']);
        }
        $v = Dispositivo::usuarioVinculado();
        $candidatas = $v ? collect([$v]) : User::auxiliares()->where('activo', true)->whereNotNull('pin')->get();
        $user = $candidatas->first(fn (User $u) => Hash::check($pin, $u->pin));
        if (! $user) {
            RateLimiter::hit($clave, 60);
            $fallos = (int) session('pin_fallos', 0) + 1;
            session(['pin_fallos' => $fallos]);

            return back()->withErrors(['pin' => $fallos >= 3
                ? 'El PIN no es correcto. Si no lo recuerdas, la responsable de turno te da uno nuevo.'
                : 'El PIN no es correcto. Prueba otra vez.']);
        }
        RateLimiter::clear($clave);
        session()->forget('pin_fallos');
        auth('turno')->login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('auxiliar.planta'));
    }

    /** La guía de la primera vez ya se ha visto (o se ha saltado): no vuelve a salir sola. */
    public function guiaVista(): RedirectResponse
    {
        $this->yo()->forceFill(['guia_vista_at' => now()])->save();

        return back();
    }

    public function salir(Request $request): RedirectResponse
    {
        auth('turno')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('auxiliar.entrar');
    }

    // ---------- Mi planta: una sola lista (decisión 1, comentarios de Pablo en A1-03 y A2-06) ----------

    /**
     * Arriba, lo que espera respuesta (pregunta de la dirección, encargos, quién vuelve hoy).
     * Después, la planta en una lista: con novedad (incidencias primero, con la novedad y quién la anotó dentro de la fila),
     * por registrar (borradores primero), registrados y fuera. Antes eran dos pestañas que repetían a los mismos residentes.
     */
    public function miPlanta(): Response
    {
        $t = $this->t();
        $h = (int) now()->format('G');
        $novedades = $t->novedades()->keyBy('residente_id');
        $g = $t->agrupados();
        $fila = function ($r) use ($t, $novedades) {
            $d = $this->residente($r, $t->estado($r));
            // Solo se destaca lo que pide atención (incidencias). Una ducha sin incidencias no es una novedad:
            // sale como «Último: …» en su fila, sin ocupar el primer bloque.
            if (($n = $novedades->get($r->id)) && $n->incidencia_destacada) {
                $d['novedad'] = ['texto' => $n->resumenCorto(), 'incidencia' => (bool) $n->incidencia_destacada,
                    'quien' => $n->user ? strtok($n->user->name, ' ') : '', 'hora' => $n->created_at->format('H:i')];
            }

            return $d;
        };
        $todos = $g['por_registrar']->concat($g['registrados'])->map($fila);

        return Inertia::render('MiPlanta', [
            'saludo' => ($h < 14 ? 'Buenos días' : ($h < 21 ? 'Buenas tardes' : 'Buenas noches')).', '.strtok($this->yo()->name, ' '),
            'fecha' => now()->locale('es')->isoFormat('ddd D MMM'),
            'preguntas' => $t->preguntas()->map(fn (Pregunta $p) => ['id' => $p->id, 'texto' => $p->texto, 'residente' => $p->residente?->nombre])->values(),
            'asignados' => Aviso::asignadosA($this->yo()->id)->with('residente')->get()
                ->map(fn ($a) => ['id' => $a->id, 'titulo' => $a->titulo, 'residente' => $a->residente?->nombre, 'vence' => $a->vence_el?->locale('es')->isoFormat('ddd D')])->values(),
            'vuelvenHoy' => $t->vuelvenHoy()->map(fn (Ausencia $a) => ['id' => $a->id, 'residente' => $a->residente?->nombre])->values(),
            'notas' => $t->notasCompaneras()->map(fn (Turno $x) => ['quien' => $x->user?->name, 'hora' => $x->fin->format('H:i'), 'nota' => $x->nota_relevo])->values(),
            'conNovedad' => $todos->filter(fn ($r) => isset($r['novedad']))->sortByDesc(fn ($r) => $r['novedad']['incidencia'])->values(),
            'porRegistrar' => $todos->filter(fn ($r) => ! isset($r['novedad']) && $r['estado'] !== 'registrado')->values(),
            'registrados' => $todos->filter(fn ($r) => ! isset($r['novedad']) && $r['estado'] === 'registrado')->values(),
            'fuera' => $g['fuera']->map(fn ($r) => $this->residente($r, 'fuera'))->values(),
            'total' => $g['por_registrar']->count() + $g['registrados']->count(),
            'hechos' => $g['registrados']->count(),
        ]);
    }

    /** Ficha del residente como página (comentario de Pablo en A1-04: las ventanas son delicadas en el móvil). «Registrar» fijo abajo. */
    public function ficha(Residente $residente): Response
    {
        $t = $this->t();
        $residente->load(['registros' => fn ($q) => $q->with('user')->whereIn('estado', ['confirmado', 'sin_novedad'])->latest()->limit(3)]);

        return Inertia::render('Residente', [
            'residente' => $this->residente($residente, $t->estado($residente)),
            'ultimos' => $residente->registros->map(fn (Registro $r) => [
                'id' => $r->id, 'texto' => $r->estado === 'sin_novedad' ? 'Sin novedad' : $r->resumenCorto(), 'incidencia' => (bool) $r->incidencia_destacada,
                'cuando' => $r->created_at->isToday() ? 'Hoy, '.$r->created_at->format('H:i') : $r->created_at->locale('es')->isoFormat('ddd D, HH:mm'),
                'quien' => $r->user ? strtok($r->user->name, ' ') : '',
            ])->values(),
        ]);
    }

    public function responder(Request $request, Pregunta $pregunta): RedirectResponse
    {
        $texto = trim((string) $request->input('respuesta'));
        if ($texto !== '') {
            $pregunta->update(['respuesta' => $texto, 'respondida_por' => $this->yo()->id]);
            $this->aviso('Respuesta enviada a la dirección.');
        }

        return back();
    }

    public function hecho(int $aviso): RedirectResponse
    {
        Aviso::asignadosA($this->yo()->id)->whereKey($aviso)->update(['hecho_at' => now(), 'estado' => 'hecho']);
        $this->aviso('Hecho. La dirección lo verá en el historial.');

        return back();
    }

    public function haVuelto(Ausencia $ausencia): RedirectResponse
    {
        $ausencia->update(['ha_vuelto' => true]);
        $ausencia->residente->update(['estado' => 'en_centro']);
        $this->aviso($ausencia->residente->nombre.' ha vuelto.');

        return back();
    }

    /** «Residentes» ya no es una pestaña: se fusiona con el resumen en Mi planta. Se mantiene la ruta para enlaces antiguos. */
    public function residentes(): RedirectResponse
    {
        return redirect()->route('auxiliar.planta');
    }

    // ---------- Registrar: grabar o escribir → Velia ordena → la auxiliar revisa y confirma ----------

    public function registrar(Request $request, Residente $residente): Response
    {
        $b = Registro::where('residente_id', $residente->id)->where('turno_id', $this->yo()->turnoAbierto()?->id)
            ->where('estado', 'borrador')->latest()->first();

        return Inertia::render('Registrar', [
            'residente' => $this->residente($residente->load('registros')),
            'fecha' => now()->locale('es')->isoFormat('ddd D MMM'),
            'grabarYa' => $request->boolean('grabar'),
            'borrador' => $b ? ['id' => $b->id, 'texto' => (string) $b->texto_original, 'categorias' => (object) ($b->categorias ?? []),
                'incidencia' => (bool) $b->incidencia_destacada, 'tipo' => $b->tipo_incidencia] : null,
            'categoriasPosibles' => Registro::CATEGORIAS,
            'tiposIncidencia' => \App\Filament\Turno\Pages\Registrar::TIPOS_INCIDENCIA,
            // Formulario guiado para escribir (comentario de Pablo en A2-08): respuestas rápidas por categoría.
            'rapidas' => self::RESPUESTAS_RAPIDAS,
        ]);
    }

    /**
     * Grabar primero y decir de quién después (v1.5, comentario de Pablo en A1-02): desde el micro de la barra se empieza a hablar
     * sin elegir. Si en lo dictado aparece el nombre o la habitación, Velia propone el residente; si no, pregunta «¿De quién?».
     */
    public function grabarSinElegir(Request $request): Response
    {
        $t = $this->t();

        return Inertia::render('Registrar', [
            'residente' => ['id' => null, 'nombre' => 'Nuevo registro', 'corto' => '', 'habitacion' => null, 'foto' => null, 'atender' => []],
            'opciones' => $t->residentes()->reject(fn ($r) => $r->estado === 'fuera')
                ->map(fn ($r) => ['id' => $r->id, 'nombre' => $r->nombre, 'habitacion' => $r->habitacion, 'foto' => Avatar::url($r->foto, $r->nombre), 'estado' => $t->estado($r)])
                ->sortBy(fn ($x) => $x['estado'] === 'registrado' ? 1 : 0)->values(),
            'fecha' => now()->locale('es')->isoFormat('ddd D MMM'),
            'grabarYa' => $request->boolean('grabar'),
            'borrador' => null,
            'categoriasPosibles' => Registro::CATEGORIAS,
            'tiposIncidencia' => \App\Filament\Turno\Pages\Registrar::TIPOS_INCIDENCIA,
            'rapidas' => self::RESPUESTAS_RAPIDAS,
        ]);
    }

    /** Busca en lo dictado el nombre o la habitación de un residente de la planta. Sin IA externa: solo coincidencias de texto. */
    private function residenteEnTexto(string $texto): ?int
    {
        $n = fn (string $s) => mb_strtolower(\Illuminate\Support\Str::ascii($s));
        $t = ' '.$n($texto).' ';
        $encontrados = $this->t()->residentes()->filter(function ($r) use ($t, $n) {
            $partes = array_filter(explode(' ', $n($r->nombre)), fn ($p) => mb_strlen($p) > 2);
            foreach ($partes as $p) {
                if (preg_match('/\b'.preg_quote($p, '/').'\b/u', $t)) {
                    return true;
                }
            }

            return $r->habitacion && preg_match('/\b(hab(itacion)?\.?|la)\s*'.preg_quote($n((string) $r->habitacion), '/').'\b/u', $t);
        });

        return $encontrados->count() === 1 ? $encontrados->first()->id : null;
    }

    /** La IA ordena lo dictado o escrito (simulada con reglas) y propone lo que suele faltar en este turno. */
    public function ordenar(Request $request)
    {
        $texto = trim((string) $request->input('texto'));
        if ($texto === '') {
            return response()->json(['error' => 'No hemos recogido nada. Prueba otra vez o escribe.'], 422);
        }
        $o = OrdenarRegistro::ordenar($texto);

        return response()->json([
            'categorias' => (object) $o['categorias'], 'incidencia' => (bool) $o['incidencia'], 'tipo' => $o['tipo_incidencia'],
            'faltan' => array_values(Sugerencias::faltanEnRegistro($o['categorias'])),
            'detectado' => $request->boolean('buscarResidente') ? $this->residenteEnTexto($texto) : null,
        ]);
    }

    public function guardar(Request $request, Residente $residente): RedirectResponse
    {
        $estado = $request->input('estado');
        abort_unless(in_array($estado, ['confirmado', 'borrador', 'sin_novedad'], true), 422);
        $yo = $this->yo();
        $categorias = array_filter((array) $request->input('categorias', []), fn ($v) => trim((string) $v) !== '');
        // Avisar a dirección (comentario de Pablo en A2-09): sin casilla. Velia lo propone si detecta algo, o la auxiliar lo pide con un motivo.
        $incidencia = $estado !== 'sin_novedad' && $request->boolean('incidencia');
        $datos = [
            'residente_id' => $residente->id, 'user_id' => $yo->id, 'turno_id' => $yo->turnoAbierto()?->id,
            'texto_original' => $estado === 'sin_novedad' ? null : (string) $request->input('texto'),
            'categorias' => $estado === 'sin_novedad' ? [] : $categorias,
            'estado' => $estado, 'incidencia_destacada' => $incidencia,
            'tipo_incidencia' => $incidencia ? ($request->input('tipo') ?: 'Otra') : null,
        ];
        $id = $request->integer('registroId');
        $registro = $id ? tap(Registro::where('user_id', $yo->id)->findOrFail($id))->update($datos) : Registro::create($datos);

        // Regla 10: toda sugerencia de la IA se acepta, se cambia o se descarta, y queda anotada.
        $propuesta = (array) $request->input('propuesta', []);
        if ($estado === 'confirmado' && $propuesta) {
            SugerenciaIa::anotar('registrar.ordenar', collect($propuesta)->map(fn ($v, $k) => "{$k}: {$v}")->join(' | '),
                $categorias == $propuesta ? 'aceptada' : 'cambiada', $registro);
        }
        if ($estado === 'confirmado' && ($p = $request->input('avisoPropuesto'))) {
            SugerenciaIa::anotar('registrar.aviso', 'Avisar a dirección: '.$p, $request->input('avisoDecision') ?: 'descartada', $registro);
        }
        foreach ((array) $request->input('faltanAceptadas', []) as $cat) {
            SugerenciaIa::anotar('registrar.faltan', 'Añadir «Sin novedad» en '.$cat, 'aceptada', $residente);
        }
        foreach ((array) $request->input('faltanDescartadas', []) as $cat) {
            SugerenciaIa::anotar('registrar.faltan', 'Añadir «Sin novedad» en '.$cat, 'descartada', $residente);
        }

        if ($estado === 'confirmado' && $registro->incidencia_destacada) {
            $motivo = trim((string) $request->input('motivoAviso'));
            Aviso::create(['tipo' => 'incidencia', 'titulo' => $registro->tipo_incidencia.' · '.$residente->nombre, 'residente_id' => $residente->id,
                'detalle' => trim(($motivo !== '' ? $motivo.'. ' : '').$registro->resumenCorto()), 'urgencia' => 1]);
            // Tras una caída, la dirección tiene que dejar constancia de que se ha informado a la familia (26/11/2026).
            // Lo hace la dirección, no la auxiliar: a la auxiliar no se le añade ningún paso (regla 6).
            if (str_starts_with((string) $registro->tipo_incidencia, 'Caídas')) {
                Aviso::create(['tipo' => 'informar_familia', 'titulo' => 'Informar a la familia de la caída', 'residente_id' => $residente->id,
                    'detalle' => $registro->resumenCorto(), 'urgencia' => 1]);
            }
        }
        $this->aviso(match ($estado) {
            'sin_novedad' => 'Sin novedad. Registrado.',
            'borrador' => 'Guardado como borrador. Confírmalo antes de irte.',
            default => $registro->incidencia_destacada ? 'Registrado. La dirección ha recibido el aviso.' : 'Registrado.',
        });

        return redirect()->route('auxiliar.planta');
    }

    // ---------- Mi turno ----------

    public function miTurno(): Response
    {
        $t = $this->t();
        $g = $t->agrupados();
        $estados = $g['por_registrar']->map(fn ($r) => $t->estado($r));

        return Inertia::render('MiTurno', [
            'registrados' => $g['registrados']->count(),
            'total' => $g['registrados']->count() + $g['por_registrar']->count(),
            'borradores' => $estados->filter(fn ($e) => $e === 'borrador')->count(),
            'pendientes' => $estados->filter(fn ($e) => $e === 'pendiente')->count(),
            'fuera' => $g['fuera']->count(),
            // «Sin novedad» en bloque (decisión 3): quién no tiene registro y no tiene borrador.
            // Al empezar el turno no se ofrece: marcar a todos «sin novedad» sin haberles atendido vaciaría la evidencia (regla 4).
            // Aparece en las 2 últimas horas del turno (o en «Antes de irte», a propósito). [SUPOSICIÓN] turnos de unas 8 h.
            'finalDeTurno' => (bool) $this->yo()->turnoAbierto()?->inicio?->lte(now()->subHours(6)),
            'sinRegistro' => $g['por_registrar']->filter(fn ($r) => $t->estado($r) === 'pendiente')->map(fn ($r) => ['id' => $r->id, 'corto' => strtok($r->nombre, ' ')])->values(),
            'companeras' => $t->companeras()->map(fn ($c) => ['nombre' => $c['user']->name, 'foto' => Avatar::url($c['user']->foto, $c['user']->name),
                'planta' => $c['user']->planta?->nombre ?? 'Todas las plantas', 'enTurno' => $c['enTurno']])->values(),
            'equipo' => Residencia::actual()?->equipoHoy() ?? [],
        ]);
    }

    // ---------- Antes de irte (opcional): «sin novedad» en bloque, relevo y nota (decisiones 2 y 3) ----------

    /**
     * Ya no hay que cerrar el turno para irse: «Salir» basta y el turno termina solo al acabar el horario.
     * Esta pantalla es opcional: marcar en bloque «sin novedad» a quien no tiene registro (evidencia UNE, regla 4),
     * revisar el relevo automático y dejar una nota. Si quedan pendientes sin marcar, se pide el motivo.
     */
    public function cerrarTurno(): Response
    {
        $t = $this->t();
        $turno = $this->yo()->turnoAbierto();
        $porRegistrar = $t->agrupados()['por_registrar'];

        return Inertia::render('CerrarTurno', [
            'pendientes' => $porRegistrar->map(fn ($r) => $this->residente($r, $t->estado($r)))->values(),
            'relevo' => Registro::with('residente')->where('turno_id', $turno?->id)->where('estado', 'confirmado')->orderByDesc('incidencia_destacada')->get()
                ->map(fn ($r) => ['id' => $r->id, 'residente' => $r->residente->nombre, 'texto' => $r->resumenCorto(), 'incidencia' => (bool) $r->incidencia_destacada])->values(),
            'motivos' => Turno::MOTIVOS_CIERRE,
        ]);
    }

    /** «Sin novedad» en bloque: un registro «sin_novedad» para cada residente pendiente (no los borradores, que se confirman). */
    public function sinNovedadBloque(Request $request): RedirectResponse
    {
        $t = $this->t();
        $yo = $this->yo();
        $ids = collect((array) $request->input('ids', []))->map(fn ($i) => (int) $i);
        $pendientes = $t->agrupados()['por_registrar']->filter(fn ($r) => $t->estado($r) === 'pendiente' && $ids->contains($r->id));
        foreach ($pendientes as $r) {
            Registro::create(['residente_id' => $r->id, 'user_id' => $yo->id, 'turno_id' => $yo->turnoAbierto()?->id,
                'texto_original' => null, 'categorias' => [], 'estado' => 'sin_novedad', 'incidencia_destacada' => false, 'tipo_incidencia' => null]);
        }
        $n = $pendientes->count();
        $this->aviso($n === 1 ? '1 residente marcado sin novedad.' : "{$n} residentes marcados sin novedad.");

        return back();
    }

    public function cerrar(Request $request): RedirectResponse
    {
        $t = $this->t();
        $yo = $this->yo();
        $turno = $yo->turnoAbierto();
        $pendientes = $t->agrupados()['por_registrar'];
        $hay = $pendientes->isNotEmpty();
        $request->validate([
            'motivo' => $hay ? 'required|in:'.implode(',', array_keys(Turno::MOTIVOS_CIERRE)) : 'nullable',
            'motivoTexto' => $request->input('motivo') === 'otros' ? 'required|min:3' : 'nullable',
        ], ['motivo.required' => 'Quedan residentes sin registrar: márcalos sin novedad o elige un motivo.', 'motivoTexto.required' => 'Cuéntalo en pocas palabras.']);

        $quitados = (array) $request->input('quitados', []);
        $lineas = Registro::with('residente')->where('turno_id', $turno?->id)->where('estado', 'confirmado')->whereNotIn('id', $quitados)
            ->orderByDesc('incidencia_destacada')->get()->map(fn ($r) => $r->residente->nombre.' · '.$r->resumenCorto())->all();
        if (trim((string) $request->input('nota')) !== '') {
            $lineas[] = trim($request->input('nota'));
        }
        $motivo = $request->input('motivo');
        if ($hay && $turno) {
            self::avisoPendientes($yo, $turno, $pendientes->count(), $motivo, $motivo === 'otros' ? $request->input('motivoTexto') : null);
        }
        $turno?->update(['fin' => now(), 'motivo_cierre' => $hay ? $motivo : null, 'motivo_texto' => $motivo === 'otros' ? $request->input('motivoTexto') : null,
            'nota_relevo' => $lineas ? implode("\n", $lineas) : null]);

        auth('turno')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        session()->flash('aviso', ['texto' => 'Listo. Gracias por tu turno.', 'tipo' => 'ok']);

        return redirect()->route('auxiliar.entrar');
    }

    /** Aviso a la dirección cuando un turno termina con residentes sin registrar (a mano o al acabar el horario). */
    public static function avisoPendientes(User $yo, Turno $turno, int $n, string $motivo, ?string $detalle = null): void
    {
        $h = (int) $turno->inicio->format('G');
        $franja = $h >= 7 && $h < 15 ? 'mañana' : ($h >= 15 && $h < 23 ? 'tarde' : 'noche');
        Aviso::create([
            'tipo' => 'turno_con_motivo',
            'titulo' => ($yo->planta?->nombre ?? 'Todas las plantas').' · '.$franja.' del '.$turno->inicio->format('d/m').' · '.$n.' sin registrar · '
                .mb_strtolower(Turno::MOTIVOS_CIERRE[$motivo] ?? $motivo).' · '.$yo->name,
            'detalle' => $detalle, 'urgencia' => 2, 'estado' => 'pendiente',
        ]);
    }
}
