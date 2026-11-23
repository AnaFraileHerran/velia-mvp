<?php

namespace App\Filament\Direccion\Pages;

use Filament\Facades\Filament;
use App\Support\PendientesAuditoria;
use App\Filament\Direccion\Widgets\AhoraEnElCentro;
use App\Filament\Direccion\Widgets\AlertaTurno;
use App\Filament\Direccion\Widgets\ComoVaLaSemana;
use App\Filament\Direccion\Widgets\EstaSemana;
use App\Filament\Direccion\Widgets\HoyNecesitaTuDecision;
use App\Models\Residencia;
use Filament\Actions\Action;
use Filament\Pages\Dashboard;
use Illuminate\Contracts\Support\Htmlable;

/** 1 · Inicio (opción C, 23/11/2026): banda «Ahora» → «Hoy» y «Esta semana» en dos columnas (una en tablet) → «Últimos 7 días». */
class Inicio extends Dashboard
{
    protected static ?string $title = 'Inicio';

    protected static ?string $navigationLabel = 'Inicio';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = 1;

    /** Cabecera de una línea (opción C): la residencia; el saludo, pequeño en el subtítulo. */
    public function getHeading(): string|Htmlable
    {
        return Residencia::actual()?->nombre ?? 'Inicio';
    }

    public function getSubheading(): ?string
    {
        $nombre = explode(' ', Filament::auth()->user()->name)[0];
        $h = (int) now()->format('H');
        $saludo = $h < 14 ? 'Buenos días' : ($h < 21 ? 'Buenas tardes' : 'Buenas noches');

        return ucfirst(now()->locale('es')->isoFormat('dddd, D [de] MMMM')).' · '.$saludo.', '.$nombre;
    }

    protected function getHeaderActions(): array
    {
        $r = Residencia::actual();

        return [
            Action::make('auditoria')
                ->label(function () use ($r) {
                    if (! $r->proxima_auditoria) {
                        return 'Fija la próxima auditoría';
                    }
                    $n = PendientesAuditoria::resumen()['total'];

                    return 'Auditoría: '.$r->proxima_auditoria->locale('es')->isoFormat('D MMM YYYY').' · '.($n ? $n.' '.($n === 1 ? 'pendiente' : 'pendientes') : 'todo al día');
                })
                ->color('gray')
                ->icon('heroicon-o-shield-check')
                ->tooltip(function () {
                    $items = PendientesAuditoria::resumen()['items'];

                    return $items ? collect($items)->map(fn ($x) => $x['n'].' '.$x['texto'])->join(' · ').'. Ver en Calidad y auditoría.' : 'Todo al día con lo que mide Velia.';
                })
                ->url(Calidad::getUrl()),
        ];
    }

    public function getWidgets(): array
    {
        return [AlertaTurno::class, AhoraEnElCentro::class, HoyNecesitaTuDecision::class, EstaSemana::class, ComoVaLaSemana::class];
    }

    public function getColumns(): int|array
    {
        return ['xl' => 3];
    }
}
