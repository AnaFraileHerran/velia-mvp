<?php

namespace App\Filament\Direccion\Resources\ResidenteResource\Pages;

use App\Filament\Direccion\Concerns\MigasDePan;
use App\Models\Ausencia;
use App\Models\Residente;
use App\Models\Residencia;
use App\Filament\Direccion\Resources\AusenciaResource;
use App\Filament\Direccion\Resources\ResidenteResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use App\Support\VersionH;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;

class ListResidentes extends ListRecords
{
    use MigasDePan;

    protected static string $resource = ResidenteResource::class;

    /** Capacidad de un vistazo y lo que pide atención, en una línea bajo el título. */
    public function getSubheading(): ?string
    {
        // Nueva versión (26/11): las tarjetas de cifras ya dicen ocupación, quién está fuera y a quién le falta algo.
        if (VersionH::activa()) {
            return null;
        }

        $activos = Residente::activos();
        $n = (clone $activos)->count();
        $plazas = Residencia::actual()->plazas_autorizadas;
        $fuera = (clone $activos)->where('estado', 'fuera')->count();
        $planes = (clone $activos)->whereDate('plan_proxima_revision', '<', today())->count();
        $sinEmergencia = (clone $activos)->whereDoesntHave('contactos', fn ($query) => $query->where('emergencia', true))->count();

        $partes = [$plazas ? "{$n} de {$plazas} plazas ocupadas · ".max(0, $plazas - $n).' libres' : "{$n} residentes"];
        $partes[] = ($n - $fuera).' en el centro'.($fuera ? " · {$fuera} fuera" : '');
        if ($planes) {
            $partes[] = $planes === 1 ? '1 plan personal vencido' : "{$planes} planes personales vencidos";
        }
        if ($sinEmergencia) {
            $partes[] = $sinEmergencia === 1 ? '1 sin contacto de emergencia' : "{$sinEmergencia} sin contacto de emergencia";
        }

        return implode(' · ', $partes);
    }

    protected function getHeaderActions(): array
    {
        if (VersionH::activa()) {
            // Ausencias deja de ser una sección (comentario de Pablo y Antonio): la salida se registra aquí o en la fila.
            return [
                self::registrarSalidaAction('registrarSalida')->label('Registrar salida')->color('gray')->icon('heroicon-o-arrow-right-start-on-rectangle'),
                CreateAction::make()->label('Añadir residente'),
            ];
        }

        return [
            Action::make('ausencias')->label('Ausencias')->color('gray')->icon('heroicon-o-arrow-right-start-on-rectangle')->url(AusenciaResource::getUrl()),
            CreateAction::make()->label('Añadir residente'),
        ];
    }

    /** «Registrar salida»: misma acción en la cabecera (eligiendo residente) y en cada fila (ya elegido). */
    public static function registrarSalidaAction(string $nombre, bool $enFila = false): Action
    {
        return Action::make($nombre)
            ->modalHeading(fn (?Residente $record) => $record ? 'Salida de '.$record->nombre : 'Registrar una salida')
            ->modalDescription('Las auxiliares lo ven en su planta. Cuando vuelva, márcalo en su fila.')
            ->modalSubmitActionLabel('Registrar salida')
            ->schema(array_values(array_filter([
                $enFila ? null : Select::make('residente_id')->label('Residente')->required()->searchable()
                    ->options(fn () => Residente::where('estado', 'en_centro')->orderBy('nombre')->pluck('nombre', 'id')),
                Select::make('tipo')->label('Motivo')->options(Ausencia::TIPOS)->required(),
                DatePicker::make('sale')->label('Sale')->default(now())->required(),
                DatePicker::make('vuelve')->label('Vuelve (si se sabe)'),
            ])))
            ->action(function (array $data, ?Residente $record) {
                $residente = $record ?? Residente::find($data['residente_id']);
                Ausencia::create([
                    'residente_id' => $residente->id, 'tipo' => $data['tipo'], 'sale' => $data['sale'], 'vuelve' => $data['vuelve'] ?? null,
                    'registrada_por' => Filament::auth()->id(),
                ]);
                if (now()->parse($data['sale'])->lte(today())) {
                    $residente->update(['estado' => 'fuera']);
                }
                Notification::make()->title('Salida registrada. Las auxiliares lo ven en su planta.')->success()->send();
            });
    }

    /** Pestañas de estado. En la Nueva versión no se ven como pestañas: las sustituyen las tarjetas de cifras (que filtran igual). */
    public function getTabs(): array
    {
        if (! VersionH::activa()) {
            return [];
        }

        return [
            'todos' => Tab::make('Todos')->modifyQueryUsing(fn ($query) => $query->where('estado', '!=', 'baja')),
            'en_centro' => Tab::make('En el centro')->modifyQueryUsing(fn ($query) => $query->where('estado', 'en_centro')),
            'fuera' => Tab::make('Fuera')->modifyQueryUsing(fn ($query) => $query->where('estado', 'fuera')),
            'falta' => Tab::make('Les falta algo')->modifyQueryUsing(fn ($query) => self::faltaAlgo($query)),
            'bajas' => Tab::make('Bajas')->modifyQueryUsing(fn ($query) => $query->where('estado', 'baja')),
        ];
    }

    public static function faltaAlgo($query)
    {
        return $query->where('estado', '!=', 'baja')->where(fn ($w) => $w
            ->whereDate('plan_proxima_revision', '<', today())
            ->orWhereNull('plan_responsable_id')
            ->orWhere('sujecion_anotada', false)
            ->orWhereDoesntHave('contactos', fn ($c) => $c->where('emergencia', true)));
    }

    /** Nueva versión: 4 tarjetas de cifras que filtran la tabla (comentario de Pablo, patrón de Filament). */
    public function getTabsContentComponent(): Component
    {
        if (! VersionH::activa()) {
            return parent::getTabsContentComponent();
        }

        $activos = Residente::activos()->count();
        $plazas = Residencia::actual()->plazas_autorizadas;
        $fuera = Residente::where('estado', 'fuera')->count();
        $vuelvenHoy = Ausencia::where('ha_vuelto', false)->whereDate('vuelve', today())->count();
        $falta = self::faltaAlgo(Residente::query())->count();

        return View::make('filament.direccion-h.tarjetas-filtro')->viewData(['tarjetas' => [
            'todos' => ['etiqueta' => 'Ocupación', 'cifra' => $activos, 'de' => $plazas ? '/'.$plazas : null, 'nota' => $plazas ? max(0, $plazas - $activos).' plazas libres' : 'residentes', 'icono' => 'heroicon-o-home', 'tono' => 'neutro'],
            'en_centro' => ['etiqueta' => 'En el centro', 'cifra' => $activos - $fuera, 'nota' => 'ahora mismo', 'icono' => 'heroicon-o-check-circle', 'tono' => 'verde'],
            'fuera' => ['etiqueta' => 'Fuera', 'cifra' => $fuera, 'nota' => $vuelvenHoy ? ($vuelvenHoy === 1 ? '1 vuelve hoy' : "{$vuelvenHoy} vuelven hoy") : 'hospital, familia…', 'icono' => 'heroicon-o-arrow-right-start-on-rectangle', 'tono' => $fuera ? 'ambar' : 'neutro'],
            'falta' => ['etiqueta' => 'Les falta algo', 'cifra' => $falta, 'nota' => 'plan, referente, sujeción o contacto', 'icono' => 'heroicon-o-exclamation-triangle', 'tono' => $falta ? 'rojo' : 'neutro'],
        ], 'secundario' => ['bajas', 'Ver bajas ('.Residente::where('estado', 'baja')->count().')']]);
    }
}
