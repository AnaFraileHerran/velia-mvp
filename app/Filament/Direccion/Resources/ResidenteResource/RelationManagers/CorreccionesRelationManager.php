<?php

namespace App\Filament\Direccion\Resources\ResidenteResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Correcciones con motivo: evidencia para la auditoría UNE 158101. */
class CorreccionesRelationManager extends RelationManager
{
    protected static string $relationship = 'correcciones';

    protected static ?string $title = 'Correcciones';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('created_at')->label('Cuándo')->dateTime('j M Y · H:i'),
            TextColumn::make('campo')->label('Dato'),
            TextColumn::make('antes')->label('Antes')->placeholder('—'),
            TextColumn::make('ahora')->label('Ahora')->placeholder('—'),
            TextColumn::make('motivo')->label('Motivo')->wrap(),
            TextColumn::make('user.name')->label('Quién'),
        ]);
    }
}
