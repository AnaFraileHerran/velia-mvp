<?php

namespace App\Filament\Direccion\Pages;

use App\Filament\Direccion\Concerns\MigasDePan;
use App\Filament\Direccion\Resources\ResidenteResource;
use App\Filament\Direccion\Resources\ResumenResource;
use App\Models\Contacto;
use App\Models\Residente;
use App\Models\Resumen;
use App\Support\Avatar;
use App\Support\VersionH;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

/**
 * Familias · Nueva versión (26/11/2026, decisión 4 de Ana sobre el comentario de Pablo en FAM-01).
 * Familias trata de las familias: quién es el contacto principal, quién más está autorizado, cómo prefieren que les avisen
 * y cuándo se les escribió por última vez. Los resúmenes de la semana son una tarea dentro («Revisar y enviar»), y cada
 * resumen enviado queda también en la ficha del residente. La ocupación sale de aquí (va a Residentes y al Inicio).
 * 26/11/2026 (tarde, decisión de Ana): la sección se sigue llamando «Familias», pero guarda además cada comunicación con la familia
 * (fecha, qué y a quién), que es lo que pide la guía de autoevaluación 2026 de Madrid. «Anotar comunicación» arriba; el resumen
 * semanal enviado se anota solo; tras una caída sale el aviso «Informar a la familia» en Inicio.
 * [SUPOSICIÓN] Faltan datos que Pablo menciona (visitas, indicaciones de cómo proceder): validar con directoras antes de añadir campos.
 */
class Familias extends Page implements HasTable
{
    use InteractsWithTable;
    use MigasDePan;

    protected static ?string $navigationLabel = 'Familias';

    protected static ?string $title = 'Familias';

    protected static ?string $slug = 'familias-directorio';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-heart';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.direccion-h.familias';

    public static function shouldRegisterNavigation(): bool
    {
        return VersionH::activa();
    }

    public function getSubheading(): ?string
    {
        return 'Quién es la familia de cada residente, cómo prefiere que le avisen y qué se le ha comunicado.';
    }

    /** Tarea de la semana, arriba: cuántos resúmenes esperan revisión y con incidencias. */
    public function tarea(): array
    {
        $porRevisar = Resumen::where('estado', '!=', 'enviado');

        return [
            'pendientes' => (clone $porRevisar)->count(),
            'incidencias' => (clone $porRevisar)->where('tiene_incidencias', true)->count(),
            'enviados' => Resumen::where('estado', 'enviado')->where('enviado_at', '>=', now()->startOfWeek())->count(),
            'url' => ResumenResource::getUrl(),
        ];
    }

