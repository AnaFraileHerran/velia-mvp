<?php

namespace App\Filament\Direccion\Resources;

use Filament\Schemas\Schema;
use Filament\Actions\Action;
use App\Filament\Direccion\Resources\AusenciaResource\Pages\ManageAusencias;
use App\Filament\Direccion\Resources\AusenciaResource\Pages;
use App\Models\Ausencia;
use App\Models\Residente;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** 3 · Residentes › Ausencias: quién está fuera y cuándo vuelve. Las auxiliares lo ven en su relevo. */
class AusenciaResource extends Resource
{
    protected static ?string $model = Ausencia::class;

    protected static ?string $navigationLabel = 'Ausencias';

    protected static ?string $navigationParentItem = 'Residentes';

    protected static ?string $modelLabel = 'ausencia';

    protected static ?string $pluralModelLabel = 'Ausencias';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('residente_id')->label('Residente')->required()->searchable()
                ->options(Residente::activos()->orderBy('nombre')->pluck('nombre', 'id')),
            Select::make('tipo')->label('Tipo')->options(Ausencia::TIPOS)->required(),
            DatePicker::make('sale')->label('Sale')->default(now())->required(),
            DatePicker::make('vuelve')->label('Vuelve (si se sabe)'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('residente.nombre')->label('Residente')->weight('semibold')
                    ->description(fn (Ausencia $a) => 'Hab. '.$a->residente->habitacion),
                TextColumn::make('tipo')->formatStateUsing(fn ($state) => Ausencia::TIPOS[$state] ?? $state),
                TextColumn::make('sale')->date('D j M'),
                TextColumn::make('vuelve')->date('D j M')->placeholder('Sin fecha de vuelta')
                    ->color(fn (Ausencia $a) => $a->vuelve && $a->vuelve->isPast() && ! $a->vuelve->isToday() ? 'danger' : null)
                    ->description(fn (Ausencia $a) => $a->vuelve && $a->vuelve->isPast() && ! $a->vuelve->isToday() && ! $a->ha_vuelto ? 'Debía volver · confírmalo' : ($a->vuelve?->isToday() ? 'Vuelve hoy' : null)),
                TextColumn::make('registradaPor.name')->label('Registrada por')->placeholder('Dirección'),
            ])
            // Primero quien debía haber vuelto (sin confirmar), luego por fecha de vuelta.
            ->modifyQueryUsing(fn ($query) => $query->orderByRaw('CASE WHEN vuelve IS NULL THEN 1 ELSE 0 END')->orderBy('vuelve'))
            ->recordActions([
                Action::make('haVuelto')->label('Ha vuelto')->button()
                    ->visible(fn (Ausencia $a) => ! $a->ha_vuelto)
                    ->action(function (Ausencia $a) {
                        $a->update(['ha_vuelto' => true, 'vuelve' => $a->vuelve ?? now()]);
                        $a->residente->update(['estado' => 'en_centro']);
                    }),
                Action::make('cambiarFecha')->label('Cambiar fecha')->color('gray')
                    ->visible(fn (Ausencia $a) => ! $a->ha_vuelto)
                    ->fillForm(fn (Ausencia $a) => ['vuelve' => $a->vuelve])
                    ->schema([DatePicker::make('vuelve')->label('Vuelve')])
                    ->action(fn (Ausencia $a, array $data) => $a->update($data)),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAusencias::route('/')];
    }
}
