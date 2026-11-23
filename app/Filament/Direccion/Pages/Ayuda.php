<?php

namespace App\Filament\Direccion\Pages;

use App\Filament\Direccion\Resources\AusenciaResource;
use App\Filament\Direccion\Resources\AuxiliarResource;
use App\Filament\Direccion\Resources\ResidenteResource;
use App\Filament\Direccion\Resources\ResumenResource;
use Filament\Pages\Page;

/**
 * 7 · Ayuda. Organizada por tareas (lo que la dirección quiere hacer), no por pantallas.
 * Cada respuesta: 2–3 frases y un enlace directo a donde se hace.
 */
class Ayuda extends Page
{
    use \App\Filament\Direccion\Concerns\MigasDePan;

    protected static ?string $title = 'Ayuda';

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?int $navigationSort = 7;

    protected static string $view = 'filament.direccion.pages.ayuda';

    protected ?string $subheading = 'Las dudas más frecuentes, cada una con el enlace a donde se resuelve.';

    public function temas(): array
    {
        return [
            'Empezar' => [
                ['Dar de alta a una auxiliar', 'En Equipo, «Añadir auxiliar». Velia genera un PIN provisional y un código para su móvil: díselos en persona. El primer día crea su propio PIN.', AuxiliarResource::getUrl('create'), 'Ir a Equipo'],
                ['Añadir enfermería o medicina', 'En Equipo, pestaña «Enfermería y medicina». Indica días y horario: las auxiliares verán en «Mi turno» quién está y cómo localizarle.', AuxiliarResource::getUrl('index', ['activeTab' => 'sanitarios']), 'Ir a Equipo'],
                ['Dar de alta a un residente', 'En Residentes, «Añadir residente». Rellena al menos la ficha de cuidados: sin ella, una auxiliar nueva no sabrá cómo atenderle.', ResidenteResource::getUrl('create'), 'Añadir residente'],
                ['Añadir a la familia', 'En la ficha del residente, pestaña «Autorizados de la familia». Sin contacto, no se puede enviar el resumen semanal.', ResidenteResource::getUrl(), 'Ir a Residentes'],
            ],
            'Cada día' => [
                ['Qué mirar al entrar', 'Inicio muestra como máximo 5 cosas que necesitan tu decisión. Si no hay ninguna, el día va bien.', Inicio::getUrl(), 'Ir a Inicio'],
                ['Enviar el resumen semanal a las familias', 'En Familias, revisa los resúmenes con incidencias uno a uno. Los que no tienen incidencias se pueden enviar juntos.', ResumenResource::getUrl(), 'Ir a Familias'],
                ['Un residente sale unos días', 'En Ausencias, registra la salida y cuándo vuelve. Las auxiliares lo ven en su relevo.', AusenciaResource::getUrl(), 'Ir a Ausencias'],
            ],
            'Si algo falla' => [
                ['Una auxiliar ha olvidado su PIN', 'Lo restablece la responsable de turno desde la tablet, también de noche. Tú también puedes, en Equipo, «Restablecer PIN».', AuxiliarResource::getUrl(), 'Ir a Equipo'],
                ['No funciona el micrófono o la conexión', 'Nada bloquea un registro: siempre se puede «Escribir». Revisa el permiso del micrófono en los ajustes del navegador de la tablet.', null, null],
                ['Hay un dato mal en una ficha', 'Usa «Corregir» en el bloque de la ficha. Pide un motivo y guarda la versión anterior: así queda la evidencia para la auditoría.', ResidenteResource::getUrl(), 'Ir a Residentes'],
            ],
        ];
    }
}