    /** «Anotar comunicación»: una llamada, una visita o un mensaje a la familia, con fecha, qué y a quién. */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('anotarComunicacion')
                ->label('Anotar comunicación')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->modalHeading('Anotar una comunicación con la familia')
                ->modalDescription('Queda con fecha y tu nombre. No se borra: si hay un error, anota otra que lo aclare.')
                ->modalSubmitActionLabel('Guardar')
                ->fillForm(['canal' => 'telefono', 'fecha' => now(), 'motivo' => 'seguimiento'])
                ->schema([
                    \Filament\Forms\Components\Select::make('residente_id')->label('Residente')->required()->searchable()->live()
                        ->options(fn () => Residente::query()->activos()->orderBy('nombre')->pluck('nombre', 'id')),
                    \Filament\Forms\Components\Select::make('contacto_id')->label('¿A quién?')->required()
                        ->options(fn (\Filament\Schemas\Components\Utilities\Get $get) => \App\Models\Comunicacion::opcionesContacto(Residente::with('contactos')->find($get('residente_id'))))
                        ->helperText('Si no aparece, añádelo en la ficha del residente.'),
                    \Filament\Forms\Components\Select::make('motivo')->label('Motivo')->required()
                        ->options(collect(\App\Models\Comunicacion::MOTIVOS)->except('resumen_semanal')->all()),
                    \Filament\Forms\Components\Radio::make('canal')->label('¿Cómo?')->required()->inline()->options(\App\Models\Comunicacion::CANALES),
                    \Filament\Forms\Components\DateTimePicker::make('fecha')->label('¿Cuándo?')->required()->native(false)->seconds(false)->maxDate(now()),
                    \Filament\Forms\Components\Textarea::make('que')->label('¿Qué se ha comunicado?')->required()->rows(3),
                ])
                ->action(function (array $data) {
                    \App\Models\Comunicacion::anotar(Residente::findOrFail($data['residente_id']), $data);
                    \Filament\Notifications\Notification::make()->title('Comunicación anotada.')->success()->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        $principal = fn (Residente $r) => $r->contactos->sortByDesc(fn (Contacto $c) => [$c->emergencia, $c->recibe_resumen])->first();

        return $table
            ->query(Residente::query()->activos()->with(['contactos', 'planta']))
            ->heading('Familias por residente')
            ->searchPlaceholder('Buscar por residente o familiar')
            ->defaultSort('habitacion')
            ->columns([
                ImageColumn::make('foto')->label('')->circular()->size(36)->grow(false)
                    ->defaultImageUrl(fn (Residente $r) => Avatar::iniciales($r->nombre)),
                TextColumn::make('nombre')->label('Residente')->weight('semibold')->sortable()
                    ->searchable(query: fn ($query, string $search) => $query->where(fn ($q) => $q->where('nombre', 'like', "%{$search}%")
                        ->orWhereHas('contactos', fn ($c) => $c->where('nombre', 'like', "%{$search}%"))))
                    ->description(fn (Residente $r) => ($r->planta?->nombre ?? '—').' · Hab. '.$r->habitacion),
                TextColumn::make('principal')->label('Contacto principal')
                    ->state(fn (Residente $r) => ($c = $principal($r)) ? $c->nombre.' · '.$c->relacion : null)
                    ->description(fn (Residente $r) => $principal($r)?->telefono)
                    ->placeholder('Sin contacto')->color(fn ($state) => $state ? null : 'danger'),
                TextColumn::make('autorizados')->label('Autorizados')->alignCenter()
                    ->state(fn (Residente $r) => $r->contactos->count() ?: null)->placeholder('0'),
                TextColumn::make('aviso')->label('Cómo prefieren que les avisen')
                    ->state(fn (Residente $r) => $r->contactos->where('recibe_resumen', true)->pluck('canal')->unique()
                        ->map(fn ($c) => $c === 'whatsapp' ? 'WhatsApp corporativo' : 'Email')->join(' y ') ?: null)
                    ->placeholder('No reciben el resumen'),
                TextColumn::make('ultima')->label('Última comunicación')
                    ->state(function (Residente $r) {
                        if ($c = $r->comunicaciones()->first()) {
                            return (\App\Models\Comunicacion::MOTIVOS[$c->motivo] ?? $c->motivo).' · '.$c->fecha->locale('es')->isoFormat('D MMM');
                        }
                        $u = Resumen::where('residente_id', $r->id)->where('estado', 'enviado')->latest('enviado_at')->first();

                        return $u?->enviado_at ? 'Resumen semanal · '.$u->enviado_at->locale('es')->isoFormat('D MMM') : null;
                    })
                    ->description(fn (Residente $r) => ($c = $r->comunicaciones()->first()) ? 'a '.$c->a_quien : null)
                    ->placeholder('Ninguna aún'),
                TextColumn::make('esta_semana')->label('Resumen de esta semana')->badge()
                    ->state(function (Residente $r) {
                        $x = Resumen::where('residente_id', $r->id)->latest('semana_inicio')->first();

                        return $x ? ResumenResource::etiqueta($x) : null;
                    })
                    ->color(fn ($state) => match (true) { $state === 'Enviado' => 'success', $state === 'Falta contacto' => 'danger', str_starts_with((string) $state, 'Borrador') => 'warning', default => 'gray' })
                    ->placeholder('—'),
            ])
            ->filters([
                Filter::make('sin_contacto')->label('Sin contacto de emergencia')
                    ->query(fn ($query) => $query->whereDoesntHave('contactos', fn ($c) => $c->where('emergencia', true))),
                Filter::make('no_reciben')->label('No reciben el resumen')
                    ->query(fn ($query) => $query->whereDoesntHave('contactos', fn ($c) => $c->where('recibe_resumen', true))),
            ])
            ->recordUrl(fn (Residente $r) => ResidenteResource::getUrl('view', ['record' => $r]));
    }
}
