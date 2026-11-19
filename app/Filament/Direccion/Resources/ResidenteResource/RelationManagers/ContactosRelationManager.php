<?php

namespace App\Filament\Direccion\Resources\ResidenteResource\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Autorizados de la familia: reciben el resumen semanal por email o WhatsApp corporativo. */
class ContactosRelationManager extends RelationManager
{
    protected static string $relationship = 'contactos';

    protected static ?string $title = 'Autorizados de la familia';

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('nombre')->required(),
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
                TextColumn::make('nombre')->description(fn ($r) => $r->relacion),
                TextColumn::make('telefono')->label('Teléfono'),
                TextColumn::make('email'),
                TextColumn::make('canal')->badge()->formatStateUsing(fn ($s) => $s === 'whatsapp' ? 'WhatsApp' : 'Email'),
                IconColumn::make('emergencia')->boolean(),
                IconColumn::make('recibe_resumen')->label('Resumen')->boolean(),
            ])
            ->headerActions([CreateAction::make()->label('Añadir contacto')])
            ->actions([EditAction::make()]);
    }
}
