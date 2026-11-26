<?php

namespace App\Filament\Direccion\Pages;

use App\Filament\Direccion\Concerns\MigasDePan;
use App\Filament\Direccion\Resources\AusenciaResource;
use App\Filament\Direccion\Resources\AuxiliarResource;
use App\Filament\Direccion\Resources\ResidenteResource;
use App\Filament\Direccion\Resources\ResumenResource;
use Filament\Pages\Page;
use App\Support\Ia\Asistente;
use Livewire\Attributes\Url;

/**
 * 7 · Ayuda. Organizada por tareas (lo que la dirección quiere hacer), no por pantallas.
 * Cada respuesta: 2–3 frases y un enlace directo a donde se hace.
 */
class Ayuda extends Page
{
    use MigasDePan;

    protected static ?string $title = 'Ayuda';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.direccion.pages.ayuda';

    protected ?string $subheading = 'Pregúntale a Velia, con voz o por escrito, o busca en las dudas frecuentes.';

    /** Pregunta que llega desde la paleta «Ir a…» (⌘K): «Preguntar a Velia: …». */
    #[Url(as: 'pregunta')]
    public string $preguntaInicial = '';

    public string $pregunta = '';

    /** Conversación de esta visita. No se guarda: la ayuda no necesita datos de residentes ni dejar rastro. */
    public array $conversacion = [];

    public function mount(): void
    {
        if (trim($this->preguntaInicial) !== '') {
            $this->pregunta = $this->preguntaInicial;
            $this->preguntaInicial = '';
            $this->preguntar();
        }
    }

    public function preguntar(?string $texto = null): void
    {
        $texto = trim($texto ?? $this->pregunta);
        if ($texto === '') {
            return;
        }
        $this->conversacion[] = ['de' => 'yo', 'texto' => $texto];
        $this->conversacion[] = ['de' => 'velia', ...Asistente::responder($texto)];
        $this->pregunta = '';
        $this->dispatch('velia-respuesta', texto: end($this->conversacion)['texto']);
    }

    public function preguntarEjemplo(int $i): void
    {
        $this->preguntar($this->ejemplos()[$i] ?? null);
    }

    /** Preguntas de ejemplo para empezar (las más frecuentes en las sesiones con directoras). */
    public function ejemplos(): array
    {
        return ['¿Qué me falta para la auditoría?', 'Una auxiliar no puede entrar', '¿Cómo envío el resumen a las familias?', '¿Cómo doy de alta a un residente?'];
    }

    public function temas(): array
    {
        $h = \App\Support\VersionH::activa();

        return [
            'Empezar' => [
                ['Dar de alta a una auxiliar', ($h ? 'En Equipo, «Añadir persona» y elige «Auxiliar». Si tienes su documentación, «Subir documentos» rellena la ficha y tú la revisas.' : 'En Equipo, «Añadir auxiliar».').' Velia genera un PIN provisional y un código para su móvil: díselos en persona. El primer día crea su propio PIN.', AuxiliarResource::getUrl('create'), 'Ir a Equipo'],
                ['Añadir enfermería o medicina', 'En Equipo, pestaña «Enfermería y medicina». Indica días y horario: las auxiliares verán en «Mi turno» quién está y cómo localizarle.', AuxiliarResource::getUrl('index', ['tab' => 'sanitarios']), 'Ir a Equipo'],
                ['Dar de alta a un residente', 'En Residentes, «Añadir residente». Rellena al menos la ficha de cuidados: sin ella, una auxiliar nueva no sabrá cómo atenderle.', ResidenteResource::getUrl('create'), 'Añadir residente'],
                ['Añadir a la familia', ($h ? 'En la ficha del residente, bloque «Familia».' : 'En la ficha del residente, pestaña «Autorizados de la familia».').' Sin contacto, no se puede enviar el resumen semanal.', ResidenteResource::getUrl(), 'Ir a Residentes'],
            ],
            'Cada día' => [
                ['Qué mirar al entrar', 'Inicio muestra como máximo 5 cosas que necesitan tu decisión. Si no hay ninguna, el día va bien.', Inicio::getUrl(), 'Ir a Inicio'],
                ['Enviar el resumen semanal a las familias', ($h ? 'En Familias, «Revisar y enviar». ' : 'En Familias, ').'revisa los resúmenes con incidencias uno a uno. Los que no tienen incidencias se pueden enviar juntos.', $h ? \App\Filament\Direccion\Pages\Familias::getUrl() : ResumenResource::getUrl(), 'Ir a Familias'],
                $h ? ['Un residente sale unos días', 'En Residentes, «Registrar salida» en su fila. Sale como «Fuera» con el motivo y cuándo vuelve; al volver, «Ha vuelto».', ResidenteResource::getUrl(), 'Ir a Residentes']
                    : ['Un residente sale unos días', 'En Ausencias, registra la salida y cuándo vuelve. Las auxiliares lo ven en su relevo.', AusenciaResource::getUrl(), 'Ir a Ausencias'],
            ],
            'Si algo falla' => [
                ['Una auxiliar ha olvidado su PIN', 'Lo restablece la responsable de turno desde la tablet, también de noche. Tú también puedes, en su ficha de Equipo, «Restablecer PIN» (bloque «Datos y acceso a Velia»).', AuxiliarResource::getUrl(), 'Ir a Equipo'],
                ['No funciona el micrófono o la conexión', 'Nada bloquea un registro: siempre se puede «Escribir». Revisa el permiso del micrófono en los ajustes del navegador de la tablet.', null, null],
                ['Hay un dato mal en una ficha', 'Usa «Corregir» en el bloque de la ficha. Pide un motivo y guarda la versión anterior: así queda la evidencia para la auditoría.', ResidenteResource::getUrl(), 'Ir a Residentes'],
            ],
        ];
    }
}
