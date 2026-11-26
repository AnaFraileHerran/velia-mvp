<?php

namespace App\Filament\Direccion\Pages;

use App\Filament\Direccion\Concerns\MigasDePan;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use App\Models\Planta;
use App\Models\Residencia;
use App\Models\SolicitudResidencia;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * 6 · Mi residencia (wireframe «V2 · Mi residencia»).
 * Arriba lo que la dirección cambia (identidad, auditoría, fotos); debajo, solo lectura (plan, plantas, normativa).
 */
class MiResidencia extends Page implements HasActions, HasForms
{
    use MigasDePan;

    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Mi residencia';

    protected static ?string $title = 'Mi residencia';

    protected static ?string $slug = 'mi-residencia';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.direccion.pages.mi-residencia';

    protected ?string $subheading = 'Los datos de tu centro: lo que ven las auxiliares en la tablet o el móvil y lo que firman los resúmenes para las familias.';

    public ?array $data = [];

    /** Nueva versión (comentario de Pablo en MIR-01): primero se ve, y «Editar» abre el formulario. */
    public bool $editando = false;

    protected function getHeaderActions(): array
    {
        if (! \App\Support\VersionH::activa()) {
            return [];
        }

        return [
            Action::make('editar')->label('Editar')->icon('heroicon-o-pencil-square')
                ->visible(fn () => ! $this->editando)
                ->action(fn () => $this->editando = true),
        ];
    }

    public function cancelar(): void
    {
        $this->form->fill(Residencia::actual()->attributesToArray());
        $this->editando = false;
    }

    /** Opción B (decisión 23/11/2026): no hay alta propia de otra residencia en el piloto; se solicita y ICARUS la prepara. */
    public function solicitarResidenciaAction(): Action
    {
        return Action::make('solicitarResidencia')
            ->label('Solicitar alta')
            ->icon('heroicon-o-plus')
            ->color('gray')
            ->modalHeading('Añadir otra residencia')
            ->modalDescription('Te llamamos para prepararla contigo, como hicimos con esta. Cada residencia tiene sus propios residentes, equipo y auditoría.')
            ->modalSubmitActionLabel('Enviar solicitud')
            ->schema([
                TextInput::make('nombre')->label('Nombre de la residencia')->required(),
                TextInput::make('plazas')->label('Plazas')->numeric()->minValue(1),
                TextInput::make('municipio')->label('Municipio'),
            ])
            ->action(function (array $data) {
                SolicitudResidencia::create($data + [
                    'residencia_id' => Residencia::actual()?->id,
                    'user_id' => Filament::auth()->id(),
                ]);
                Notification::make()->title('Solicitud enviada. Te llamamos esta semana.')->success()->send();
            });
    }

    public function mount(): void
    {
        $this->form->fill(Residencia::actual()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('Identidad y contacto')
                ->description('Aparece al pie de cada resumen para las familias.')
                ->schema([
                    TextInput::make('nombre')->label('Nombre de la residencia')->required(),
                    TextInput::make('responsable')->label('Persona responsable'),
                    TextInput::make('direccion')->label('Dirección')->columnSpanFull(),
                    TextInput::make('telefono')->label('Teléfono')->tel(),
                    TextInput::make('email')->label('Email de contacto')->email(),
                    TextInput::make('cif')->label('CIF'),
                    TextInput::make('plazas_autorizadas')->label('Plazas autorizadas')->numeric()->minValue(1),
                ])->columns(2),
            Section::make('Auditoría')
                ->description('Se muestra en Inicio y en Calidad para llegar con todo listo.')
                ->schema([DatePicker::make('proxima_auditoria')->label('Próxima auditoría o inspección')]),
            Section::make('Pantalla de acceso')
                ->description('Fotos de la residencia que ven las auxiliares al entrar.')
                ->schema([
                    FileUpload::make('fotos')->label('Fotos (hasta 5)')->image()->multiple()->maxFiles(5)->reorderable()->directory('residencia'),
                ])->collapsible(),
        ]);
    }

    public function guardar(): void
    {
        Residencia::actual()->update($this->form->getState());
        $this->editando = false;
        Notification::make()->title('Guardado.')->success()->send();
    }

    public function plantas()
    {
        return Planta::withCount([
            'residentes' => fn ($query) => $query->where('estado', '!=', 'baja'),
            'auxiliares' => fn ($query) => $query->where('activo', true),
        ])->get();
    }
}
