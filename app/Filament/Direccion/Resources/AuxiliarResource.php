<?php

namespace App\Filament\Direccion\Resources;

use App\Filament\Direccion\Resources\AuxiliarResource\Pages;
use App\Models\Planta;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** 2 · Equipo: auxiliares, responsables de turno, PIN provisional y bajas. */
class AuxiliarResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Equipo';

    protected static ?string $modelLabel = 'auxiliar';

    protected static ?string $pluralModelLabel = 'Equipo';

    protected static ?string $slug = 'equipo';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('rol', 'auxiliar');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->label('Nombre y apellidos')->required(),
            Select::make('planta_id')->label('Planta')->options(Planta::pluck('nombre', 'id'))->placeholder('Todas las plantas'),
            Select::make('turno_habitual')->label('Turno habitual')->options(['manana' => 'Mañana', 'tarde' => 'Tarde', 'noche' => 'Noche'])->required(),
            Toggle::make('responsable_turno')->label('Responsable de turno')
                ->helperText('Puede restablecer el PIN de sus compañeras en la tablet o el móvil, también de noche.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Wireframe V2 · Equipo: «Planta 1 · Mañana · en turno ahora» bajo el nombre.
                TextColumn::make('name')->label('Auxiliar')->searchable()->weight('semibold')
                    ->description(fn (User $r) => self::lineaEstado($r)),
                IconColumn::make('responsable_turno')->label('Responsable de turno')->boolean()
                    ->trueIcon('heroicon-o-star')->falseIcon('heroicon-o-minus')->falseColor('gray'),
            ])
            ->filters([
                TernaryFilter::make('activo')->label('Estado')->trueLabel('Activas')->falseLabel('De baja')->default(true),
            ])
            ->actions([
                Action::make('restablecerPin')
                    ->label(fn (User $r) => $r->pin_provisional ? 'Nuevo PIN provisional' : 'Restablecer PIN')
                    ->color('gray')
                    ->icon('heroicon-o-key')
                    ->visible(fn (User $r) => $r->activo)
                    ->requiresConfirmation()
                    ->modalDescription('Se genera un PIN provisional. La auxiliar creará el suyo al entrar.')
                    ->action(function (User $r) {
                        $pin = $r->generarPinProvisional(auth()->user());
                        Notification::make()->title("PIN provisional de {$r->name}: {$pin}")
                            ->body('Comunícaselo en persona. Solo se muestra esta vez.')->persistent()->success()->send();
                    }),
                ActionGroup::make([
                    Action::make('responsable')
                        ->label(fn (User $r) => $r->responsable_turno ? 'Quitar como responsable de turno' : 'Hacer responsable de turno')
                        ->icon('heroicon-o-star')
                        ->action(fn (User $r) => $r->update(['responsable_turno' => ! $r->responsable_turno])),
                    Action::make('cambiarPlantaTurno')
                        ->label('Cambiar planta o turno')
                        ->icon('heroicon-o-arrows-right-left')
                        ->fillForm(fn (User $r) => ['planta_id' => $r->planta_id, 'turno_habitual' => $r->turno_habitual])
                        ->form([
                            Select::make('planta_id')->label('Planta')->options(fn () => Planta::pluck('nombre', 'id'))->placeholder('Todas las plantas'),
                            Select::make('turno_habitual')->label('Turno habitual')->options(['manana' => 'Mañana', 'tarde' => 'Tarde', 'noche' => 'Noche'])->required(),
                        ])
                        ->action(fn (User $r, array $data) => $r->update($data)),
                    Action::make('darDeBaja')
                        ->label('Dar de baja…')
                        ->icon('heroicon-o-user-minus')
                        ->color('danger')
                        ->visible(fn (User $r) => $r->activo)
                        ->requiresConfirmation()
                        ->modalDescription('Pierde el acceso a la tablet y al móvil. Sus registros se conservan.')
                        ->form([
                            Select::make('motivo')->label('Motivo')->required()
                                ->options(['fin_contrato' => 'Fin de contrato', 'cambio' => 'Cambio de centro', 'otros' => 'Otros']),
                            Textarea::make('nota')->label('Nota (opcional)')->rows(2),
                        ])
                        ->action(fn (User $r, array $data) => $r->update(['activo' => false, 'baja_motivo' => trim($data['motivo'].' '.($data['nota'] ?? ''))])),
                    Action::make('reactivar')->label('Reactivar')->icon('heroicon-o-arrow-path')
                        ->visible(fn (User $r) => ! $r->activo)
                        ->action(fn (User $r) => $r->update(['activo' => true, 'baja_motivo' => null])),
                ])->tooltip('Más opciones'),
            ]);
    }

    public static function lineaEstado(User $r): string
    {
        $partes = [$r->planta?->nombre ?? 'Todas las plantas', $r->etiquetaTurno()];
        $pin = self::estadoPin($r);
        if ($pin !== 'PIN asignado') {
            $partes[] = $pin;
        } elseif ($r->turnoAbierto()) {
            $partes[] = 'en turno ahora';
        }

        return implode(' · ', $partes);
    }

    public static function estadoPin(User $r): string
    {
        if (! $r->activo) {
            return 'De baja · sin acceso';
        }
        if ($r->pin_provisional) {
            return 'PIN provisional, pendiente de primer acceso';
        }
        if ($r->pin_restablecido_at) {
            $por = User::find($r->pin_restablecido_por)?->name ?? 'la dirección';

            return 'PIN restablecido por '.$por.' '.($r->pin_restablecido_at->isToday() ? 'hoy a las '.$r->pin_restablecido_at->format('H:i') : 'el '.$r->pin_restablecido_at->locale('es')->isoFormat('D MMM, HH:mm'));
        }

        return 'PIN asignado';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuxiliares::route('/'),
            'create' => Pages\CreateAuxiliar::route('/nueva'),
        ];
    }
}
