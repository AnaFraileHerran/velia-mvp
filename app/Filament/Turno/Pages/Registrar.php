<?php

namespace App\Filament\Turno\Pages;

use Filament\Facades\Filament;
use App\Models\Aviso;
use App\Models\Registro;
use App\Models\Residente;
use App\Support\OrdenarRegistro;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

/**
 * A2 · Registrar (grabar 117:274 · sin micrófono 116:887 · revisar 117:312).
 * Grabar → la IA ordena (simulado con OrdenarRegistro) → la auxiliar revisa y confirma.
 * Nada bloquea (regla 3): «Escribir» siempre visible y «Sin novedad» con un toque.
 */
class Registrar extends Page
{
    protected static ?string $slug = 'registrar';

    protected string $view = 'filament.turno.pages.registrar';

    #[Url]
    public ?int $residente = null;

    /** grabar | escribir | revisar */
    public string $paso = 'grabar';

    public string $texto = '';

    /** @var array<string, string> */
    public array $categorias = [];

    public bool $incidencia = false;

    public ?string $tipoIncidencia = null;

    public ?int $registroId = null;

    public const TIPOS_INCIDENCIA = ['Caídas y mareos', 'Piel y úlceras', 'Alimentación e hidratación', 'Conducta', 'Otra'];

    public function mount(): void
    {
        abort_unless($this->residente && $this->r(), 404);
        // Si dejó un borrador en este turno, se retoma en «Revisar».
        $b = Registro::where('residente_id', $this->residente)->where('turno_id', Filament::auth()->user()->turnoAbierto()?->id)
            ->where('estado', 'borrador')->latest()->first();
        if ($b) {
            $this->registroId = $b->id;
            $this->texto = (string) $b->texto_original;
            $this->categorias = $b->categorias ?? [];
            $this->incidencia = $b->incidencia_destacada;
            $this->tipoIncidencia = $b->tipo_incidencia;
            $this->paso = 'revisar';
        }
    }

    public function r(): ?Residente
    {
        return Residente::find($this->residente);
    }

    public function getTitle(): string
    {
        return $this->paso === 'revisar' ? 'Revisa el registro de '.strtok($this->r()->nombre, ' ') : $this->r()->nombre;
    }

    public function getSubheading(): ?string
    {
        return 'Hab. '.$this->r()->habitacion.' · '.now()->locale('es')->isoFormat('ddd D MMM');
    }

    /** Llega del dictado (Web Speech API) o del cuadro de texto. */
    public function ordenar(): void
    {
        if (trim($this->texto) === '') {
            Notification::make()->title('No hemos recogido nada. Prueba otra vez o escribe.')->warning()->send();

            return;
        }
        $o = OrdenarRegistro::ordenar($this->texto);
        $this->categorias = $o['categorias'];
        $this->incidencia = $o['incidencia'];
        $this->tipoIncidencia = $o['tipo_incidencia'];
        $this->paso = 'revisar';
    }

    public function quitarCategoria(string $cat): void
    {
        unset($this->categorias[$cat]);
    }

    public function sinNovedad()
    {
        return $this->guardar('sin_novedad');
    }

    public function borrador()
    {
        return $this->guardar('borrador');
    }

    public function confirmar()
    {
        return $this->guardar('confirmado');
    }

    private function guardar(string $estado)
    {
        $turno = Filament::auth()->user()->turnoAbierto();
        $datos = [
            'residente_id' => $this->residente,
            'user_id' => Filament::auth()->id(),
            'turno_id' => $turno?->id,
            'texto_original' => $estado === 'sin_novedad' ? null : $this->texto,
            'categorias' => $estado === 'sin_novedad' ? [] : array_filter($this->categorias, fn ($v) => trim($v) !== ''),
            'estado' => $estado,
            'incidencia_destacada' => $estado !== 'sin_novedad' && $this->incidencia,
            'tipo_incidencia' => $this->incidencia ? ($this->tipoIncidencia ?: 'Otra') : null,
        ];
        $registro = $this->registroId ? tap(Registro::find($this->registroId))->update($datos) : Registro::create($datos);

        if ($estado === 'confirmado' && $registro->incidencia_destacada) {
            Aviso::create([
                'tipo' => 'incidencia',
                'titulo' => $registro->tipo_incidencia.' · '.$this->r()->nombre,
                'residente_id' => $this->residente,
                'detalle' => $registro->resumenCorto(),
                'urgencia' => 1,
            ]);
        }

        Notification::make()->title(match ($estado) {
            'sin_novedad' => 'Sin novedad. Registrado.',
            'borrador' => 'Guardado como borrador. Confírmalo antes de cerrar el turno.',
            default => $registro->incidencia_destacada ? 'Registrado. La dirección ha recibido el aviso.' : 'Registrado.',
        })->success()->send();

        $this->js("try { localStorage.removeItem('velia-pendiente-{$this->residente}') } catch (e) {}");
        return redirect()->to(Residentes::getUrl());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
