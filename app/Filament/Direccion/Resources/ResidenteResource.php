<?php

namespace App\Filament\Direccion\Resources;

use App\Filament\Direccion\Resources\ResidenteResource\Pages;
use App\Filament\Direccion\Resources\ResidenteResource\RelationManagers;
use App\Models\Planta;
use App\Models\Residente;
use App\Models\User;
use App\Support\Correcciones;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Arr;

/**
 * 3 · Residentes: lista y ficha. Sin pantalla de edición libre (regla 5):
 * cada bloque de la ficha tiene «Corregir», que pide motivo y guarda versión.
 */
class ResidenteResource extends Resource
{
    protected static ?string $model = Residente::class;

    protected static ?string $navigationLabel = 'Residentes';

    protected static ?string $modelLabel = 'residente';

    protected static ?string $pluralModelLabel = 'Residentes';

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 3;

    /** Campos que se pueden corregir, agrupados como los bloques de la ficha. */
    public static function camposCorregibles(): array
    {
        $plantas = Planta::pluck('nombre', 'id')->all();

        // Bloques de la ficha en el orden del wireframe «V2 · Residentes · lista y ficha».
        return [
            'Cuidados' => [
                TextInput::make('alergias')->label('Alergias'),
                TextInput::make('riesgos')->label('Riesgos'),
                TextInput::make('movilidad')->label('Movilidad'),
                Toggle::make('sujecion')->label('Usa sujeción')->helperText('La UNE pide registrar cada sujeción.'),
                Textarea::make('como_tratarle')->label('Cómo tratarle')->rows(2),
            ],
            'Dieta' => [
                TextInput::make('dieta')->label('Dieta y textura'),
            ],
            'Plan personal' => [
                DatePicker::make('plan_ultima_revision')->label('Última revisión'),
                DatePicker::make('plan_proxima_revision')->label('Próxima revisión'),
                Select::make('plan_responsable_id')->label('Responsable')->options(User::auxiliares()->where('activo', true)->pluck('name', 'id')),
            ],
            'Informes' => [
                FileUpload::make('informes')->label('Informes (PDF)')->multiple()->acceptedFileTypes(['application/pdf'])
                    ->directory('informes')->preserveFilenames(),
            ],
            'Notas' => [
                Textarea::make('notas')->label('Notas')->rows(3),
            ],
            'Datos y ubicación' => [
                TextInput::make('dni')->label('DNI / NIE'),
                DatePicker::make('fecha_nacimiento')->label('Fecha de nacimiento'),
                DatePicker::make('fecha_ingreso')->label('Fecha de ingreso'),
                Select::make('planta_id')->label('Planta')->options($plantas),
                TextInput::make('habitacion')->label('Habitación'),
                Select::make('tipo_habitacion')->label('Tipo de habitación')->options(['individual' => 'Individual', 'doble' => 'Doble']),
            ],
        ];
    }

