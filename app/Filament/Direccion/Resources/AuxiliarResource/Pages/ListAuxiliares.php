<?php

namespace App\Filament\Direccion\Resources\AuxiliarResource\Pages;

use App\Filament\Direccion\Concerns\MigasDePan;
use Filament\Schemas\Components\Tabs\Tab;
use App\Filament\Direccion\Resources\AuxiliarResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAuxiliares extends ListRecords
{
    use MigasDePan;

    protected static string $resource = AuxiliarResource::class;

    public function getSubheading(): ?string
    {
        $n = User::auxiliares()->where('activo', true)->count();
        $s = User::sanitarios()->where('activo', true)->count();

        return "{$n} auxiliares · {$s} de enfermería y medicina";
    }

    public function getTabs(): array
    {
        return [
            'auxiliares' => Tab::make('Auxiliares')
                ->badge(User::auxiliares()->where('activo', true)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('rol', 'auxiliar')),
            'sanitarios' => Tab::make('Enfermería y medicina')
                ->badge(User::sanitarios()->where('activo', true)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('rol', 'sanitario')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('sanitario')->label('Añadir enfermería o medicina')->color('gray')
                ->url(AuxiliarResource::getUrl('create', ['rol' => 'sanitario'])),
            CreateAction::make()->label('Añadir auxiliar'),
        ];
    }
}
