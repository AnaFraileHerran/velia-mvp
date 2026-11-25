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
 * Mismo flujo y mismas reglas: entrar con PIN · Resumen · Residentes · Registrar (grabar, escribir, revisar) · Mi turno · Cerrar turno.
 * La lógica es la del panel de Filament (TurnoActual, OrdenarRegistro, Sugerencias); aquí solo cambia la interfaz.
 */
class AuxiliarController extends Controller
{
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
            return redirect()->route('auxiliar.resumen');
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

        return redirect()->intended(route('auxiliar.resumen'));
    }

    public function salir(Request $request): RedirectResponse
    {
        auth('turno')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('auxiliar.entrar');
    }

    // ---------- Resumen: pregunta de la dirección, lo que te han encargado, novedades y plegados ----------

    public function resumen(): Response
    {
        $t = $this->t();
        $h = (int) now()->format('G');

        return Inertia::render('Resumen', [
            'saludo' => ($h < 14 ? 'Buenos días' : ($h < 21 ? 'Buenas tardes' : 'Buenas noches')).', '.strtok($this->yo()->name, ' '),
            'fecha' => now()->locale('es')->isoFormat('ddd D MMM'),
            'preguntas' => $t->preguntas()->map(fn (Pregunta $p) => ['id' => $p->id, 'texto' => $p->texto, 'residente' => $p->residente?->nombre])->values(),
            'asignados' => Aviso::asignadosA($this->yo()->id)->with('residente')->get()
                ->map(fn ($a) => ['id' => $a->id, 'titulo' => $a->titulo, 'residente' => $a->residente?->nombre, 'vence' => $a->vence_el?->locale('es')->isoFormat('ddd D')])->values(),
            'novedades' => $t->novedades()->map(fn (Registro $r) => [
                'id' => $r->id, 'texto' => $r->resumenCorto(), 'incidencia' => (bool) $r->incidencia_destacada, 'tipo' => $r->tipo_incidencia,
                'quien' => $r->user ? strtok($r->user->name, ' ').' '.mb_substr(explode(' ', $r->user->name)[1] ?? '', 0, 1).'.' : '',
                'hora' => $r->created_at->format('H:i'),
                'residente' => $this->residente($r->residente->load('registros')),
            ])->values(),
            'vuelvenHoy' => $t->vuelvenHoy()->map(fn (Ausencia $a) => ['id' => $a->id, 'residente' => $a->residente?->nombre])->values(),
            'notas' => $t->notasCompaneras()->map(fn (Turno $x) => ['quien' => $x->user?->name, 'hora' => $x->fin->format('H:i'), 'nota' => $x->nota_relevo])->values(),
            'sinNovedades' => $t->sinNovedades()->map(fn ($r) => $this->residente($r))->values(),
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

    // ---------- Residentes: por registrar (borradores primero), registrados y fuera ----------

    public function residentes(): Response
    {
        $t = $this->t();
        $g = $t->agrupados();
        $map = fn ($c) => $c->map(fn ($r) => $this->residente($r, $t->estado($r)))->values();

        return Inertia::render('Residentes', [
            'porRegistrar' => $map($g['por_registrar']),
            'registrados' => $map($g['registrados']),
            'fuera' => $map($g['fuera']),
        ]);
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
        ]);
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
        ]);
    }

    public function guardar(Request $request, Residente $residente): RedirectResponse
    {
        $estado = $request->input('estado');
        abort_unless(in_array($estado, ['confirmado', 'borrador', 'sin_novedad'], true), 422);
        $yo = $this->yo();
        $categorias = array_filter((array) $request->input('categorias', []), fn ($v) => trim((string) $v) !== '');
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
        foreach ((array) $request->input('faltanAceptadas', []) as $cat) {
            SugerenciaIa::anotar('registrar.faltan', 'Añadir «Sin novedad» en '.$cat, 'aceptada', $residente);
        }
        foreach ((array) $request->input('faltanDescartadas', []) as $cat) {
            SugerenciaIa::anotar('registrar.faltan', 'Añadir «Sin novedad» en '.$cat, 'descartada', $residente);
        }

        if ($estado === 'confirmado' && $registro->incidencia_destacada) {
            Aviso::create(['tipo' => 'incidencia', 'titulo' => $registro->tipo_incidencia.' · '.$residente->nombre, 'residente_id' => $residente->id,
                'detalle' => $registro->resumenCorto(), 'urgencia' => 1]);
        }
        $this->aviso(match ($estado) {
            'sin_novedad' => 'Sin novedad. Registrado.',
            'borrador' => 'Guardado como borrador. Confírmalo antes de cerrar el turno.',
            default => $registro->incidencia_destacada ? 'Registrado. La dirección ha recibido el aviso.' : 'Registrado.',
        });

        return redirect()->route('auxiliar.residentes');
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
            'companeras' => $t->companeras()->map(fn ($c) => ['nombre' => $c['user']->name, 'foto' => Avatar::url($c['user']->foto, $c['user']->name),
                'planta' => $c['user']->planta?->nombre ?? 'Todas las plantas', 'enTurno' => $c['enTurno']])->values(),
            'equipo' => Residencia::actual()?->equipoHoy() ?? [],
        ]);
    }

    // ---------- Cerrar el turno: pendientes con motivo, relevo automático revisable y nota ----------

    public function cerrarTurno(): Response
    {
        $t = $this->t();
        $turno = $this->yo()->turnoAbierto();

        return Inertia::render('CerrarTurno', [
            'pendientes' => $t->agrupados()['por_registrar']->map(fn ($r) => $this->residente($r, $t->estado($r)))->values(),
            'relevo' => Registro::with('residente')->where('turno_id', $turno?->id)->where('estado', 'confirmado')->orderByDesc('incidencia_destacada')->get()
                ->map(fn ($r) => ['id' => $r->id, 'residente' => $r->residente->nombre, 'texto' => $r->resumenCorto(), 'incidencia' => (bool) $r->incidencia_destacada])->values(),
            'motivos' => Turno::MOTIVOS_CIERRE,
        ]);
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
        ], ['motivo.required' => 'Elige un motivo para cerrar con residentes sin registrar.', 'motivoTexto.required' => 'Cuéntalo en pocas palabras.']);

        $quitados = (array) $request->input('quitados', []);
        $lineas = Registro::with('residente')->where('turno_id', $turno?->id)->where('estado', 'confirmado')->whereNotIn('id', $quitados)
            ->orderByDesc('incidencia_destacada')->get()->map(fn ($r) => $r->residente->nombre.' · '.$r->resumenCorto())->all();
        if (trim((string) $request->input('nota')) !== '') {
            $lineas[] = trim($request->input('nota'));
        }
        $motivo = $request->input('motivo');
        if ($hay && $turno) {
            $h = (int) $turno->inicio->format('G');
            $franja = $h >= 7 && $h < 15 ? 'mañana' : ($h >= 15 && $h < 23 ? 'tarde' : 'noche');
            Aviso::create([
                'tipo' => 'turno_con_motivo',
                'titulo' => ($yo->planta?->nombre ?? 'Todas las plantas').' · '.$franja.' del '.$turno->inicio->format('d/m').' · '.$pendientes->count().' sin registrar · '
                    .mb_strtolower(Turno::MOTIVOS_CIERRE[$motivo] ?? $motivo).' · cerrado por '.$yo->name,
                'detalle' => $motivo === 'otros' ? $request->input('motivoTexto') : null, 'urgencia' => 2, 'estado' => 'pendiente',
            ]);
        }
        $turno?->update(['fin' => now(), 'motivo_cierre' => $hay ? $motivo : null, 'motivo_texto' => $motivo === 'otros' ? $request->input('motivoTexto') : null,
            'nota_relevo' => $lineas ? implode("\n", $lineas) : null]);

        auth('turno')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        session()->flash('aviso', ['texto' => 'Turno cerrado. Gracias.', 'tipo' => 'ok']);

        return redirect()->route('auxiliar.entrar');
    }
}
