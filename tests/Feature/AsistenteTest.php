<?php

namespace Tests\Feature;

use App\Filament\Direccion\Pages\Ayuda;
use App\Filament\Direccion\Pages\Calidad;
use App\Filament\Direccion\Widgets\HoyNecesitaTuDecision;
use App\Models\Aviso;
use App\Models\Resumen;
use App\Models\SugerenciaIa;
use App\Models\User;
use App\Support\Destinos;
use App\Support\Ia\Asistente;
use App\Support\Ia\Sugerencias;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Asistente IA, sugerencias en los flujos y navegación contextual (23/11/2026). */
class AsistenteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Filament::setCurrentPanel(Filament::getPanel('direccion'));
        $this->actingAs(User::where('rol', 'direccion')->first(), 'direccion');
    }

    public function test_simulado_por_defecto_y_responde_con_enlace(): void
    {
        $this->assertTrue(Asistente::esSimulado());
        $r = Asistente::responder('¿Qué me falta para la auditoría?');
        $this->assertNotEmpty($r['texto']);
        $this->assertSame(Calidad::getUrl(), $r['enlaces'][0]['url']);

        $pin = Asistente::responder('Lucía ha olvidado el PIN y no puede entrar');
        $this->assertStringContainsString('No puede entrar', $pin['texto']);
    }

    public function test_paleta_y_busqueda(): void
    {
        $this->assertNotEmpty(Destinos::paraPaleta());
        $this->assertSame('planes-vencidos', Destinos::buscar('planes vencidos')[0]['id']);
    }

    public function test_ficha_desde_dictado(): void
    {
        $c = Sugerencias::fichaDesdeDictado('Es alérgica a la penicilina. Camina con andador. Dieta blanda sin sal. No usa sujeción. Le gusta que la llamen Carmencita.');
        $this->assertSame('la penicilina', $c['alergias']);
        $this->assertStringContainsString('andador', $c['movilidad']);
        $this->assertStringContainsString('sin sal', $c['dieta']);
        $this->assertSame(0, $c['sujecion']);
        $this->assertStringContainsString('Carmencita', $c['como_tratarle']);
    }

    public function test_sugerencias_de_avisos_y_resumenes_no_fallan(): void
    {
        foreach (Aviso::all() as $a) {
            Sugerencias::paraAviso($a);
        }
        foreach (Resumen::all() as $r) {
            $this->assertNotSame('', Sugerencias::resumenFamilia($r));
        }
        $this->assertTrue(true);
    }

    public function test_aceptar_sugerencia_deja_rastro(): void
    {
        $aviso = Aviso::abiertos()->get()->first(fn ($a) => Sugerencias::paraAviso($a));
        $this->assertNotNull($aviso, 'Los datos de ejemplo deberían tener al menos un aviso con sugerencia');
        Livewire::test(HoyNecesitaTuDecision::class)->call('aceptarSugerencia', $aviso->id);
        $this->assertSame('aceptada', SugerenciaIa::first()->decision);
        $this->assertNotSame('pendiente', $aviso->fresh()->estado);
    }

    public function test_ayuda_responde(): void
    {
        Livewire::test(Ayuda::class)
            ->call('preguntar', '¿Cómo envío el resumen a las familias?')
            ->assertSee('Redactar con Velia');
    }
}
