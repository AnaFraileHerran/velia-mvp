<?php

namespace App\Filament\Direccion\Concerns;

use App\Filament\Direccion\Pages\Inicio;

/**
 * Migas de pan en todas las secciones de dirección: «Inicio › Residentes › Carmen Ruiz».
 * Cada página declara sus pasos intermedios en migas(); la última miga es la página actual (sin enlace).
 */
trait MigasDePan
{
    /** @return array<string, string> url => texto, sin incluir Inicio ni la página actual */
    protected function migas(): array
    {
        return [];
    }

    protected function migaActual(): string
    {
        return (string) $this->getTitle();
    }

    public function getBreadcrumbs(): array
    {
        return [Inicio::getUrl() => 'Inicio', ...$this->migas(), $this->migaActual()];
    }
}
