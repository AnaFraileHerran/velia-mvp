<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Foto de una persona o, si no hay, sus iniciales en un círculo gris claro.
 * Las iniciales se generan aquí (sin servicios externos: nada de datos de residentes fuera).
 */
class Avatar
{
    public static function url(?string $foto, string $nombre): string
    {
        if ($foto && preg_match('#^https?://#', $foto)) {
            return $foto; // foto ficticia de la demo (FotosDemo)
        }
        if ($foto) {
            return Storage::disk('public')->url($foto);
        }

        return self::iniciales($nombre);
    }

    public static function iniciales(string $nombre): string
    {
        $partes = preg_split('/\s+/', trim($nombre)) ?: [];
        $ini = mb_strtoupper(mb_substr($partes[0] ?? '', 0, 1).mb_substr($partes[1] ?? '', 0, 1));
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'
            .'<rect width="64" height="64" rx="32" fill="#E2E6ED"/>'
            .'<text x="32" y="33" dy=".35em" text-anchor="middle" font-family="Albert Sans, Arial, sans-serif" font-size="24" font-weight="600" fill="#3E3E3A">'
            .htmlspecialchars($ini, ENT_XML1).'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
