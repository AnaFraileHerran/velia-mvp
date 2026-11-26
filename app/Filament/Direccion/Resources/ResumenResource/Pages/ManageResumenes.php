<?php

namespace App\Filament\Direccion\Resources\ResumenResource\Pages;

use App\Filament\Direccion\Concerns\MigasDePan;
use App\Filament\Direccion\Pages\Ocupacion;
use App\Filament\Direccion\Resources\ResumenResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use App\Models\Resumen;
use App\Support\VersionH;
use Filament\Schemas\Components\Tabs\Tab;

class ManageResumenes extends ManageRecords
{
    use MigasDePan;

    protected static string $resource = ResumenResource::class;

    protected static ?string $title = 'Familias';

    protected ?string $subheading = 'Resúmenes de la semana, generados con los registros confirmados. Revísalos antes de enviar.';

    public function getTitle(): string
    {
        return VersionH::activa() ? 'Resúmenes de la semana' : 'Familias';
    }

    protected function migas(): array
    {
        return VersionH::activa() ? [\App\Filament\Direccion\Pages\Familias::getUrl() => 'Familias'] : [];
    }

    protected function getHeaderActions(): array
    {
        // Nueva versión: la ocupación sale de Familias (comentario de Pablo en FAM-04): está en Residentes y en el Inicio.
        if (VersionH::activa()) {
            return [];
        }

        return [Action::make('ocupacion')->label('Residencia y ocupación')->color('gray')->url(Ocupacion::getUrl())];
    }

    /** Versión H: estados separados en pestañas con contador (arregla los estados mezclados, T4 de la auditoría del 23/11). */
    public function getTabs(): array
    {
        if (! VersionH::activa()) {
            return [];
        }

        $n = fn (string $estado) => Resumen::where('estado', $estado)->count();

        return [
            'por_revisar' => Tab::make('Por revisar')->modifyQueryUsing(fn ($query) => $query->where('estado', 'por_revisar'))->badge($n('por_revisar') ?: null)->badgeColor('warning'),
            'aprobado' => Tab::make('Aprobados')->modifyQueryUsing(fn ($query) => $query->where('estado', 'aprobado'))->badge($n('aprobado') ?: null),
            'enviado' => Tab::make('Enviados')->modifyQueryUsing(fn ($query) => $query->where('estado', 'enviado'))->badge($n('enviado') ?: null)->badgeColor('success'),
            'todos' => Tab::make('Todos'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return VersionH::activa() ? 'por_revisar' : null;
    }
}
