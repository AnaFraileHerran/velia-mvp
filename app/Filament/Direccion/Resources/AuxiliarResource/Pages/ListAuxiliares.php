<?php

namespace App\Filament\Direccion\Resources\AuxiliarResource\Pages;

use App\Filament\Direccion\Concerns\MigasDePan;
use Filament\Schemas\Components\Tabs\Tab;
use App\Models\AreaEquipo;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use App\Filament\Direccion\Resources\AuxiliarResource;
use App\Models\User;
use App\Support\VersionH;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAuxiliares extends ListRecords
{
    use MigasDePan;

    protected static string $resource = AuxiliarResource::class;

    public function getSubheading(): ?string
    {
        $n = User::auxiliares()->where('activo', true)->count();
        $s = User::sanitarios()->where('activo', true)->count();

        return "{$n} auxiliares · {$s} de enfermería y medicina";
    }

    public function getTabs(): array
    {
        $areas = AreaEquipo::orderBy('nombre')->get()->mapWithKeys(fn (AreaEquipo $a) => ['area-'.$a->id => Tab::make($a->nombre)
            ->badge($a->personas()->where('activo', true)->count())
            ->modifyQueryUsing(fn ($query) => $query->where('rol', 'otro')->where('area_equipo_id', $a->id))])->all();

        $tabs = [
            'auxiliares' => Tab::make('Auxiliares')
                ->badge(User::auxiliares()->where('activo', true)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('rol', 'auxiliar')),
            'sanitarios' => Tab::make('Enfermería y medicina')
                ->badge(User::sanitarios()->where('activo', true)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('rol', 'sanitario')),
            // Otros roles creados por la dirección (Ocio, Fisioterapia…), una pestaña por área
            ...$areas,
        ];

        if (! VersionH::activa()) {
            return $tabs;
        }

        // Nueva versión (26/11, comentarios de Pablo): «Todos» primero, como hace Filament. Un rol nuevo se crea desde «Añadir persona»
        // (opción «Otro rol nuevo…»): Ana prefiere no repetir la acción en las pestañas.
        return [
            'todos' => Tab::make('Todos')->badge(User::whereIn('rol', ['auxiliar', 'sanitario', 'otro'])->where('activo', true)->count()),
            ...$tabs,
        ];
    }

    /** La pestaña «+ Añadir rol» no filtra: abre la ventana para crear el rol y vuelve a la pestaña en la que estabas. */
    public ?string $tabAnterior = null;

    public function updatingActiveTab($valor): void
    {
        $this->tabAnterior = $this->activeTab;
    }

    public function updatedActiveTab(): void
    {
        if ($this->activeTab === 'nuevo-rol') {
            $this->activeTab = $this->tabAnterior ?: 'todos';
            $this->mountAction('anadirRol');

            return;
        }
        parent::updatedActiveTab();
    }

    /** Crear un rol nuevo (p. ej. Ocio) y añadir directamente la ficha de la persona en él (comentario de Ana en EQ-01). */
    public function anadirRolAction(): Action
    {
        return $this->accionAnadirRol('anadirRol');
    }

    private function accionAnadirRol(string $nombre): Action
    {
        return Action::make($nombre)->label('Añadir otro rol')->color('gray')->icon('heroicon-o-plus')
            ->modalHeading('Añadir otro rol')
            ->modalDescription('Para el resto del equipo: ocio, fisioterapia, cocina… No entran en la app; su ficha queda en Equipo, en su pestaña.')
            ->modalSubmitActionLabel('Continuar con la ficha')
            ->schema([
                TextInput::make('nombre')->label('Nombre del rol o área')->placeholder('Ocio')->required()->maxLength(40)
                    ->datalist(fn () => AreaEquipo::orderBy('nombre')->pluck('nombre')->all())
                    ->helperText('Si ya existe, la persona se añade a ese.'),
            ])
            ->action(function (array $data) {
                $this->redirect(AuxiliarResource::getUrl('create', ['rol' => 'otro', 'area' => self::area($data['nombre'])->id]));
            });
    }

    /**
     * Nueva versión: un solo «Añadir persona» y el rol se elige al empezar (comentario de Pablo: escala sin un botón por rol).
     */
    private function accionAnadirPersona(): Action
    {
        $opciones = ['auxiliar' => 'Auxiliar', 'sanitario' => 'Enfermería o medicina']
            + AreaEquipo::orderBy('nombre')->pluck('nombre', 'id')->mapWithKeys(fn ($n, $id) => ['area-'.$id => $n])->all()
            + ['nueva' => 'Otro rol nuevo…'];

        return Action::make('anadirPersona')->label('Añadir persona')->icon('heroicon-m-plus')
            ->modalHeading('Añadir una persona al equipo')
            ->modalDescription('Primero, su rol. Las auxiliares entran en la app con PIN; el resto queda en Equipo y las auxiliares lo ven en «Mi turno».')
            ->modalSubmitActionLabel('Continuar con la ficha')
            ->modalWidth('md')
            ->schema([
                Radio::make('rol')->label('¿Qué rol tiene?')->options($opciones)->required()->default('auxiliar')->live(),
                TextInput::make('nombre_rol')->label('Nombre del rol nuevo')->placeholder('Fisioterapia')->maxLength(40)
                    ->visible(fn (Get $get) => $get('rol') === 'nueva')->required(fn (Get $get) => $get('rol') === 'nueva'),
            ])
            ->action(function (array $data) {
                $params = match (true) {
                    $data['rol'] === 'auxiliar' => [],
                    $data['rol'] === 'sanitario' => ['rol' => 'sanitario'],
                    $data['rol'] === 'nueva' => ['rol' => 'otro', 'area' => self::area($data['nombre_rol'])->id],
                    default => ['rol' => 'otro', 'area' => (int) str_replace('area-', '', $data['rol'])],
                };
                $this->redirect(AuxiliarResource::getUrl('create', $params));
            });
    }

    private static function area(string $nombre): AreaEquipo
    {
        $nombre = mb_convert_case(trim($nombre), MB_CASE_TITLE);

        return AreaEquipo::whereRaw('lower(nombre) = ?', [mb_strtolower($nombre)])->first() ?? AreaEquipo::create(['nombre' => $nombre]);
    }

    protected function getHeaderActions(): array
    {
        if (VersionH::activa()) {
            return [$this->accionAnadirPersona()];
        }

        return [
            $this->accionAnadirRol('anadirRolCabecera'),
            CreateAction::make('sanitario')->label('Añadir enfermería o medicina')->color('gray')
                ->url(AuxiliarResource::getUrl('create', ['rol' => 'sanitario'])),
            CreateAction::make()->label('Añadir auxiliar'),
        ];
    }
}
