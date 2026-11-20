<?php

namespace App\Filament\Turno\Pages;

use App\Support\TurnoActual;
use Filament\Facades\Filament;
use Filament\Pages\Page;

/**
 * A3 · Mi turno (116:937). Cómo va el turno, lo de la responsable y salir.
 * «Cambiar de persona» cierra la sesión en la tablet sin cerrar el turno.
 */
class MiTurno extends Page
{
    protected static ?string $title = 'Mi turno';

    protected static ?string $slug = 'mi-turno';

    protected static string $view = 'filament.turno.pages.mi-turno';

    public function getHeading(): string
    {
        $u = auth()->user();

        return $u->name.($u->responsable_turno ? ' · Responsable de turno' : '');
    }

    public function getSubheading(): ?string
    {
        $u = auth()->user();
        $t = $u->turnoAbierto();

        return ($u->planta?->nombre ?? 'Todas las plantas').' · Turno desde las '.$t?->inicio->format('H:i').' · '.now()->locale('es')->isoFormat('ddd D MMM');
    }

    public function cambiarDePersona()
    {
        Filament::auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->to(Filament::getLoginUrl());
    }

    protected function getViewData(): array
    {
        $t = TurnoActual::make();
        $g = $t->agrupados();
        $estados = $g['por_registrar']->map(fn ($record) => $t->estado($record));

        return [
            'registrados' => $g['registrados']->count(),
            'total' => $g['registrados']->count() + $g['por_registrar']->count(),
            'borradores' => $estados->filter(fn ($e) => $e === 'borrador')->count(),
            'pendientes' => $estados->filter(fn ($e) => $e === 'pendiente')->count(),
            'fuera' => $g['fuera']->count(),
        ];
    }
}
