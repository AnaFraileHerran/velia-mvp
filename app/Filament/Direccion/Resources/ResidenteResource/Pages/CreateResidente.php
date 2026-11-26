<?php

namespace App\Filament\Direccion\Resources\ResidenteResource\Pages;

use App\Filament\Direccion\Concerns\MigasDePan;
use App\Filament\Direccion\Resources\ResidenteResource;
use App\Models\Aviso;
use App\Models\SugerenciaIa;
use App\Support\Ia\Sugerencias;
use App\Support\VersionH;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

/** Único sitio con edición completa. Crea el aviso de la valoración inicial del plan personal (primer mes). */
class CreateResidente extends CreateRecord
{
    use MigasDePan;

    protected function migas(): array
    {
        return [ResidenteResource::getUrl() => 'Residentes'];
    }

    protected static string $resource = ResidenteResource::class;

    protected static ?string $title = 'Nuevo residente';

    /**
     * Nueva versión (26/11/2026, comentario de Pablo en RES-07): el alta luce el asistente y va por pasos, con el mismo patrón
     * que Registrar en la app: 1 Cuéntaselo a Velia · 2 Revisa lo que ha rellenado y completa lo que falta · 3 Plan y familia, y crear.
     * Un paso cada vez baja la carga: antes era una página larga con todo mezclado.
     */
    public function form(Schema $schema): Schema
    {
        if (! VersionH::activa()) {
            return parent::form($schema);
        }
        $c = ResidenteResource::camposCorregibles();

        return parent::form($schema)->columns(null)->components([
            Wizard::make([
                Step::make('Cuéntaselo a Velia')->icon('heroicon-o-light-bulb')
                    ->description('Dicta o escribe lo que sabes')
                    ->schema([
                        TextInput::make('nombre')->label('Nombre y apellidos')->required(),
                        View::make('filament.direccion.partials.dictar-ficha'),
                    ]),
                Step::make('Revisa y completa')->icon('heroicon-o-check-circle')
                    ->description('Lo que ha rellenado Velia, marcado')
                    ->schema([
                        View::make('filament.direccion-h.rellenado-por-velia'),
                        Section::make('Ficha de cuidados')->description('Sin ficha, una auxiliar nueva no sabrá cómo atender a este residente.')
                            ->schema([...$c['Cuidados'], ...$c['Dieta']])->columns(2),
                        Section::make('Datos y ubicación')->schema($c['Datos y ubicación'])->columns(2)->collapsible(),
                    ]),
                Step::make('Plan y familia')->icon('heroicon-o-users')
                    ->description('Revisión inicial y contactos')
                    ->schema([
                        Section::make('Plan personal')->description('Valoración inicial: antes de un mes desde el ingreso (Orden 2680/2024). Se creará un aviso en Inicio.')
                            ->schema($c['Plan personal'])->columns(3),
                        Section::make('Familia')->description('Sin contactos, no se podrá enviar el resumen semanal a la familia.')->schema([
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
                    ]),
            ])
                ->cancelAction($this->getCancelFormAction())
                ->submitAction($this->getSubmitFormAction()->label('Crear residente'))
                ->alpineSubmitHandler('$wire.create()')
                ->contained(false),
        ]);
    }

    public function hasFormWrapper(): bool
    {
        return ! VersionH::activa();
    }

    public function getFormContentComponent(): Component
    {
        return VersionH::activa() ? EmbeddedSchema::make('form') : parent::getFormContentComponent();
    }

    /** Lo que propuso Velia a partir del dictado, para anotar al guardar si se aceptó o se cambió. */
    public array $propuestaVelia = [];

    public function rellenarConVelia(string $texto): void
    {
        $campos = Sugerencias::fichaDesdeDictado($texto);
        if (! $campos) {
            Notification::make()->title('No he encontrado nada que poner en la ficha. Prueba con más detalle.')->warning()->send();

            return;
        }
        $this->data = array_replace($this->data ?? [], $campos);
        $this->propuestaVelia = $campos;
        $nombres = ['alergias' => 'alergias', 'riesgos' => 'riesgos', 'movilidad' => 'movilidad', 'sujecion' => 'sujeción', 'dieta' => 'dieta', 'como_tratarle' => 'cómo tratarle', 'notas' => 'notas', 'habitacion' => 'habitación'];
        $rellenos = collect(array_keys($campos))->map(fn ($k) => $nombres[$k] ?? null)->filter()->unique()->join(', ');
        Notification::make()->title('Velia ha rellenado: '.$rellenos)->body('Revisa cada campo antes de guardar.')->success()->send();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['fecha_ingreso'] ??= now()->toDateString();
        $data['plan_proxima_revision'] ??= now()->parse($data['fecha_ingreso'])->addMonth()->toDateString();

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->propuestaVelia) {
            $final = array_intersect_key($this->record->only(array_keys($this->propuestaVelia)), $this->propuestaVelia);
            $igual = collect($this->propuestaVelia)->every(fn ($v, $k) => (string) $v === (string) ($final[$k] ?? ''));
            SugerenciaIa::anotar('residente.alta', json_encode($this->propuestaVelia, JSON_UNESCAPED_UNICODE), $igual ? 'aceptada' : 'cambiada', $this->record);
        }

        Aviso::create([
            'tipo' => 'revision_vencida',
            'titulo' => 'Valoración inicial del plan personal (antes de un mes desde el ingreso)',
            'residente_id' => $this->record->id,
            'urgencia' => 2,
        ]);
    }
}
