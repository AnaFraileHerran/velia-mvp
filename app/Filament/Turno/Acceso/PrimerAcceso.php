<?php

namespace App\Filament\Turno\Acceso;

use App\Models\Residencia;
use App\Models\User;
use App\Support\Dispositivo;
use Filament\Facades\Filament;
use Filament\Pages\SimplePage;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Url;

/**
 * A00 · Primer acceso (tablet 116:606 + 116:653 · móvil 118:347 + 118:369).
 * Paso 1 · tablet: elige tu nombre + PIN provisional. Móvil: código de 6 caracteres (no se lista a nadie).
 * Paso 2 · crea tu PIN (dos veces) y prueba el micrófono. Si no hay micrófono, puede escribir: no bloquea.
 */
class PrimerAcceso extends SimplePage
{
    protected static string $view = 'filament.turno.auth.primer-acceso';

    protected static string $layout = 'filament-panels::components.layout.simple';

    #[Url]
    public int $paso = 1;

    public ?int $elegida = null;

    public string $pin = '';

    public string $codigo = '';

    public string $nuevo = '';

    public string $repetido = '';

    public bool $confirmando = false;

    public ?string $error = null;

    public bool $esMovil = false;

    public function mount(): void
    {
        $this->esMovil = Dispositivo::esMovil();
        if ($this->paso === 2 && ! Filament::auth()->user()?->pin_provisional) {
            $this->paso = 1;
        }
    }

    public function getTitle(): string
    {
        return 'Tu primer día en Velia';
    }

    public function pendientes()
    {
        return User::auxiliares()->where('activo', true)->where('pin_provisional', true)->with('planta')->orderBy('name')->get();
    }

    public function updatedPin(): void
    {
        $this->error = null;
        if (strlen($this->pin) < 4) {
            return;
        }
        $user = $this->elegida ? User::find($this->elegida) : null;
        if (! $user || ! $user->pin_provisional || ! Hash::check($this->pin, $user->pin)) {
            $this->pin = '';
            $this->error = 'Ese PIN provisional no es correcto. Pídeselo de nuevo a la responsable de turno.';

            return;
        }
        $this->entrarYSeguir($user);
    }

    public function vincularConCodigo(): void
    {
        $user = User::auxiliares()->where('activo', true)->where('codigo_invitacion', strtoupper(trim($this->codigo)))->first();
        if (! $user) {
            $this->error = 'No encontramos ese código. Revisa las letras y los números.';

            return;
        }
        Dispositivo::vincular($user);
        $user->forceFill(['codigo_invitacion' => null])->save(); // un solo uso
        $user->forceFill(['pin_provisional' => true])->save();
        $this->entrarYSeguir($user);
    }

    private function entrarYSeguir(User $user): void
    {
        Filament::auth()->login($user);
        session()->regenerate();
        $this->reset('pin', 'error');
        $this->paso = 2;
    }

    public function updatedNuevo(): void
    {
        $this->error = null;
        if (strlen($this->nuevo) === 4) {
            $this->confirmando = true;
        }
    }

    public function updatedRepetido(): void
    {
        if (strlen($this->repetido) < 4) {
            return;
        }
        if ($this->repetido !== $this->nuevo) {
            $this->reset('nuevo', 'repetido', 'confirmando');
            $this->error = 'Los dos PIN no coinciden. Vuelve a crearlo.';

            return;
        }
        /** @var User $user */
        $user = Filament::auth()->user();
        $enUso = User::where('id', '!=', $user->id)->where('activo', true)->whereNotNull('pin')->get()
            ->contains(fn (User $u) => Hash::check($this->nuevo, $u->pin));
        if ($enUso || in_array($this->nuevo, ['0000', '1111', '1234', '4321'], true)) {
            $this->reset('nuevo', 'repetido', 'confirmando');
            $this->error = 'Elige otro PIN, ese es fácil de adivinar.';

            return;
        }
        $user->forceFill(['pin' => Hash::make($this->nuevo), 'pin_provisional' => false])->save();
        $this->paso = 3; // micrófono
    }

    public function terminar()
    {
        session()->flash('velia_consejos', true); // el relevo enseña 3 consejos la primera vez

        return redirect()->to(Filament::getUrl());
    }

    protected function getViewData(): array
    {
        return ['residencia' => Residencia::actual(), 'user' => Filament::auth()->user()];
    }
}
