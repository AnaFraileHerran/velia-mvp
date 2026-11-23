<?php

namespace App\Support;

/**
 * Fotos ficticias para la demo (Residencia Los Almendros). Son URLs de retratos de stock de randomuser.me:
 * no se envía ningún dato de personas reales. En una residencia real la foto la sube la dirección.
 */
class FotosDemo
{
    private const MUJERES = ['Ana', 'Lucía', 'Sonia', 'Inés', 'Elena', 'Marta', 'Carmen', 'Rosario', 'Pilar', 'Dolores', 'Teresa', 'Josefa',
        'Mercedes', 'Isabel', 'Concepción', 'Consuelo', 'María', 'Amparo', 'Remedios', 'Encarnación', 'Julia', 'Rosa', 'Antonia', 'Francisca'];

    /**
     * Residentes: retratos de personas mayores de Unsplash (licencia libre de Unsplash), revisados uno a uno.
     * Se reparten por nombre para que cada residente tenga siempre la misma foto.
     */
    private const MAYORES_MUJERES = [
        '1719037108848-685e9e599827', '1446161543652-83eaa65fddab', '1525599428495-0441bd5c67de',
        '1566616213894-2d4e1baee5d8', '1706272971886-20f2fadf577e', '1772975968186-0b932c38bf97',
    ];

    private const MAYORES_HOMBRES = [
        '1533101585792-27f81a845550', '1715515012774-501892dcabe4', '1668621819576-e5a92bcd7e15',
        '1640583818579-740430212d27', '1566761284295-af58908238bb',
    ];

    public static function esMujer(string $nombre): bool
    {
        $primero = strtok(trim($nombre), ' ');

        return in_array($primero, self::MUJERES, true) || (str_ends_with($primero, 'a') && ! in_array($primero, ['Luca', 'Andrea'], true));
    }

    public static function residente(string $nombre, int $semilla): string
    {
        $lista = self::esMujer($nombre) ? self::MAYORES_MUJERES : self::MAYORES_HOMBRES;

        return 'https://images.unsplash.com/photo-'.$lista[$semilla % count($lista)].'?w=240&h=240&fit=crop&crop=faces';
    }

    public static function para(string $nombre, int $semilla): string
    {
        $primero = strtok(trim($nombre), ' ');
        $mujer = in_array($primero, self::MUJERES, true) || (str_ends_with($primero, 'a') && ! in_array($primero, ['Luca', 'Andrea'], true));

        return 'https://randomuser.me/api/portraits/'.($mujer ? 'women' : 'men').'/'.(($semilla * 7 + 11) % 90).'.jpg';
    }
}
