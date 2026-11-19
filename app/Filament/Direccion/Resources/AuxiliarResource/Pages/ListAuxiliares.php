<?php

namespace App\Filament\Direccion\Resources\AuxiliarResource\Pages;

use App\Filament\Direccion\Resources\AuxiliarResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAuxiliares extends ListRecords
{
    protected static string $resource = AuxiliarResource::class;

    public function getSubheading(): ?string
    {
        $n = User::auxiliares()->where('activo', true)->count();

        return "{$n} auxiliares activas · Las responsables de turno pueden restablecer el PIN en la tablet.";
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Añadir auxiliar')];
    }
}
