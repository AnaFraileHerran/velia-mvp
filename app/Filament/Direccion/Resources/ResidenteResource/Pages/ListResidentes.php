<?php

namespace App\Filament\Direccion\Resources\ResidenteResource\Pages;

use App\Filament\Direccion\Resources\AusenciaResource;
use App\Filament\Direccion\Resources\ResidenteResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListResidentes extends ListRecords
{
    protected static string $resource = ResidenteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ausencias')->label('Ausencias')->color('gray')->icon('heroicon-o-arrow-right-start-on-rectangle')->url(AusenciaResource::getUrl()),
            CreateAction::make()->label('Añadir residente'),
        ];
    }
}
