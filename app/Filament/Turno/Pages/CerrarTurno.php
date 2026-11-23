<?php

namespace App\Filament\Turno\Pages;

use App\Models\Aviso;
use App\Models\Registro;
use App\Models\Turno;
use App\Support\TurnoActual;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * A3 · Cerrar el turno (116:970). Con pendientes, el motivo es obligatorio («Otros» pide texto).
 * El relevo automático se puede revisar y quitar líneas antes de cerrar.
 */
class CerrarTurno extends Page
{
    protected static ?string $title = 'Cerrar el turno';

    protected static ?string $slug = 'cerrar-turno';

    protected string $view = 'filament.turno.pages.cerrar-turno';

    public ?string $motivo = null;

    public string $motivoTexto = '';

    public string $nota = '';

    /** @var array<int> */
    public array $quitados = [];

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function pendientes()
    {
        $t = TurnoActual::make();

        return $t->agrupados()['por_registrar']->map(fn ($record) => ['r' => $record, 'estado' => $t->estado($record)]);
    }

    public function relevo()
    {
        $turno = Filament::auth()->user()->turnoAbierto();

        return Registro::with('residente')->where('turno_id', $turno?->id)->where('estado', 'confirmado')
            ->whereNotIn('id', $this->quitados)->orderByDesc('incidencia_destacada')->get();
    }

    public function quitar(int $id): void
    {
        $this->quitados[] = $id;
    }

    public function cerrar()
    {
        $hayPendientes = $this->pendientes()->isNotEmpty();
        $this->validate([
            'motivo' => $hayPendientes ? 'required|in:'.implode(',', array_keys(Turno::MOTIVOS_CIERRE)) : 'nullable',
            'motivoTexto' => $this->motivo === 'otros' ? 'required|min:3' : 'nullable',
        ], [
            'motivo.required' => 'Elige un motivo para cerrar con residentes sin registrar.',
            'motivoTexto.required' => 'Cuéntalo en pocas palabras.',
        ]);

        $lineas = $this->relevo()->map(fn ($record) => $record->residente->nombre.' · '.$record->resumenCorto())->all();
        if (trim($this->nota) !== '') {
            $lineas[] = trim($this->nota);
        }

        $u = Filament::auth()->user();
        $turno = $u->turnoAbierto();
        if ($hayPendientes && $turno) {
            // Aviso para «Hoy necesita tu decisión» con todo el contexto: planta, turno, fecha, cuántos, motivo y quién (decisión 23/11/2026)
            $h = (int) $turno->inicio->format('G');
            $franja = $h >= 7 && $h < 15 ? 'mañana' : ($h >= 15 && $h < 23 ? 'tarde' : 'noche');
            $n = $this->pendientes()->count();
            Aviso::create([
                'tipo' => 'turno_con_motivo',
                'titulo' => ($u->planta?->nombre ?? 'Todas las plantas').' · '.$franja.' del '.$turno->inicio->format('d/m').' · '.$n.' sin registrar · '
                    .mb_strtolower(Turno::MOTIVOS_CIERRE[$this->motivo] ?? $this->motivo).' · cerrado por '.$u->name,
                'detalle' => $this->motivo === 'otros' ? $this->motivoTexto : null,
                'urgencia' => 2,
                'estado' => 'pendiente',
            ]);
        }

        $turno?->update([
            'fin' => now(),
            'motivo_cierre' => $hayPendientes ? $this->motivo : null,
            'motivo_texto' => $this->motivo === 'otros' ? $this->motivoTexto : null,
            'nota_relevo' => $lineas ? implode("\n", $lineas) : null,
        ]);

        Filament::auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        Notification::make()->title('Turno cerrado. Gracias.')->success()->send();

        return redirect()->to(Filament::getLoginUrl());
    }
}
