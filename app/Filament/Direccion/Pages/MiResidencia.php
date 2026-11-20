<?php

namespace App\Filament\Direccion\Pages;

use App\Models\Planta;
use App\Models\Residencia;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * 6 · Mi residencia (wireframe «V2 · Mi residencia»).
 * Arriba lo que la dirección cambia (identidad, auditoría, fotos); debajo, solo lectura (plan, plantas, normativa).
 */
class MiResidencia extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Mi residencia';

    protected static ?string $title = 'Mi residencia';

    protected static ?string $slug = 'mi-residencia';

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.direccion.pages.mi-residencia';

    protected ?string $subheading = 'Los datos de tu centro: lo que ven las auxiliares en la tablet o el móvil y lo que firman los resúmenes para las familias.';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(Residencia::actual()->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $form->statePath('data')->schema([
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
            Section::make('Equipo sanitario y de apoyo')
                ->description('Las auxiliares ven en «Mi turno» quién está en la residencia y cómo localizarle.')
                ->schema([
                    Repeater::make('equipo')->label('')
                        ->addActionLabel('Añadir persona')
                        ->itemLabel(fn (array $state): ?string => trim(($state['nombre'] ?? '').' · '.(Residencia::PUESTOS[$state['puesto'] ?? ''] ?? ''), ' ·') ?: null)
                        ->collapsible()
                        ->schema([
                            TextInput::make('nombre')->label('Nombre')->required(),
                            Select::make('puesto')->label('Puesto')->options(Residencia::PUESTOS)->required(),
                            CheckboxList::make('dias')->label('Días que viene')
                                ->options([1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'])
                                ->columns(7)->gridDirection('row')->columnSpanFull(),
                            TimePicker::make('desde')->label('Desde')->seconds(false),
                            TimePicker::make('hasta')->label('Hasta')->seconds(false),
                            TextInput::make('contacto')->label('Cómo localizarle')->placeholder('Ext. 210 o teléfono')->columnSpanFull(),
                        ])->columns(2),
                ])->collapsible(),
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
