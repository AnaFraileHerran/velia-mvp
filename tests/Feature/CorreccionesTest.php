<?php

namespace Tests\Feature;

use App\Models\Correccion;
use App\Models\Residente;
use App\Support\Correcciones;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Regla 5: toda corrección pide motivo y guarda versión. */
class CorreccionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_motivo_no_se_corrige(): void
    {
        $r = Residente::create(['nombre' => 'Carmen Ruiz', 'dieta' => 'Normal']);
        $this->expectException(\InvalidArgumentException::class);
        Correcciones::aplicar($r, ['dieta' => 'Blanda, sin sal'], '  ');
    }

    public function test_guarda_antes_ahora_y_motivo(): void
    {
        $r = Residente::create(['nombre' => 'Carmen Ruiz', 'dieta' => 'Normal']);
        $n = Correcciones::aplicar($r, ['dieta' => 'Blanda, sin sal', 'riesgos' => null], 'Indicación de la logopeda');

        $this->assertSame(1, $n);
        $this->assertSame('Blanda, sin sal', $r->fresh()->dieta);
        $c = Correccion::first();
        $this->assertSame(['Normal', 'Blanda, sin sal', 'Indicación de la logopeda'], [$c->antes, $c->ahora, $c->motivo]);
    }
}
