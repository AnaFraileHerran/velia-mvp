<?php

namespace App\Filament\Direccion\Resources\ResidenteResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Autorizados de la familia: reciben el resumen semanal por email o WhatsApp corporativo. */
class ContactosRelationManager extends RelationManager
{
    protected static string $relationship = 'contactos';

    protected static ?string $title = 'Autorizados de la familia';

    // En la ficha (página «ver») Filament deja las pestañas en solo lectura: aquí sí se añaden contactos.
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')->label('Nombre')->required(),
            TextInput::make('relacion')->label('Relación')->required(),
            TextInput::make('telefono')->label('Teléfono')->tel(),
            TextInput::make('email')->email(),
            Select::make('canal')->label('Canal del resumen')->options(['email' => 'Email', 'whatsapp' => 'WhatsApp corporativo'])->default('email'),
            Toggle::make('emergencia')->label('Contacto de emergencia'),
            Toggle::make('recibe_resumen')->label('Recibe el resumen semanal')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->description(fn ($record) => $record->relacion),
                TextColumn::make('telefono')->label('Teléfono'),
                TextColumn::make('email'),
                TextColumn::make('canal')->badge()->formatStateUsing(fn ($state) => $state === 'whatsapp' ? 'WhatsApp' : 'Email'),
                IconColumn::make('emergencia')->boolean(),
                IconColumn::make('recibe_resumen')->label('Resumen')->boolean(),
            ])
            ->headerActions([CreateAction::make()->label('Añadir contacto')->modalHeading('Añadir contacto')->createAnother(false)])
            ->recordActions([EditAction::make()]);
    }
}
