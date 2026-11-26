<?php

namespace App\Filament\Direccion\Resources;

use Filament\Forms\Components\Radio;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Filters\Filter;
use Filament\Schemas\Components\Flex;
use Filament\Actions\Action;
use App\Filament\Direccion\Resources\ResidenteResource\RelationManagers\RegistrosRelationManager;
use App\Filament\Direccion\Resources\ResidenteResource\RelationManagers\CorreccionesRelationManager;
use App\Filament\Direccion\Resources\ResidenteResource\RelationManagers\ContactosRelationManager;
use App\Filament\Direccion\Resources\ResidenteResource\Pages\ListResidentes;
use App\Filament\Direccion\Resources\ResidenteResource\Pages\CreateResidente;
use App\Filament\Direccion\Resources\ResidenteResource\Pages\ViewResidente;
use App\Filament\Direccion\Resources\ResidenteResource\Pages;
use App\Filament\Direccion\Resources\ResidenteResource\RelationManagers;
use App\Models\Planta;
use App\Models\Residente;
use App\Models\User;
use App\Support\Correcciones;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ImageEntry;
use App\Support\Avatar;
use Filament\Tables\Columns\ImageColumn;
use Filament\Infolists\Components\TextEntry;
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

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

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
                Radio::make('sujecion')->label('¿Usa sujeción?')->inline()
                    ->options([1 => 'Sí', 0 => 'No'])->required()
                    ->helperText('La UNE pide registrar cada sujeción, con prescripción y consentimiento.')
                    ->afterStateHydrated(function ($component, $record) {
                        if ($record && ! $record->sujecion_anotada) {
                            $component->state(null); // sin anotar: obliga a elegir
                        }
                    }),
                Textarea::make('como_tratarle')->label('Cómo tratarle')->rows(2),
            ],
            'Dieta' => [
                TextInput::make('dieta')->label('Dieta y textura'),
            ],
            'Plan personal' => [
                DatePicker::make('plan_ultima_revision')->label('Última revisión'),
                DatePicker::make('plan_proxima_revision')->label('Próxima revisión'),
                Select::make('plan_responsable_id')->label('Profesional de referencia')
                    ->helperText('La UNE pide un profesional de referencia para cada plan personal.')
                    ->options(User::whereIn('rol', ['auxiliar', 'sanitario'])->where('activo', true)->orderBy('name')->pluck('name', 'id')),
            ],
            'Informes' => [
                FileUpload::make('informes')->label('Informes (PDF)')->multiple()->acceptedFileTypes(['application/pdf'])
                    ->directory('informes')->preserveFilenames(),
            ],
            'Notas' => [
                Textarea::make('notas')->label('Notas')->rows(3),
            ],
            'Datos y ubicación' => [
                FileUpload::make('foto')->label('Foto')->avatar()->image()->imageEditor()->directory('fotos')
                    ->helperText('Con su consentimiento o el de su representante. Ayuda a una auxiliar nueva a reconocerle.')->columnSpanFull(),
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
    public static function form(Schema $schema): Schema
    {
        // Wireframe «V2 · Nuevo residente»: Datos y ubicación → Ficha de cuidados → Plan personal y contactos.
        $c = self::camposCorregibles();

        return $schema->components([
            // Nuevo residente con IA (23/11/2026): la directora cuenta lo que sabe y Velia propone los campos de la ficha.
            Section::make('Cuéntaselo a Velia')
                ->description('Dicta o escribe lo que sabes de la persona y Velia rellena la ficha de cuidados. Tú lo revisas antes de guardar.')
                ->icon('heroicon-o-light-bulb')
                ->collapsible()
                ->schema([\Filament\Schemas\Components\View::make('filament.direccion.partials.dictar-ficha')])
                ->visibleOn('create'),
            Section::make('Datos y ubicación')->schema([
                TextInput::make('nombre')->label('Nombre y apellidos')->required()->columnSpanFull(),
                ...$c['Datos y ubicación'],
            ])->columns(2),
            Section::make('Ficha de cuidados')
                ->description('Sin ficha, una auxiliar nueva no sabrá cómo atender a este residente.')
                ->schema([...$c['Cuidados'], ...$c['Dieta']])->columns(2),
            Section::make('Plan personal y contactos')
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
        if (\App\Support\VersionH::activa()) {
            return self::tablaNuevaVersion($table);
        }

        return $table
            ->columns([
                ImageColumn::make('foto')->label('')->circular()->size(40)->grow(false)
                    ->defaultImageUrl(fn (Residente $r) => Avatar::iniciales($r->nombre)),
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
                Filter::make('sin_referencia')->label('Sin profesional de referencia')
                    ->query(fn ($query) => $query->whereNull('plan_responsable_id')),
                Filter::make('sujecion_sin_anotar')->label('Sujeción sin anotar')
                    ->query(fn ($query) => $query->where('sujecion_anotada', false)),
                Filter::make('plan_vencido')->label('Plan personal vencido')
                    ->query(fn ($query) => $query->whereDate('plan_proxima_revision', '<', today())),
                Filter::make('sin_emergencia')->label('Sin contacto de emergencia')
                    ->query(fn ($query) => $query->whereDoesntHave('contactos', fn ($c) => $c->where('emergencia', true))),
                SelectFilter::make('estado')->label('Estado')
                    ->options(['en_centro' => 'En el centro', 'fuera' => 'Fuera', 'baja' => 'Bajas'])
                    ->query(fn ($query, array $data) => $data['value'] ? $query->where('estado', $data['value']) : $query->where('estado', '!=', 'baja')),
            ])
            ->recordUrl(fn (Residente $r) => static::getUrl('view', ['record' => $r]));
    }

    /**
     * Nueva versión (26/11/2026, comentarios de Pablo en RES-01): la tabla sirve para comparar sin abrir fichas.
     * Columnas: Residente · Edad · Ubicación · Estado (con motivo y vuelta si está fuera) · Profesional de referencia · Próxima revisión.
     * Sin tratamiento ni diagnóstico (regla 9). «Registrar salida» y «Ha vuelto» en la propia fila: Ausencias deja de ser una sección.
     */
    public static function tablaNuevaVersion(Table $table): Table
    {
        $fueraActual = fn (Residente $r) => $r->ausencias()->where('ha_vuelto', false)->latest('sale')->first();

        return $table
            ->heading(fn ($livewire) => match ($livewire->activeTab ?? 'todos') {
                'en_centro' => 'En el centro', 'fuera' => 'Fuera del centro', 'falta' => 'Les falta algo en la ficha', 'bajas' => 'Bajas (historial conservado)', default => 'Todos los residentes',
            })
            ->description(fn ($livewire) => ($livewire->activeTab ?? '') === 'falta' ? 'Plan vencido, sin profesional de referencia, sujeción sin anotar o sin contacto de emergencia. Lo que falta sale en rojo.' : null)
            ->searchPlaceholder('Buscar por nombre')
            ->columns([
                ImageColumn::make('foto')->label('')->circular()->size(36)->grow(false)
                    ->defaultImageUrl(fn (Residente $r) => Avatar::iniciales($r->nombre)),
                TextColumn::make('nombre')->label('Residente')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('fecha_nacimiento')->label('Edad')->sortable()
                    ->formatStateUsing(fn ($state) => $state ? $state->age.' años' : '—')->placeholder('—'),
                TextColumn::make('ubicacion')->label('Ubicación')->sortable(query: fn ($query, string $direction) => $query->orderBy('habitacion', $direction))
                    ->state(fn (Residente $r) => ($r->planta?->nombre ?? '—').' · Hab. '.$r->habitacion),
                TextColumn::make('estado')->label('Estado')->badge()
                    ->formatStateUsing(fn ($state, Residente $r) => $r->etiquetaEstado())
                    ->color(fn ($state) => match ($state) { 'en_centro' => 'success', 'fuera' => 'warning', default => 'gray' })
                    ->description(function (Residente $r) use ($fueraActual) {
                        if ($r->estado !== 'fuera' || ! ($a = $fueraActual($r))) {
                            return null;
                        }
                        $motivo = \App\Models\Ausencia::TIPOS[$a->tipo] ?? $a->tipo;

                        return $motivo.' · '.($a->vuelve ? ($a->vuelve->isToday() ? 'vuelve hoy' : 'vuelve el '.$a->vuelve->locale('es')->isoFormat('D MMM')) : 'sin fecha de vuelta');
                    }),
                TextColumn::make('referente')->label('Profesional de referencia')
                    ->state(fn (Residente $r) => $r->planResponsable?->name ?? 'Sin asignar')
                    ->color(fn (Residente $r) => $r->plan_responsable_id ? null : 'danger'),
                TextColumn::make('plan_proxima_revision')->label('Próxima revisión')->date('j M Y')->sortable()->placeholder('—')
                    ->color(fn (Residente $r) => $r->plan_proxima_revision?->isPast() ? 'danger' : null)
                    ->description(fn (Residente $r) => $r->plan_proxima_revision?->isPast() ? 'Vencida' : null),
            ])
            ->defaultSort('habitacion')
            ->filters([
                SelectFilter::make('planta_id')->label('Planta')->options(fn () => Planta::pluck('nombre', 'id')),
                Filter::make('sin_referencia')->label('Sin profesional de referencia')
                    ->query(fn ($query) => $query->whereNull('plan_responsable_id')),
                Filter::make('sujecion_sin_anotar')->label('Sujeción sin anotar')
                    ->query(fn ($query) => $query->where('sujecion_anotada', false)),
                Filter::make('plan_vencido')->label('Plan personal vencido')
                    ->query(fn ($query) => $query->whereDate('plan_proxima_revision', '<', today())),
                Filter::make('sin_emergencia')->label('Sin contacto de emergencia')
                    ->query(fn ($query) => $query->whereDoesntHave('contactos', fn ($c) => $c->where('emergencia', true))),
            ])
            ->recordActions([
                // Solo se ve al pasar por la fila (o con el teclado): repetida en todas las filas, era ruido.
                ListResidentes::registrarSalidaAction('salidaFila', true)->label('Registrar salida')->link()->color('gray')
                    ->icon('heroicon-o-arrow-right-start-on-rectangle')->extraAttributes(['class' => 'velia-accion-al-pasar'])
                    ->visible(fn (Residente $r) => $r->estado === 'en_centro'),
                Action::make('haVuelto')->label('Ha vuelto')->button()->size('sm')
                    ->visible(fn (Residente $r) => $r->estado === 'fuera')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Residente $r) => $r->nombre.' ha vuelto')
                    ->modalDescription('Vuelve a aparecer en la planta para registrar sus cuidados.')
                    ->modalSubmitActionLabel('Sí, ha vuelto')
                    ->action(function (Residente $r) use ($fueraActual) {
                        if ($a = $fueraActual($r)) {
                            $a->update(['ha_vuelto' => true, 'vuelve' => $a->vuelve ?? now()]);
                        }
                        $r->update(['estado' => 'en_centro']);
                        Notification::make()->title('Marcado: '.$r->nombre.' ha vuelto.')->success()->send();
                    }),
            ])
            ->recordUrl(fn (Residente $r) => static::getUrl('view', ['record' => $r]));
    }

    public static function infolist(Schema $schema): Schema
    {
        // Orden del wireframe: Emergencia · Cuidados · Dieta · Plan personal · Familia · Informes · Notas.
        $ficha = [
            'Cuidados' => [
                TextEntry::make('riesgos')->label('Riesgos')->placeholder('—'),
                TextEntry::make('movilidad')->label('Movilidad')->placeholder('—'),
                TextEntry::make('sujecion')->label('Sujeción')
                    ->state(fn (Residente $r) => ! $r->sujecion_anotada ? 'Sin anotar · pendiente para la auditoría' : ($r->sujecion ? 'Sí, registrar cada uso' : 'No usa'))
                    ->color(fn (Residente $r) => $r->sujecion_anotada ? null : 'danger'),
                TextEntry::make('como_tratarle')->label('Cómo tratarle')->placeholder('—')->columnSpanFull(),
            ],
            'Dieta' => [TextEntry::make('dieta')->hiddenLabel()->placeholder('—')],
            'Plan personal' => [
                TextEntry::make('plan_proxima_revision')->label('Próxima revisión')->date('j M Y')->placeholder('—')
                    ->color(fn (Residente $r) => $r->plan_proxima_revision?->isPast() ? 'danger' : null),
                TextEntry::make('planResponsable.name')->label('Profesional de referencia')
                    ->placeholder('Sin asignar · pendiente para la auditoría')
                    ->color(fn (Residente $r) => $r->plan_responsable_id ? null : 'danger'),
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
                ->schema([Flex::make([
                    ImageEntry::make('foto')->hiddenLabel()->circular()->height(64)->grow(false)
                        ->defaultImageUrl(fn (Residente $r) => Avatar::iniciales($r->nombre)),
                    TextEntry::make('emergencia')->hiddenLabel()->weight('semibold')
                        ->state(function (Residente $r) {
                            $c = $r->contactoEmergencia();
                            $linea = $c ? "Emergencia: {$c->nombre} ({$c->relacion}) · {$c->telefono}" : 'Falta el contacto de emergencia';

                            return $linea.' · Alergia: '.($r->alergias ?: 'ninguna conocida');
                        }),
                ])->verticallyAlignCenter()])->compact(),
            // Navegación contextual (23/11/2026): lo que suele buscarse después de abrir una ficha, con el dato al lado.
            Section::make('Relacionado')->compact()->collapsible()->visible(fn () => ! \App\Support\VersionH::activa())
                ->schema([
                    TextEntry::make('relacionado')->hiddenLabel()->html()
                        ->state(fn (Residente $r) => collect(\App\Support\Destinos::relacionadosResidente($r))
                            ->map(fn ($d) => '<a href="'.e($d['url']).'" style="display:inline-flex;align-items:center;gap:.25rem;min-height:32px;margin:0 1rem .25rem 0;color:'.($d['destacar'] ? 'rgb(185 28 28)' : '#0F3D95').';text-decoration:underline;text-underline-offset:3px">'.e($d['titulo']).' →</a>')
                            ->join('')),
                ]),
        ];
        foreach ($ficha as $titulo => $entries) {
            $sections[] = Section::make($titulo)
                ->schema($entries)
                ->columns(2)
                ->collapsible($titulo === 'Datos y ubicación')
                ->collapsed($titulo === 'Datos y ubicación')
                ->headerActions([self::corregirAction($titulo)]);
            // Nueva versión (v1.5, comentario de Pablo en RES-02): ficha sin pestañas. Lo reciente a primer nivel
            // (los 3 últimos registros, tras «Cómo atender») y el historial completo en un segundo nivel.
            if ($titulo === 'Dieta' && \App\Support\VersionH::activa()) {
                $sections[] = Section::make('Últimos registros')
                    ->headerActions([Action::make('historial')->label('Ver historial completo')->link()->icon('heroicon-m-arrow-right')->iconPosition('after')
                        ->url(fn (Residente $r) => static::getUrl('view', ['record' => $r]).'?historial=1')])
                    ->schema([
                        TextEntry::make('ultimos')->hiddenLabel()->listWithLineBreaks()
                            ->state(fn (Residente $r) => $r->registros()->with('user')->whereIn('estado', ['confirmado', 'sin_novedad'])->limit(3)->get()
                                ->map(fn ($x) => $x->created_at->locale('es')->isoFormat('ddd D, HH:mm').' · '.strtok($x->user?->name ?? '', ' ').' · '.($x->estado === 'sin_novedad' ? 'Sin novedad' : $x->resumenCorto()).($x->incidencia_destacada ? ' · INCIDENCIA' : ''))->all())
                            ->placeholder('Aún no hay registros'),
                    ]);
            }
            if ($titulo === 'Plan personal') {
                $sections[] = Section::make('Familia')
                    ->schema([
                        TextEntry::make('familia')->hiddenLabel()->listWithLineBreaks()
                            ->state(fn (Residente $r) => $r->contactos->map(fn ($c) => "{$c->nombre} · {$c->relacion}".($c->recibe_resumen ? ' · recibe el resumen' : ''))->all())
                            ->placeholder('Sin contactos: no se podrá enviar el resumen semanal'),
                        // 26/11/2026 (tarde): comunicaciones con la familia (fecha, qué y a quién), evidencia para la inspección.
                        TextEntry::make('comunicaciones_familia')->label('Comunicaciones con la familia')
                            ->listWithLineBreaks()->limitList(3)->expandableLimitedList()
                            ->state(fn (Residente $r) => $r->comunicaciones()->get()->map(fn ($c) => $c->linea())->all())
                            ->placeholder('Aún no hay ninguna anotada. Se anotan en Familias.'),
                        // Nueva versión (decisión 4, 26/11): cada resumen enviado queda también en el historial del residente.
                        TextEntry::make('resumenes_familia')->label('Resúmenes enviados a la familia')
                            ->visible(fn () => \App\Support\VersionH::activa())
                            ->listWithLineBreaks()->limitList(3)->expandableLimitedList()
                            ->state(fn (Residente $r) => \App\Models\Resumen::where('residente_id', $r->id)->where('estado', 'enviado')->latest('enviado_at')->get()
                                ->map(fn ($x) => 'Semana del '.$x->semana_inicio->locale('es')->isoFormat('D MMM').' · enviado el '.($x->enviado_at?->locale('es')->isoFormat('D MMM') ?? '—'))->all())
                            ->placeholder('Aún no se ha enviado ninguno'),
                    ])
                    ->headerActions([
                        Action::make('corregir_familia')->label('Corregir')->icon('heroicon-o-pencil-square')->link()
                            ->url(fn (Residente $r) => static::getUrl('view', ['record' => $r]).(\App\Support\VersionH::activa() ? '?historial=1&activeRelationManager=2' : '?activeRelationManager=2')),
                    ]);
            }
        }

        return $schema->components($sections);
    }

    /** «Corregir» de un bloque: mismos campos, motivo obligatorio, guarda versión. */
    public static function corregirAction(string $bloque): Action
    {
        $campos = self::camposCorregibles()[$bloque];
        $nombres = array_map(fn ($c) => $c->getName(), $campos);

        return Action::make('corregir_'.str($bloque)->slug('_'))
            ->label('Corregir')
            ->icon('heroicon-o-pencil-square')
            ->link()
            ->modalHeading("Corregir · {$bloque}")
            ->modalDescription(fn (Residente $record) => "{$record->nombre} · Hab. {$record->habitacion}. Se guarda una versión con fecha y persona. La auxiliar lo ve al momento en «Cómo atender».")
            ->modalSubmitActionLabel('Guardar con motivo')
            ->fillForm(fn (Residente $record) => Arr::only($record->attributesToArray(), $nombres))
            ->schema([
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
            RegistrosRelationManager::class,
            CorreccionesRelationManager::class,
            ContactosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListResidentes::route('/'),
            'create' => CreateResidente::route('/nuevo'),
            'view' => ViewResidente::route('/{record}'),
        ];
    }
}
