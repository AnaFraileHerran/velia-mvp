<?php

namespace App\Filament\Turno\Acceso;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\SimplePage;
use Illuminate\Support\Facades\Hash;

/**
 * A0 · Restablecer PIN (116:548). Lo hace la responsable de turno, también de noche.
 * Si ya ha entrado (desde «Mi turno») no vuelve a pedir su PIN.
 */
class RestablecerPin extends SimplePage
{
    protected static string $view = 'filament.turno.auth.restablecer-pin';

    protected static string $layout = 'filament-panels::components.layout.simple';

    public string $pin = '';

    public ?int $responsableId = null;

    public ?string $error = null;

    public ?string $resultado = null;

    public ?string $para = null;

    public function mount(): void
    {
        $u = Filament::auth()->user();
        if ($u?->responsable_turno) {
            $this->responsableId = $u->id;
        }
    }

    public function getTitle(): string
    {
        return 'Restablecer el PIN de una compañera';
    }

    public function updatedPin(): void
    {
        $this->error = null;
        if (strlen($this->pin) < 4) {
            return;
        }
        $r = User::auxiliares()->where('activo', true)->where('responsable_turno', true)->get()
            ->first(fn (User $u) => Hash::check($this->pin, $u->pin));
        $this->pin = '';
        if (! $r) {
            $this->error = 'Ese PIN no es de una responsable de turno. Si no hay ninguna, llama a la dirección.';

            return;
        }
        $this->responsableId = $r->id;
    }

    public function companeras()
    {
        return User::auxiliares()->where('activo', true)->where('id', '!=', $this->responsableId)->with('planta')->orderBy('name')->get();
    }

    public function restablecer(int $id): void
    {
        $c = User::auxiliares()->where('activo', true)->findOrFail($id);
        $this->resultado = $c->generarPinProvisional(User::find($this->responsableId));
        $this->para = strtok($c->name, ' ');
    }
}
