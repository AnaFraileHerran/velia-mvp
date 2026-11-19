<?php

namespace App\Filament\Direccion\Resources;

use App\Filament\Direccion\Resources\ResumenResource\Pages;
use App\Models\Residencia;
use App\Models\Resumen;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

/** 5 · Familias: resúmenes semanales. La dirección revisa y aprueba antes de enviar. */
class ResumenResource extends Resource
{
    protected static ?string $model = Resumen::class;

    protected static ?string $navigationLabel = 'Familias';

    protected static ?string $modelLabel = 'resumen';

    protected static ?string $pluralModelLabel = 'Resúmenes semanales';

    protected static ?string $slug = 'familias';

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?int $navigationSort = 5;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('residente.nombre')->label('Residente')->weight('semibold')
                    ->description(fn (Resumen $r) => 'Hab. '.$r->residente->habitacion),
                TextColumn::make('texto')->label('Resumen')->limit(110)->wrap()
                    ->tooltip('Borrador hecho con los registros confirmados de la semana. Revísalo antes de enviar.'),
                // Estado en lenguaje de la dirección: qué tiene que hacer con cada uno.
                TextColumn::make('estado')->label('Estado')->badge()
                    ->state(fn (Resumen $r) => self::etiqueta($r))
                    ->color(fn (Resumen $r) => match (true) {
                        $r->estado === 'enviado' => 'success',
                        ! $r->tieneContacto() => 'danger',
                        $r->tiene_incidencias => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([SelectFilter::make('estado')->options(['por_revisar' => 'Por revisar', 'aprobado' => 'Aprobado', 'enviado' => 'Enviado'])])
            ->actions([
                Action::make('editar')->label('Editar')->color('gray')
                    ->visible(fn (Resumen $r) => $r->estado !== 'enviado')
                    ->fillForm(fn (Resumen $r) => ['texto' => $r->texto])
                    ->form([Textarea::make('texto')->label('Texto para la familia')->rows(6)->required()])
                    ->action(fn (Resumen $r, array $data) => $r->update($data)),
                Action::make('enviar')->label(fn (Resumen $r) => $r->tieneContacto() ? 'Revisar y enviar' : 'Añadir contacto')
                    ->visible(fn (Resumen $r) => $r->estado !== 'enviado')
                    // Sin contacto no se abre un envío que fallaría: lleva a la ficha para añadirlo.
                    ->url(fn (Resumen $r) => $r->tieneContacto() ? null : ResidenteResource::getUrl('view', ['record' => $r->residente_id]).'?activeRelationManager=2')
                    ->modalHeading('Enviar a la familia')
                    ->modalDescription(fn (Resumen $r) => 'Resumen de '.$r->residente->nombre.' · '.$r->semana_inicio->locale('es')->isoFormat('D MMM').' – '.$r->semana_inicio->copy()->addDays(6)->locale('es')->isoFormat('D MMM'))
                    ->modalContent(fn (Resumen $r) => view('filament.direccion.resumen-envio', [
                        'resumen' => $r,
                        'contactos' => $r->residente->contactos()->where('recibe_resumen', true)->get(),
                        'residencia' => Residencia::actual(),
                    ]))
                    ->modalSubmitActionLabel('Enviar')
                    ->action(fn (Resumen $r) => self::enviar($r)),
            ])
            ->bulkActions([
                BulkAction::make('aprobarSinIncidencias')->label('Enviar los que no tienen incidencias')
                    ->modalDescription('Los que tienen incidencias o les falta contacto se quedan para que los revises uno a uno.')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $enviados = 0;
                        foreach ($records as $r) {
                            if (! $r->tiene_incidencias && $r->estado !== 'enviado' && $r->tieneContacto()) {
                                self::enviar($r, false);
                                $enviados++;
                            }
                        }
                        Notification::make()->title("{$enviados} resumen(es) enviados. Los que tienen incidencias o falta contacto siguen pendientes.")->success()->send();
                    }),
            ]);
    }

    public static function etiqueta(Resumen $r): string
    {
        if ($r->estado === 'enviado') {
            return 'Enviado';
        }
        if (! $r->tieneContacto()) {
            return 'Falta contacto';
        }
        if ($r->tiene_incidencias) {
            $n = $r->numeroIncidencias();

            return 'Borrador · '.$n.' '.($n === 1 ? 'incidencia' : 'incidencias');
        }

        return 'Sin incidencias';
    }

    public static function destinatarios(Resumen $r): string
    {
        $c = $r->residente->contactos()->where('recibe_resumen', true)->get();
        if ($c->isEmpty()) {
            return 'Falta el contacto de la familia. Añádelo en la ficha del residente.';
        }

        return 'Se enviará a: '.$c->map(fn ($x) => $x->nombre.' ('.($x->canal === 'whatsapp' ? 'WhatsApp corporativo' : 'email').')')->join(', ')
            .'. Al pie, cómo responder: por teléfono o email.';
    }

    public static function enviar(Resumen $r, bool $avisar = true): void
    {
        if (! $r->tieneContacto()) {
            Notification::make()->title('Falta el contacto de la familia')->danger()->send();

            return;
        }
        // Simulación: en el MVP no se envía nada; se marca como enviado.
        $r->update(['estado' => 'enviado', 'aprobado_por' => auth()->id(), 'enviado_at' => now()]);
        if ($avisar) {
            Notification::make()->title('Enviado a la familia.')->success()->send();
        }
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageResumenes::route('/')];
    }
}
