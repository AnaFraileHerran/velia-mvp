<?php

namespace App\Filament\Direccion\Resources\ResumenResource\Pages;

use App\Filament\Direccion\Pages\Ocupacion;
use App\Filament\Direccion\Resources\ResumenResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;

class ManageResumenes extends ManageRecords
{
    protected static string $resource = ResumenResource::class;

    protected static ?string $title = 'Familias';

    protected ?string $subheading = 'Resúmenes de la semana, generados con los registros confirmados. Revísalos antes de enviar.';

    protected function getHeaderActions(): array
    {
        return [Action::make('ocupacion')->label('Residencia y ocupación')->color('gray')->url(Ocupacion::getUrl())];
    }
}
