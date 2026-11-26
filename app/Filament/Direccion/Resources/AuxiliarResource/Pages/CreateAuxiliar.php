<?php

namespace App\Filament\Direccion\Resources\AuxiliarResource\Pages;

use App\Filament\Direccion\Concerns\MigasDePan;
use Filament\Facades\Filament;
use App\Filament\Direccion\Resources\AuxiliarResource;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use App\Models\SugerenciaIa;
use App\Support\VersionH;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

/** Al añadir una auxiliar, el panel genera su PIN provisional y un código para vincular su móvil. */
class CreateAuxiliar extends CreateRecord
{
    use MigasDePan;

    protected function migas(): array
    {
        return [AuxiliarResource::getUrl() => 'Equipo'];
    }

    protected static string $resource = AuxiliarResource::class;

    /** Nueva versión (comentario de Pablo en EQ-06): sin «Crear y crear otro». Se crea y se vuelve a la lista con la fila resaltada. */
    public function canCreateAnother(): bool
    {
        return ! VersionH::activa();
    }

    /** Lo que propuso Velia al leer los documentos, para anotar al crear si se aceptó o se cambió (regla 10). */
    public array $propuestaDocumentos = [];

    /**
     * «Subir documentos» (v1.5, respuesta de Ana a Pablo en EQ-06): se sube su documentación y Velia rellena la ficha.
     * Condiciones: Velia propone y la dirección confirma campo a campo; el documento no se guarda (solo se lee para rellenar);
     * el alta a mano sigue existiendo. Simulado durante el piloto (lee el nombre del archivo), marcado como «Ejemplo».
     */
    protected function getHeaderActions(): array
    {
        if (! VersionH::activa()) {
            return [];
        }

        return [
            Action::make('subirDocumentos')->label('Subir documentos')->icon('heroicon-o-document-arrow-up')->color('gray')
                ->modalHeading('Rellenar la ficha con sus documentos')
                ->modalDescription('Sube su DNI o su contrato. Velia lee el nombre y los datos de trabajo y los pone en la ficha para que los revises. '
                    .'El documento no se guarda en Velia: solo se lee para rellenar.'.(\App\Support\Ia\Asistente::esSimulado() ? ' (Ejemplo: durante el piloto, Velia lee el nombre del archivo.)' : ''))
                ->modalSubmitActionLabel('Rellenar la ficha')
                ->schema([
                    FileUpload::make('documentos')->label('Documentos (PDF o foto)')->multiple()->maxFiles(3)
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])->storeFiles(false)->required(),
                ])
                ->action(function (array $data) {
                    $propuesta = self::leerDocumentos($data['documentos'] ?? []);
                    if (! $propuesta) {
                        Notification::make()->title('No he podido leer nada útil. Rellena la ficha a mano.')->warning()->send();

                        return;
                    }
                    $this->propuestaDocumentos = $propuesta;
                    $this->form->fill(array_replace($this->form->getRawState(), $propuesta));
                    Notification::make()->title('Velia ha rellenado: '.implode(', ', array_map(fn ($k) => ['name' => 'nombre', 'telefono' => 'teléfono'][$k] ?? $k, array_keys($propuesta))))
                        ->body('Revísalo antes de crear. El documento no se ha guardado.')->success()->send();
                }),
        ];
    }

    /** Simulación: «DNI_Laura_Sanchez_Ruiz.pdf» → nombre «Laura Sanchez Ruiz». Con proveedor real, aquí iría la lectura del documento. */
    public static function leerDocumentos(array $archivos): array
    {
        foreach ($archivos as $a) {
            $nombre = is_object($a) && method_exists($a, 'getClientOriginalName') ? $a->getClientOriginalName() : (string) $a;
            $base = preg_replace('/\.[a-z0-9]+$/i', '', basename($nombre));
            $base = trim(preg_replace('/\b(dni|nie|contrato|documento|doc|scan|copia)\b/i', '', str_replace(['_', '-'], ' ', $base)));
            $base = trim(preg_replace('/\d+/', '', $base));
            if (str_word_count($base) >= 2) {
                return ['name' => mb_convert_case(preg_replace('/\s+/', ' ', $base), MB_CASE_TITLE)];
            }
        }

        return [];
    }

    public function getTitle(): string
    {
        $rol = $this->data['rol'] ?? request()->query('rol');
        if ($rol === 'otro') {
            $area = \App\Models\AreaEquipo::find($this->data['area_equipo_id'] ?? request()->query('area'));

            return 'Añadir a '.($area?->nombre ?? 'otro rol');
        }

        return $rol === 'sanitario' ? 'Añadir enfermería o medicina' : 'Añadir auxiliar';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (in_array($data['rol'] ?? null, ['sanitario', 'otro'], true)) {
            return $data; // no entra en la app: sin PIN ni código
        }
        $data['area_equipo_id'] = null;
        $data['rol'] = 'auxiliar';
        $data['codigo_invitacion'] = Str::upper(Str::random(3)).random_int(100, 999);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->propuestaDocumentos) {
            $igual = collect($this->propuestaDocumentos)->every(fn ($v, $k) => (string) $v === (string) $this->record->{$k});
            SugerenciaIa::anotar('equipo.documentos', json_encode($this->propuestaDocumentos, JSON_UNESCAPED_UNICODE), $igual ? 'aceptada' : 'cambiada', $this->record);
        }
        if ($this->record->rol === 'otro') {
            Notification::make()->title("{$this->record->name} ya está en {$this->record->etiquetaPuesto()}.")->success()->send();

            return;
        }
        if ($this->record->rol === 'sanitario') {
            Notification::make()->title("{$this->record->name} ya aparece en «Mi turno» de las auxiliares.")->success()->send();

            return;
        }
        $pin = $this->record->generarPinProvisional(Filament::auth()->user());
        Notification::make()
            ->title("PIN provisional de {$this->record->name}: {$pin}")
            ->body("Para su móvil, código de invitación: {$this->record->codigo_invitacion}. Comunícaselo en persona; creará su PIN al entrar.")
            ->persistent()->success()->send();
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }

    protected function getRedirectUrl(): string
    {
        // Nueva versión: vuelve a la lista con la fila nueva resaltada (?nueva=)
        $extra = VersionH::activa() ? ['nueva' => $this->record->id] : [];

        return static::getResource()::getUrl('index', match ($this->record->rol) {
            'sanitario' => ['tab' => 'sanitarios'],
            'otro' => ['tab' => 'area-'.$this->record->area_equipo_id],
            default => VersionH::activa() ? ['tab' => 'auxiliares'] : [],
        } + $extra);
    }
}
