<?php

namespace App\Filament\Turno\Pages;

use App\Filament\Turno\Concerns\ComoAtender;
use App\Models\Residente;
use App\Support\Dispositivo;
use App\Support\TurnoActual;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use App\Models\Registro;
use Filament\Notifications\Actions\Action as AccionAviso;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

/**
 * A2 · Residentes (lista 116:804 · plano 116:841 · móvil 118:433).
 * «Por registrar» arriba y abierto; registrados y fuera, plegados (comentario de Antonio en Figma).
 * En móvil no hay plano: la lista es la vista por defecto.
 */
class Residentes extends Page implements HasActions
{
    use ComoAtender;
    use InteractsWithActions;

    protected static ?string $title = 'Residentes';

    protected static ?string $slug = 'residentes';

    protected static string $view = 'filament.turno.pages.residentes';

    #[Url]
    public string $vista = 'lista';

    public string $buscar = '';

    public bool $esMovil = false;

    public function mount(): void
    {
        $this->esMovil = Dispositivo::esMovil();
        if ($this->esMovil) {
            $this->vista = 'lista';
        }
    }

    public function getSubheading(): ?string
    {
        $t = TurnoActual::make();
        $g = $t->agrupados();
        $total = $g['por_registrar']->count() + $g['registrados']->count();

        return (\Filament\Facades\Filament::auth()->user()->planta?->nombre ?? 'Todas las plantas').' · '.$g['registrados']->count().' de '.$total.' registrados';
    }

    /** «Sin novedad» a un toque desde la tarjeta (decisión UX 23/11/2026), con deshacer por si fue sin querer. */
    public function sinNovedad(int $residente): void
    {
        $r = Residente::findOrFail($residente);
        $registro = Registro::create([
            'residente_id' => $r->id,
            'user_id' => \Filament\Facades\Filament::auth()->id(),
            'turno_id' => \Filament\Facades\Filament::auth()->user()->turnoAbierto()?->id,
            'texto_original' => null,
            'categorias' => [],
            'estado' => 'sin_novedad',
            'incidencia_destacada' => false,
            'tipo_incidencia' => null,
        ]);

        Notification::make()
            ->title('Sin novedad · '.$r->nombre)
            ->success()
            ->actions([
                AccionAviso::make('deshacer')->label('Deshacer')->button()
                    ->dispatch('deshacerSinNovedad', ['registro' => $registro->id])->close(),
            ])
            ->send();
    }

    #[On('deshacerSinNovedad')]
    public function deshacerSinNovedad(int $registro): void
    {
        Registro::where('id', $registro)
            ->where('estado', 'sin_novedad')
            ->where('user_id', \Filament\Facades\Filament::auth()->id())
            ->delete();
    }

    public static function lineaEstado(Residente $r, string $estado): string
    {
        if ($estado === 'borrador') {
            return 'Borrador sin confirmar';
        }
        $ultimo = $r->registros->first();
        if (! $ultimo || $ultimo->created_at->lt(now()->subDay())) {
            return 'Sin registros en 24 h';
        }

        return 'Último registro: '.($ultimo->created_at->isToday() ? 'hoy' : 'ayer').' '.$ultimo->created_at->format('H:i');
    }

    protected function getViewData(): array
    {
        $t = TurnoActual::make();
        $g = $t->agrupados();
        if ($this->buscar !== '') {
            $q = mb_strtolower($this->buscar);
            foreach ($g as $k => $lista) {
                $g[$k] = $lista->filter(fn ($record) => str_contains(mb_strtolower($record->nombre), $q) || str_contains((string) $record->habitacion, $q));
            }
        }

        return ['t' => $t, 'g' => $g];
    }
}
