<?php

namespace App\Filament\Direccion\Resources\AuxiliarResource\Pages;

use App\Filament\Direccion\Concerns\MigasDePan;
use Filament\Schemas\Components\Tabs\Tab;
use App\Models\AreaEquipo;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use App\Filament\Direccion\Resources\AuxiliarResource;
use App\Models\User;
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
        return [
            'auxiliares' => Tab::make('Auxiliares')
                ->badge(User::auxiliares()->where('activo', true)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('rol', 'auxiliar')),
            'sanitarios' => Tab::make('Enfermería y medicina')
                ->badge(User::sanitarios()->where('activo', true)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('rol', 'sanitario')),
            // Otros roles creados por la dirección (Ocio, Fisioterapia…), una pestaña por área
            ...AreaEquipo::orderBy('nombre')->get()->mapWithKeys(fn (AreaEquipo $a) => ['area-'.$a->id => Tab::make($a->nombre)
                ->badge($a->personas()->where('activo', true)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('rol', 'otro')->where('area_equipo_id', $a->id))])->all(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            // Comentario de Ana en EQ-01: crear un rol nuevo (p. ej. Ocio) y añadir directamente la ficha de la persona en él.
            Action::make('anadirRol')->label('Añadir otro rol')->color('gray')->icon('heroicon-o-plus')
                ->modalHeading('Añadir otro rol')
                ->modalDescription('Para el resto del equipo: ocio, fisioterapia, cocina… No entran en la app; su ficha queda en Equipo, en su pestaña.')
                ->modalSubmitActionLabel('Continuar con la ficha')
                ->schema([
                    TextInput::make('nombre')->label('Nombre del rol o área')->placeholder('Ocio')->required()->maxLength(40)
                        ->datalist(fn () => AreaEquipo::orderBy('nombre')->pluck('nombre')->all())
                        ->helperText('Si ya existe, la persona se añade a ese.'),
                ])
                ->action(function (array $data) {
                    $nombre = mb_convert_case(trim($data['nombre']), MB_CASE_TITLE);
                    $area = AreaEquipo::whereRaw('lower(nombre) = ?', [mb_strtolower($nombre)])->first() ?? AreaEquipo::create(['nombre' => $nombre]);
                    $this->redirect(AuxiliarResource::getUrl('create', ['rol' => 'otro', 'area' => $area->id]));
                }),
            CreateAction::make('sanitario')->label('Añadir enfermería o medicina')->color('gray')
                ->url(AuxiliarResource::getUrl('create', ['rol' => 'sanitario'])),
            CreateAction::make()->label('Añadir auxiliar'),
        ];
    }
}
