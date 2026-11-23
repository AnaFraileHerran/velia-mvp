<?php

namespace App\Filament\Direccion\Resources\ResidenteResource\RelationManagers;

use App\Models\Registro;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/** Historial de registros del residente (solo lectura). */
class RegistrosRelationManager extends RelationManager
{
    protected static string $relationship = 'registros';

    protected static ?string $title = 'Historial de registros';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Cuándo')->dateTime('j M · H:i'),
                TextColumn::make('resumen')->label('Registro')->state(fn (Registro $r) => $r->resumenCorto())->wrap(),
                TextColumn::make('tipo_incidencia')->label('Incidencia')->badge()->color('danger')->placeholder('—'),
                TextColumn::make('user.name')->label('Quién'),
                TextColumn::make('estado')->badge()->formatStateUsing(fn ($state) => match ($state) { 'sin_novedad' => 'Sin novedad', 'confirmado' => 'Confirmado', default => 'Borrador' }),
            ])
            ->filters([TernaryFilter::make('incidencia_destacada')->label('Solo incidencias')]);
    }
}
