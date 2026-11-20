<?php

namespace App\Filament\Direccion\Pages;

use App\Filament\Direccion\Widgets\EstaSemana;
use App\Filament\Direccion\Widgets\HoyNecesitaTuDecision;
use App\Models\Residencia;
use Filament\Actions\Action;
use Filament\Pages\Dashboard;
use Illuminate\Contracts\Support\Htmlable;

/** 1 · Inicio. Cabe en una pantalla: «Hoy» (máximo 5) y «Esta semana». Lo demás, en Calidad y auditoría. */
class Inicio extends Dashboard
{
    protected static ?string $title = 'Inicio';

    protected static ?string $navigationLabel = 'Inicio';

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = 1;

    public function getHeading(): string|Htmlable
    {
        $nombre = explode(' ', \Filament\Facades\Filament::auth()->user()->name)[0];
        $hora = (int) now()->format('H');
        $saludo = $hora < 14 ? 'Buenos días' : ($hora < 21 ? 'Buenas tardes' : 'Buenas noches');

        return "{$saludo}, {$nombre}";
    }

    public function getSubheading(): ?string
    {
        $r = Residencia::actual();

        return $r->nombre.' · '.now()->locale('es')->isoFormat('dddd, D [de] MMMM');
    }

    protected function getHeaderActions(): array
    {
        $r = Residencia::actual();

        return [
            Action::make('auditoria')
                ->label($r->proxima_auditoria ? 'Próxima auditoría: '.$r->proxima_auditoria->locale('es')->isoFormat('D MMM YYYY') : 'Fija la próxima auditoría')
                ->color('gray')
                ->icon('heroicon-o-shield-check')
                ->url(Calidad::getUrl()),
        ];
    }

    public function getWidgets(): array
    {
        return [HoyNecesitaTuDecision::class, EstaSemana::class];
    }

    public function getColumns(): int|string|array
    {
        return ['md' => 3];
    }
}
