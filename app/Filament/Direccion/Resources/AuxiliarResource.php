<?php

namespace App\Filament\Direccion\Resources;

use App\Filament\Direccion\Resources\AuxiliarResource\Pages;
use App\Models\Planta;
use App\Models\User;
use App\Models\Residencia;
use App\Support\Avatar;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Get;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid as InfoGrid;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\Split as InfoSplit;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * 2 · Equipo: auxiliares (entran con PIN) y enfermería y medicina (rol «sanitario»: no entran en la app,
 * pero las auxiliares ven en «Mi turno» quién está y cómo localizarle).
 */
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
        return parent::getEloquentQuery()->whereIn('rol', ['auxiliar', 'sanitario']);
    }

    public static function form(Form $form): Form
    {
        $aux = fn (Get $get) => $get('rol') !== 'sanitario';

        return $form->schema([
            Hidden::make('rol')->default(fn () => request()->query('rol') === 'sanitario' ? 'sanitario' : 'auxiliar'),
            \Filament\Forms\Components\Section::make('Datos personales')->columns(2)->schema([
                FileUpload::make('foto')->label('Foto')->avatar()->image()->imageEditor()->directory('fotos')
                    ->helperText('Ayuda a reconocerse en la tablet, sobre todo el primer día.')->columnSpanFull(),
                TextInput::make('name')->label('Nombre y apellidos')->required()->columnSpanFull(),
            ]),
            \Filament\Forms\Components\Section::make(fn (Get $get) => $get('rol') === 'sanitario' ? 'Puesto y horario' : 'Planta y turno')->columns(2)->schema([
            Select::make('planta_id')->label('Planta')->options(Planta::pluck('nombre', 'id'))->placeholder('Todas las plantas')->visible($aux),
            Select::make('turno_habitual')->label('Turno habitual')->options(['manana' => 'Mañana', 'tarde' => 'Tarde', 'noche' => 'Noche'])->required($aux)->visible($aux),
            TextInput::make('telefono')->label('Teléfono o extensión de trabajo')->placeholder('Ext. 120 o móvil de empresa')->visible($aux)
                ->helperText('Solo el de trabajo. El personal, únicamente si la persona lo autoriza.'),
            Toggle::make('responsable_turno')->label('Responsable de turno')->visible($aux)->columnSpanFull()
                ->helperText('Puede restablecer el PIN de sus compañeras en la tablet o el móvil, también de noche.'),
            ...self::camposSanitario(),
            ]),
        ]);
    }

    /** Ficha de la persona: lo que la dirección consulta antes de llamarla, asignarle algo o cambiarle el turno. */
    public static function infolist(Infolist $infolist): Infolist
    {
        $aux = fn (User $r) => $r->rol === 'auxiliar';
        $san = fn (User $r) => $r->rol === 'sanitario';

        return $infolist->schema([
            // 1. Cabecera: quién es, dónde y cuándo trabaja, y si está ahora
            InfoSection::make()->compact()->schema([InfoSplit::make([
                ImageEntry::make('foto')->hiddenLabel()->circular()->height(80)->grow(false)
                    ->state(fn (User $r) => Avatar::url($r->foto, $r->name)),
                InfoGrid::make(1)->schema([
                    TextEntry::make('rol')->hiddenLabel()->badge()->color('gray')
                        ->formatStateUsing(fn (User $r) => $r->rol === 'sanitario' ? $r->etiquetaPuesto() : ($r->responsable_turno ? 'Auxiliar · responsable de turno' : 'Auxiliar')),
                    TextEntry::make('donde')->hiddenLabel()->visible($aux)
                        ->state(fn (User $r) => ($r->planta?->nombre ?? 'Todas las plantas').' · turno de '.mb_strtolower($r->etiquetaTurno())),
                    TextEntry::make('ahora')->hiddenLabel()->visible($aux)->badge()
                        ->state(fn (User $r) => ! $r->activo ? 'De baja' : ($r->turnoAbierto() ? 'En turno ahora' : 'Fuera de turno'))
                        ->color(fn (User $r) => $r->turnoAbierto() ? 'success' : 'gray'),
                    TextEntry::make('telefono')->hiddenLabel()->visible($aux)->icon('heroicon-o-phone')
                        ->placeholder('Sin contacto de trabajo · añádelo en «Editar ficha»')->copyable(),
                ]),
            ])->verticallyAlignCenter()]),

            // 2. Lo que tiene pendiente: antes de asignarle algo, ves cuánto tiene ya
            InfoSection::make('Pendiente')->visible($aux)->schema([
                \Filament\Infolists\Components\ViewEntry::make('pendiente')->hiddenLabel()
                    ->view('filament.direccion.equipo.pendiente'),
            ]),

            // 3. Últimos turnos: hechos, no notas (nada de porcentajes ni rankings)
            InfoSection::make('Últimos turnos')->visible($aux)
                ->description('Para entender un turno cerrado con pendientes, no para puntuar a nadie.')
                ->schema([
                    \Filament\Infolists\Components\ViewEntry::make('turnos')->hiddenLabel()
                        ->view('filament.direccion.equipo.ultimos-turnos'),
                ]),

            // 4. Acceso a Velia (solo el dato que es cierto)
            InfoSection::make('Acceso a Velia')->visible($aux)->schema([
                TextEntry::make('pin_estado')->label('PIN')->state(fn (User $r) => self::estadoPin($r)),
            ]),

            // Enfermería y medicina: no usan la app
            InfoSection::make('Puesto y horario')->columns(2)->visible($san)->schema([
                TextEntry::make('puesto')->label('Puesto')->formatStateUsing(fn (User $r) => $r->etiquetaPuesto()),
                TextEntry::make('horario')->label('Días y horario')->state(fn (User $r) => $r->etiquetaHorario()),
                TextEntry::make('contacto')->label('Cómo localizarle')->placeholder('—')->copyable()->icon('heroicon-o-phone'),
                TextEntry::make('app')->label('En Velia')->state('No entra en la app. Las auxiliares le ven en «Mi turno».'),
            ]),
            InfoSection::make('Baja')->visible(fn (User $r) => ! $r->activo)->schema([
                TextEntry::make('baja_motivo')->label('Motivo')->placeholder('—'),
            ]),
        ]);
    }

    /** Puesto, días, horario y cómo localizarle: lo que ve la auxiliar en «Mi turno». */
    public static function camposSanitario(): array
    {
        $san = fn (Get $get) => $get('rol') === 'sanitario';

        return [
            Select::make('puesto')->label('Puesto')->options(Residencia::PUESTOS)->required($san)->visible($san),
            CheckboxList::make('dias')->label('Días que viene')->visible($san)
                ->options([1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'])->columns(7)->gridDirection('row'),
            TimePicker::make('desde')->label('Desde')->seconds(false)->visible($san),
            TimePicker::make('hasta')->label('Hasta')->seconds(false)->visible($san),
            TextInput::make('contacto')->label('Cómo localizarle')->placeholder('Ext. 210 o teléfono')->visible($san),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (User $r) => self::getUrl('view', ['record' => $r]))
            ->columns([
                // Wireframe V2 · Equipo: «Planta 1 · Mañana · en turno ahora» bajo el nombre.
                ImageColumn::make('foto')->label('')->circular()->size(44)->grow(false)
                    ->state(fn (User $r) => Avatar::url($r->foto, $r->name)),
                TextColumn::make('name')->label('Nombre')->searchable()->weight('semibold')
                    ->description(fn (User $r) => self::lineaEstado($r)),
                IconColumn::make('responsable_turno')->label('Responsable de turno')->boolean()
                    ->visible(fn ($livewire) => ($livewire->activeTab ?? 'auxiliares') === 'auxiliares')
                    ->trueIcon('heroicon-o-star')->falseIcon('heroicon-o-minus')->falseColor('gray'),
            ])
            ->filters([
                TernaryFilter::make('activo')->label('Estado')->trueLabel('Activas')->falseLabel('De baja')->default(true),
            ])
            ->actions([
                Action::make('editar')->label('Editar')->color('gray')->icon('heroicon-o-pencil-square')
                    ->visible(fn (User $r) => $r->activo)
                    ->url(fn (User $r) => self::getUrl('edit', ['record' => $r])),
                Action::make('editarSanitario')->visible(false)
                    ->fillForm(fn (User $r) => $r->only(['rol', 'foto', 'name', 'puesto', 'dias', 'desde', 'hasta', 'contacto']))
                    ->form(fn () => [
                        Hidden::make('rol'),
                        FileUpload::make('foto')->label('Foto')->avatar()->image()->imageEditor()->directory('fotos'),
                        TextInput::make('name')->label('Nombre y apellidos')->required(),
                        ...self::camposSanitario(),
                    ])
                    ->action(fn (User $r, array $data) => $r->update(collect($data)->except('rol')->all())),
                ActionGroup::make([
                    Action::make('foto')->label('Cambiar foto')->icon('heroicon-o-camera')
                        ->visible(fn (User $r) => $r->rol === 'auxiliar')
                        ->fillForm(fn (User $r) => ['foto' => $r->foto])
                        ->form([FileUpload::make('foto')->label('Foto')->avatar()->image()->imageEditor()->directory('fotos')])
                        ->action(fn (User $r, array $data) => $r->update(['foto' => $data['foto']])),
                    Action::make('responsable')
                        ->label(fn (User $r) => $r->responsable_turno ? 'Quitar como responsable de turno' : 'Hacer responsable de turno')
                        ->icon('heroicon-o-star')
                        ->visible(fn (User $r) => $r->rol === 'auxiliar')
                        ->action(fn (User $r) => $r->update(['responsable_turno' => ! $r->responsable_turno])),
                    Action::make('cambiarPlantaTurno')
                        ->label('Cambiar planta o turno')
                        ->icon('heroicon-o-arrows-right-left')
                        ->visible(fn (User $r) => $r->rol === 'auxiliar')
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
                        ->modalDescription(fn (User $r) => $r->rol === 'auxiliar' ? 'Pierde el acceso a la tablet y al móvil. Sus registros se conservan.' : 'Deja de aparecer en «Mi turno» de las auxiliares.')
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

    /** «No puede entrar» (decisión 23/11/2026): la dirección elige la situación, no el mecanismo (PIN o código del móvil). */
    public static function formNoPuedeEntrar(): array
    {
        return [
            \Filament\Forms\Components\Radio::make('que')->label('¿Qué le pasa?')->required()
                ->validationMessages(['required' => 'Elige qué le pasa. Cada opción cambia algo distinto.'])
                ->options([
                    'pin' => 'Ha olvidado su PIN',
                    'movil' => 'Tiene un móvil nuevo o no lo ha vinculado',
                ])
                ->descriptions([
                    'pin' => 'Se genera un PIN provisional. Al entrar, creará el suyo.',
                    'movil' => 'Se genera un código nuevo para vincular el móvil. El anterior deja de servir.',
                ]),
        ];
    }

    /** Genera el PIN provisional o el código del móvil y devuelve [título, valor, instrucción] para mostrarlo dentro de la ventana. */
    public static function resolverAcceso(User $r, string $que): array
    {
        $nombre = strtok($r->name, ' ');
        if ($que === 'movil') {
            $codigo = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(3)).random_int(100, 999);
            $r->update(['codigo_invitacion' => $codigo]);

            return ["Código para el móvil de {$nombre}", $codigo, 'Díselo en persona. Lo escribe al abrir Velia en su móvil. El código anterior ya no sirve.'];
        }
        $pin = $r->generarPinProvisional(\Filament\Facades\Filament::auth()->user());

        return ["PIN provisional de {$nombre}", $pin, 'Díselo en persona. Al entrar con él, creará su PIN.'];
    }

    public static function lineaEstado(User $r): string
    {
        if ($r->rol === 'sanitario') {
            return implode(' · ', array_filter([$r->etiquetaPuesto(), $r->etiquetaHorario(), $r->contacto, $r->activo ? null : 'De baja']));
        }
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
            'view' => Pages\ViewAuxiliar::route('/{record}'),
            'edit' => Pages\EditAuxiliar::route('/{record}/editar'),
        ];
    }
}
