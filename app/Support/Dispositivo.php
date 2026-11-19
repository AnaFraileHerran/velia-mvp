<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cookie;

/**
 * Un móvil personal queda vinculado a una auxiliar (cookie cifrada). En él no se muestra
 * la lista de compañeras ni datos fuera de la sesión. La tablet de planta no se vincula.
 */
class Dispositivo
{
    public const COOKIE = 'velia_movil';

    public static function esMovil(): bool
    {
        return (bool) preg_match('/Mobi|iPhone|Android(?!.*Tablet)/i', request()->userAgent() ?? '');
    }

    public static function usuarioVinculado(): ?User
    {
        $id = request()->cookie(self::COOKIE);

        return $id ? User::auxiliares()->where('activo', true)->find($id) : null;
    }

    public static function vincular(User $user): void
    {
        Cookie::queue(self::COOKIE, (string) $user->id, 60 * 24 * 365);
    }
}
