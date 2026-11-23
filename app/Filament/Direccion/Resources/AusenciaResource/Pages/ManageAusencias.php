<?php

namespace App\Filament\Direccion\Resources\AusenciaResource\Pages;

use App\Filament\Direccion\Concerns\MigasDePan;
use App\Filament\Direccion\Resources\ResidenteResource;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Facades\Filament;
use App\Filament\Direccion\Resources\AusenciaResource;
use App\Models\Ausencia;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAusencias extends ManageRecords
{
    use MigasDePan;

    protected function migas(): array
    {
        return [ResidenteResource::getUrl() => 'Residentes'];
    }

    protected static string $resource = AusenciaResource::class;

    protected ?string $subheading = 'Quién está fuera y cuándo vuelve. Las auxiliares lo ven en su relevo.';

    /** Wireframe: «Fuera ahora · 3» y «Salidas previstas · 1». */
    public function getTabs(): array
    {
        $fuera = fn ($query) => $query->where('ha_vuelto', false)->whereDate('sale', '<=', today());
        $previstas = fn ($query) => $query->where('ha_vuelto', false)->whereDate('sale', '>', today());

        return [
            'fuera' => Tab::make('Fuera ahora')->modifyQueryUsing($fuera)->badge($fuera(Ausencia::query())->count()),
            'previstas' => Tab::make('Salidas previstas')->modifyQueryUsing($previstas)->badge($previstas(Ausencia::query())->count()),
            'vueltas' => Tab::make('Han vuelto')->modifyQueryUsing(fn ($query) => $query->where('ha_vuelto', true)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Registrar ausencia')
                ->mutateDataUsing(fn (array $data) => [...$data, 'registrada_por' => Filament::auth()->id()])
                ->after(fn (Ausencia $record) => $record->residente->update(['estado' => 'fuera'])),
        ];
    }
}
