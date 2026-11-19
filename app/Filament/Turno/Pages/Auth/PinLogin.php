<?php

namespace App\Filament\Turno\Pages\Auth;

use App\Models\Residencia;
use App\Models\User;
use App\Support\Dispositivo;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * A1 · Entrar con PIN. Tablet: «Te reconoceremos al escribirlo» (el PIN identifica a la auxiliar).
 * Móvil vinculado: «Hola, Lucía» y solo se comprueba su PIN.
 * Nada bloquea: tras 3 intentos se ofrece pedir el PIN a la responsable de turno (regla 3).
 */
class PinLogin extends Login
{
    protected static string $view = 'filament.turno.auth.pin-login';

    public string $pin = '';

    public ?string $error = null;

    public int $fallos = 0;

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }
    }

    public function updatedPin(): void
    {
        $this->error = null;
        if (strlen($this->pin) === 4) {
            $this->entrar();
        }
    }

    public function entrar(): ?LoginResponse
    {
        $clave = 'pin:'.request()->ip();
        if (RateLimiter::tooManyAttempts($clave, 10)) {
            $this->error = 'Demasiados intentos. Espera un minuto o pide ayuda a la responsable de turno.';
            $this->pin = '';

            return null;
        }

        $vinculada = Dispositivo::usuarioVinculado();
        $candidatas = $vinculada ? collect([$vinculada]) : User::auxiliares()->where('activo', true)->whereNotNull('pin')->get();
        $user = $candidatas->first(fn (User $u) => Hash::check($this->pin, $u->pin));

        if (! $user) {
            RateLimiter::hit($clave, 60);
            $this->fallos++;
            $this->pin = '';
            $this->error = $this->fallos >= 3
                ? 'El PIN no es correcto. Si no lo recuerdas, la responsable de turno te da uno nuevo.'
                : 'El PIN no es correcto. Prueba otra vez.';

            return null;
        }

        RateLimiter::clear($clave);
        Filament::auth()->login($user);
        session()->regenerate();

        return app(LoginResponse::class);
    }

    public function authenticate(): ?LoginResponse
    {
        return $this->entrar();
    }

    protected function getViewData(): array
    {
        return [
            'residencia' => Residencia::actual(),
            'vinculada' => Dispositivo::usuarioVinculado(),
            'esMovil' => Dispositivo::esMovil(),
        ];
    }
}
