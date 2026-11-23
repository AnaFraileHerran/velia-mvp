<?php

namespace App\Filament\Turno\Pages;

use App\Filament\Turno\Concerns\ComoAtender;
use App\Models\Ausencia;
use App\Models\Aviso;
use App\Models\Pregunta;
use App\Support\TurnoActual;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * A1 · Relevo del turno (116:706). Pantalla de inicio de la auxiliar.
 * Jerarquía: 1) pregunta de la dirección (espera respuesta), 2) residentes con novedades
 * (incidencias primero), 3) plegado: vuelve hoy, notas de compañeras, sin novedades.
 */
class Relevo extends Page implements HasActions
{
    use ComoAtender;
    use InteractsWithActions;

    protected static ?string $title = 'Resumen';

    protected static ?string $slug = 'relevo';

    protected static string $view = 'filament.turno.pages.relevo';

    /** @var array<int, string> */
    public array $respuestas = [];

    public static function getRoutePath(): string
    {
        return '/';
    }

    public function getHeading(): string
    {
        $h = (int) now()->format('G');
        $saludo = $h < 14 ? 'Buenos días' : ($h < 21 ? 'Buenas tardes' : 'Buenas noches');

        return $saludo.', '.strtok(\Filament\Facades\Filament::auth()->user()->name, ' ');
    }

    public function getSubheading(): ?string
    {
        $u = \Filament\Facades\Filament::auth()->user();

        return ($u->planta?->nombre ?? 'Todas las plantas').' · Turno de '.mb_strtolower($u->etiquetaTurno()).' · '.now()->locale('es')->isoFormat('ddd D MMM');
    }

    public function responder(int $id): void
    {
        $texto = trim($this->respuestas[$id] ?? '');
        if ($texto === '') {
            return;
        }
        Pregunta::findOrFail($id)->update(['respuesta' => $texto, 'respondida_por' => \Filament\Facades\Filament::auth()->id()]);
        unset($this->respuestas[$id]);
        Notification::make()->title('Respuesta enviada a la dirección.')->success()->send();
    }

    public function hecho(int $avisoId): void
    {
        Aviso::asignadosA(\Filament\Facades\Filament::auth()->id())->whereKey($avisoId)->update(['hecho_at' => now(), 'estado' => 'hecho']);
        Notification::make()->title('Hecho. La dirección lo verá en el historial.')->success()->send();
    }

    public function haVuelto(int $ausenciaId): void
    {
        $a = Ausencia::findOrFail($ausenciaId);
        $a->update(['ha_vuelto' => true]);
        $a->residente->update(['estado' => 'en_centro']);
        Notification::make()->title($a->residente->nombre.' ha vuelto.')->success()->send();
    }

    protected function getViewData(): array
    {
        return ['t' => TurnoActual::make(), 'asignados' => Aviso::asignadosA(\Filament\Facades\Filament::auth()->id())->with('residente')->get()];
    }
}