    /** Formulario completo: solo al dar de alta (Nuevo residente). */
    public static function form(Form $form): Form
    {
        // Wireframe «V2 · Nuevo residente»: Datos y ubicación → Ficha de cuidados → Plan personal y contactos.
        $c = self::camposCorregibles();

        return $form->schema([
            FormSection::make('Datos y ubicación')->schema([
                TextInput::make('nombre')->label('Nombre y apellidos')->required()->columnSpanFull(),
                ...$c['Datos y ubicación'],
            ])->columns(2),
            FormSection::make('Ficha de cuidados')
                ->description('Sin ficha, una auxiliar nueva no sabrá cómo atender a este residente.')
                ->schema([...$c['Cuidados'], ...$c['Dieta']])->columns(2),
            FormSection::make('Plan personal y contactos')
                ->description('Valoración inicial: antes de un mes desde el ingreso (Orden 2680/2024). Se creará un aviso en Inicio. Sin contactos, no se podrá enviar el resumen semanal a la familia.')
                ->schema([
                    Repeater::make('contactos')->relationship()->label('')->addActionLabel('Añadir contacto')
                        ->schema([
                            TextInput::make('nombre')->required(),
                            TextInput::make('relacion')->label('Relación')->required(),
                            TextInput::make('telefono')->label('Teléfono')->tel(),
                            TextInput::make('email')->email(),
                            Select::make('canal')->label('Canal del resumen')->options(['email' => 'Email', 'whatsapp' => 'WhatsApp corporativo'])->default('email'),
                            Toggle::make('emergencia')->label('Contacto de emergencia'),
                        ])->columns(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Residente')->searchable()->weight('semibold')
                    ->description(fn (Residente $r) => ($r->planta?->nombre ?? '—').' · Hab. '.$r->habitacion),
                TextColumn::make('estado')->label('Estado')->badge()
                    ->formatStateUsing(fn ($state, Residente $r) => $r->etiquetaEstado())
                    ->color(fn ($state) => match ($state) { 'en_centro' => 'success', 'fuera' => 'warning', default => 'gray' }),
                TextColumn::make('plan_proxima_revision')->label('Revisión del plan')->date('j M Y')
                    ->color(fn (Residente $r) => $r->plan_proxima_revision?->isPast() ? 'danger' : null),
            ])
            ->defaultSort('habitacion')
            ->filters([
                SelectFilter::make('planta_id')->label('Planta')->options(fn () => Planta::pluck('nombre', 'id')),
                SelectFilter::make('estado')->label('Estado')
                    ->options(['en_centro' => 'En el centro', 'fuera' => 'Fuera', 'baja' => 'Bajas'])
                    ->query(fn ($query, array $data) => $data['value'] ? $query->where('estado', $data['value']) : $query->where('estado', '!=', 'baja')),
            ])
            ->recordUrl(fn (Residente $r) => static::getUrl('view', ['record' => $r]));
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        // Orden del wireframe: Emergencia · Cuidados · Dieta · Plan personal · Familia · Informes · Notas.
        $ficha = [
            'Cuidados' => [
                TextEntry::make('riesgos')->label('Riesgos')->placeholder('—'),
                TextEntry::make('movilidad')->label('Movilidad')->placeholder('—'),
                TextEntry::make('sujecion')->label('Sujeción')->formatStateUsing(fn ($state) => $state ? 'Sí, registrar cada uso' : 'No indicada'),
                TextEntry::make('como_tratarle')->label('Cómo tratarle')->placeholder('—')->columnSpanFull(),
            ],
            'Dieta' => [TextEntry::make('dieta')->hiddenLabel()->placeholder('—')],
            'Plan personal' => [
                TextEntry::make('plan_proxima_revision')->label('Próxima revisión')->date('j M Y')->placeholder('—')
                    ->color(fn (Residente $r) => $r->plan_proxima_revision?->isPast() ? 'danger' : null),
                TextEntry::make('planResponsable.name')->label('Responsable')->placeholder('Sin asignar'),
                TextEntry::make('plan_ultima_revision')->label('Última revisión')->date('j M Y')->placeholder('—'),
            ],
            'Informes' => [
                TextEntry::make('informes')->hiddenLabel()->placeholder('Sin informes')->listWithLineBreaks()
                    ->formatStateUsing(fn ($state) => basename((string) $state)),
            ],
            'Notas' => [TextEntry::make('notas')->hiddenLabel()->placeholder('Sin notas')->columnSpanFull()],
            'Datos y ubicación' => [
                TextEntry::make('dni')->label('DNI / NIE')->placeholder('—'),
                TextEntry::make('fecha_nacimiento')->label('Nacimiento')->date('j M Y')->placeholder('—'),
                TextEntry::make('fecha_ingreso')->label('Ingreso')->date('j M Y')->placeholder('—'),
            ],
        ];

        $sections = [
            Section::make()
                ->schema([
                    TextEntry::make('emergencia')->hiddenLabel()->weight('semibold')
                        ->state(function (Residente $r) {
                            $c = $r->contactoEmergencia();
                            $linea = $c ? "Emergencia: {$c->nombre} ({$c->relacion}) · {$c->telefono}" : 'Falta el contacto de emergencia';

                            return $linea.' · Alergia: '.($r->alergias ?: 'ninguna conocida');
                        }),
                ])->compact(),
        ];
        foreach ($ficha as $titulo => $entries) {
            $sections[] = Section::make($titulo)
                ->schema($entries)
                ->columns(2)
                ->collapsible($titulo === 'Datos y ubicación')
                ->collapsed($titulo === 'Datos y ubicación')
                ->headerActions([self::corregirAction($titulo)]);
            if ($titulo === 'Plan personal') {
                $sections[] = Section::make('Familia')
                    ->schema([
                        TextEntry::make('familia')->hiddenLabel()->listWithLineBreaks()
                            ->state(fn (Residente $r) => $r->contactos->map(fn ($c) => "{$c->nombre} · {$c->relacion}".($c->recibe_resumen ? ' · recibe el resumen' : ''))->all())
                            ->placeholder('Sin contactos: no se podrá enviar el resumen semanal'),
                    ])
                    ->headerActions([
                        InfolistAction::make('corregir_familia')->label('Corregir')->icon('heroicon-o-pencil-square')->link()
                            ->url(fn (Residente $r) => static::getUrl('view', ['record' => $r]).'?activeRelationManager=2'),
                    ]);
            }
        }

        return $infolist->schema($sections);
    }

    /** «Corregir» de un bloque: mismos campos, motivo obligatorio, guarda versión. */
    public static function corregirAction(string $bloque): InfolistAction
    {
        $campos = self::camposCorregibles()[$bloque];
        $nombres = array_map(fn ($c) => $c->getName(), $campos);

        return InfolistAction::make('corregir_'.str($bloque)->slug('_'))
            ->label('Corregir')
            ->icon('heroicon-o-pencil-square')
            ->link()
            ->modalHeading("Corregir · {$bloque}")
            ->modalDescription(fn (Residente $record) => "{$record->nombre} · Hab. {$record->habitacion}. Se guarda una versión con fecha y persona. La auxiliar lo ve al momento en «Cómo atender».")
            ->modalSubmitActionLabel('Guardar con motivo')
            ->fillForm(fn (Residente $record) => Arr::only($record->attributesToArray(), $nombres))
            ->form([
                ...$campos,
                Textarea::make('motivo')->label('Motivo (obligatorio)')->required()->rows(2)
                    ->placeholder('Ej.: indicación de la logopeda tras la valoración'),
            ])
            ->action(function (Residente $record, array $data) use ($nombres) {
                $n = Correcciones::aplicar($record, Arr::only($data, $nombres), $data['motivo']);
                Notification::make()->title($n ? "Corregido ({$n} dato(s)). Queda en el historial." : 'No había cambios.')->success()->send();
            });
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RegistrosRelationManager::class,
            RelationManagers\CorreccionesRelationManager::class,
            RelationManagers\ContactosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResidentes::route('/'),
            'create' => Pages\CreateResidente::route('/nuevo'),
            'view' => Pages\ViewResidente::route('/{record}'),
        ];
    }
}
