<?php

namespace App\Filament\Direccion\Resources;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Facades\Filament;
use App\Filament\Direccion\Resources\ResumenResource\Pages\ManageResumenes;
use App\Filament\Direccion\Resources\ResumenResource\Pages;
use App\Models\Residencia;
use App\Models\Resumen;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use App\Models\SugerenciaIa;
use App\Support\Ia\Asistente;
use App\Support\Ia\Sugerencias;

/** 5 · Familias: resúmenes semanales. La dirección revisa y aprueba antes de enviar. */
class ResumenResource extends Resource
{
    protected static ?string $model = Resumen::class;

    protected static ?string $navigationLabel = 'Familias';

    protected static ?string $modelLabel = 'resumen';

    protected static ?string $pluralModelLabel = 'Resúmenes semanales';

    protected static ?string $slug = 'familias';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-envelope';

    protected static ?int $navigationSort = 5;

    /** Nueva versión (decisión 4, 26/11): «Familias» es el directorio; los resúmenes son una tarea dentro de Familias. */
    public static function shouldRegisterNavigation(): bool
    {
        return ! \App\Support\VersionH::activa();
    }

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
            ->recordActions([
                // Familias (23/11/2026): la IA redacta un borrador con los registros confirmados; la dirección lo edita y lo aprueba.
                Action::make('redactar')->label('Redactar con Velia')->icon('heroicon-o-light-bulb')->color('gray')
                    ->visible(fn (Resumen $r) => $r->estado !== 'enviado')
                    ->modalHeading(fn (Resumen $r) => 'Borrador para la familia de '.$r->residente->nombre)
                    ->modalDescription(fn () => 'Velia lo ha redactado con los registros confirmados de la semana, en lenguaje sencillo. Revísalo y cámbialo lo que quieras: nada se envía sin tu aprobación.'
                        .(Asistente::esSimulado() ? ' (Ejemplo: borrador hecho con reglas durante el piloto.)' : ''))
                    ->fillForm(fn (Resumen $r) => ['texto' => Sugerencias::resumenFamilia($r)])
                    ->schema([Textarea::make('texto')->label('Texto para la familia')->rows(8)->required()])
                    ->modalSubmitActionLabel('Guardar como borrador')
                    ->action(function (Resumen $r, array $data) {
                        $propuesta = Sugerencias::resumenFamilia($r);
                        $r->update(['texto' => $data['texto']]);
                        SugerenciaIa::anotar('familias.resumen', $propuesta, trim($data['texto']) === trim($propuesta) ? 'aceptada' : 'cambiada', $r);
                        Notification::make()->title('Borrador guardado. Envíalo cuando lo hayas revisado.')->success()->send();
                    }),
                Action::make('editar')->label('Editar')->color('gray')
                    ->visible(fn (Resumen $r) => $r->estado !== 'enviado')
                    ->fillForm(fn (Resumen $r) => ['texto' => $r->texto])
                    ->schema([Textarea::make('texto')->label('Texto para la familia')->rows(6)->required()])
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
            ->toolbarActions([
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
        $r->update(['estado' => 'enviado', 'aprobado_por' => Filament::auth()->id(), 'enviado_at' => now()]);
        // 26/11/2026: el resumen enviado queda también como comunicación con la familia (una por destinatario).
        foreach ($r->residente->contactos->where('recibe_resumen', true) as $c) {
            \App\Models\Comunicacion::create([
                'residente_id' => $r->residente_id, 'contacto_id' => $c->id, 'a_quien' => "{$c->nombre} ({$c->relacion})",
                'motivo' => 'resumen_semanal', 'canal' => $c->canal === 'whatsapp' ? 'whatsapp' : 'email',
                'que' => 'Resumen de la semana del '.$r->semana_inicio->locale('es')->isoFormat('D MMM').'.',
                'fecha' => now(), 'registrado_por' => Filament::auth()->id(), 'resumen_id' => $r->id,
            ]);
        }
        if ($avisar) {
            Notification::make()->title('Enviado a la familia.')->success()->send();
        }
    }

    public static function getPages(): array
    {
        return ['index' => ManageResumenes::route('/')];
    }
}
