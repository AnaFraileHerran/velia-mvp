<?php

namespace Tests\Unit;

use App\Support\OrdenarRegistro;
use PHPUnit\Framework\TestCase;

class OrdenarRegistroTest extends TestCase
{
    public function test_reparte_por_categorias_y_marca_incidencia(): void
    {
        $o = OrdenarRegistro::ordenar('Ha desayunado poco. Se ha mareado al levantarse. Tranquila y habladora.');

        $this->assertArrayHasKey('Alimentación e hidratación', $o['categorias']);
        $this->assertTrue($o['incidencia']);
        $this->assertSame('Caídas y mareos', $o['tipo_incidencia']);
        $this->assertArrayHasKey('Ánimo', $o['categorias']);
    }
}
