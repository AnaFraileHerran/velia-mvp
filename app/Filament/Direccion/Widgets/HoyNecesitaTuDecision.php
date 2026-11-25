<?php

namespace App\Filament\Direccion\Widgets;

use App\Support\TurnosSinAbrir;
use Filament\Facades\Filament;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use App\Models\Planta;
use App\Support\Franja;
use App\Models\Aviso;
use App\Models\Pregunta;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use App\Models\SugerenciaIa;
use App\Support\Ia\Sugerencias;

/** Hoy necesita tu decisión: como máximo 5 avisos, ordenados por urgencia. Cada uno se resuelve aquí (D2). */
class HoyNecesitaTuDecision extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public const MAXIMO = 5;

    protected string $view = 'filament.direccion.widgets.hoy-necesita-tu-decision';

    protected int|string|array $columnSpan = ['xl' => 2];

    protected static ?int $sort = 1;

    public bool $verTodos = false;

    /** Sugerencias que la directora ha descartado en esta visita (quedan anotadas en sugerencias_ia). */
    public array $descartadas = [];

    /** «Velia te sugiere» para un aviso, si la hay y no se ha descartado. */
    public function sugerencia(Aviso $aviso): ?array
    {
        if (in_array($aviso->id, $this->descartadas) || $aviso->tipo === 'turno_sin_abrir') {
            return null;
        }

        return Sugerencias::paraAviso($aviso);
    }

    /** Aceptar la sugerencia tal cual: un toque. Queda anotado quién la aceptó. */
    public function aceptarSugerencia(int $id): void
    {
        $aviso = Aviso::findOrFail($id);
        $s = Sugerencias::paraAviso($aviso);
        if (! $s) {
            return;
        }
        if ($s['tipo'] === 'asignar') {
            $aviso->update(['estado' => 'asignado', 'responsable_id' => $s['datos']['responsable_id'], 'vence_el' => $s['datos']['vence_el'], 'hecho_at' => null]);
            Notification::make()->title('Asignado. Lo verá en su relevo.')->body($s['texto'])->success()->send();
        } else {
            Pregunta::create(['residente_id' => $aviso->residente_id, 'aviso_id' => $aviso->id, 'texto' => $s['datos']['texto']]);
            $aviso->update(['estado' => 'pregunta']);
            Notification::make()->title('La pregunta aparece en el relevo del turno.')->success()->send();
        }
        SugerenciaIa::anotar('inicio.aviso', $s['texto'], 'aceptada', $aviso);
    }

    public function descartarSugerencia(int $id): void
    {
        $aviso = Aviso::findOrFail($id);
        if ($s = Sugerencias::paraAviso($aviso)) {
            SugerenciaIa::anotar('inicio.aviso', $s['texto'], 'descartada', $aviso);
        }
        $this->descartadas[] = $id;
    }

    /** Si la directora usa «Asignar» o «Preguntar al turno» y cambia lo sugerido, se anota como «cambiada». */
    private function anotarSiCambia(Aviso $aviso, array $data): void
    {
        $s = Sugerencias::paraAviso($aviso);
        if ($s && array_intersect_key($data, $s['datos']) != $s['datos']) {
            SugerenciaIa::anotar('inicio.aviso', $s['texto'], 'cambiada', $aviso, json_encode($data, JSON_UNESCAPED_UNICODE));
        } elseif ($s) {
            SugerenciaIa::anotar('inicio.aviso', $s['texto'], 'aceptada', $aviso);
        }
    }

    public function getAvisos()
    {
        TurnosSinAbrir::comprobar();

        $q = Aviso::abiertos()->where('tipo', '!=', 'turno_sin_abrir')->with('residente.planta');

        return $this->verTodos ? $q->get() : $q->limit(self::MAXIMO)->get();
    }

    public function getTotal(): int
    {
        TurnosSinAbrir::comprobar();

        return Aviso::abiertos()->where('tipo', '!=', 'turno_sin_abrir')->count();
    }

    public function asignarAction(): Action
    {
        return Action::make('asignar')
            ->label('Asignar')
            ->modalHeading('Asignar responsable')
            ->fillForm(fn (array $arguments) => (($s = Sugerencias::paraAviso(Aviso::find($arguments['aviso']))) && $s['tipo'] === 'asignar') ? $s['datos'] : ['vence_el' => today()->addDays(2)->toDateString()])
            ->schema([
                Select::make('responsable_id')->label('¿Quién hace el seguimiento?')
                    ->options(User::auxiliares()->where('activo', true)->pluck('name', 'id'))->required(),
                DatePicker::make('vence_el')->label('¿Para cuándo?')->native(false)->displayFormat('d/m/Y')
                    ->default(today()->addDays(2))->minDate(today())->required()
                    ->helperText('Si no está hecho ese día, vuelve a «Hoy necesita tu decisión».'),
            ])
            ->action(function (array $data, array $arguments) {
                $aviso = Aviso::findOrFail($arguments['aviso']);
                $this->anotarSiCambia($aviso, ['responsable_id' => (int) $data['responsable_id'], 'vence_el' => \Illuminate\Support\Carbon::parse($data['vence_el'])->toDateString()]);
                $aviso->update(['estado' => 'asignado', 'responsable_id' => $data['responsable_id'], 'vence_el' => $data['vence_el'], 'hecho_at' => null]);
                Notification::make()->title('Asignado. Lo verá en su relevo.')->success()->send();
            });
    }

    public function preguntarAction(): Action
    {
        return Action::make('preguntar')
            ->label(fn () => \App\Support\VersionH::activa() ? 'Preguntar' : 'Preguntar al turno')
            ->color('gray')
            ->fillForm(fn (array $arguments) => (($s = Sugerencias::paraAviso(Aviso::find($arguments['aviso']))) && $s['tipo'] === 'preguntar') ? $s['datos'] : [])
            ->schema([Textarea::make('texto')->label('Pregunta')->required()->rows(2)])
            ->action(function (array $data, array $arguments) {
                $aviso = Aviso::findOrFail($arguments['aviso']);
                $this->anotarSiCambia($aviso, ['texto' => $data['texto']]);
                Pregunta::create(['residente_id' => $aviso->residente_id, 'aviso_id' => $aviso->id, 'texto' => $data['texto']]);
                $aviso->update(['estado' => 'pregunta']);
                Notification::make()->title('La pregunta aparece en el relevo del turno.')->success()->send();
            });
    }

    /** «Ya lo he revisado»: lo he mirado y no hace falta nada más. Queda con nombre y fecha para la auditoría. */
    public function revisadoAction(): Action
    {
        return Action::make('revisado')
            ->label(fn () => \App\Support\VersionH::activa() ? 'Revisado' : 'Ya lo he revisado')
            ->icon('heroicon-o-check')
            ->color('gray')
            ->action(function (array $arguments) {
                $u = Filament::auth()->user();
                Aviso::findOrFail($arguments['aviso'])->update(['estado' => 'revisado', 'revisado_por' => $u->id, 'revisado_at' => now()]);
                Notification::make()->title('Revisado por '.strtok($u->name, ' ').', hoy a las '.now()->format('H:i'))
                    ->body('Queda en el historial para la auditoría.')->success()->send();
            });
    }

    /** «No procede» (sustituye a «Cerrar»): también deja rastro, con el motivo. */
    public function noProcedeAction(): Action
    {
        return Action::make('noProcede')
            ->label('No procede')
            ->link()
            ->color('primary')
            ->modalHeading('¿Por qué no procede este aviso?')
            ->modalDescription('Se quita de la lista y queda anotado con tu nombre, la fecha y el motivo.')
            ->modalSubmitActionLabel('Guardar')
            ->schema([
                Radio::make('motivo')->label('Motivo')->required()->options(Aviso::MOTIVOS_DESCARTE)->live(),
                TextInput::make('texto')->label('Cuéntalo en pocas palabras')
                    ->visible(fn (Get $get) => $get('motivo') === 'otro')->required(fn (Get $get) => $get('motivo') === 'otro'),
            ])
            ->action(function (array $data, array $arguments) {
                $motivo = Aviso::MOTIVOS_DESCARTE[$data['motivo']].(($data['texto'] ?? '') !== '' ? ': '.$data['texto'] : '');
                Aviso::findOrFail($arguments['aviso'])->update([
                    'estado' => 'descartado', 'revisado_por' => Filament::auth()->id(), 'revisado_at' => now(), 'motivo_descarte' => $motivo,
                ]);
                Notification::make()->title('Anotado como «no procede».')->body($motivo)->send();
            });
    }

    /** Turno sin abrir: quién debería estar, cómo localizarla y, si no puede entrar, ir a su ficha. */
    public function quienFaltaAction(): Action
    {
        return Action::make('quienFalta')
            ->label('Ver quién falta')
            ->icon('heroicon-o-phone')
            ->color('gray')
            ->modalHeading('¿Quién debería estar?')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(function (array $arguments) {
                $p = Planta::find($arguments['planta'] ?? null);

                return view('filament.direccion.widgets.quien-falta', [
                    'planta' => $p,
                    'personas' => $p ? TurnosSinAbrir::quienDeberia($p) : collect(),
                    'franja' => Franja::actual(),
                ]);
            });
    }
}
