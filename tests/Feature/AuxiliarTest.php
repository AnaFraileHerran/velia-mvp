<?php

namespace Tests\Feature;

use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** App de la auxiliar con shadcn-vue (/auxiliar): cada pantalla carga y el registro se guarda igual que en /turno. */
class AuxiliarTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_sesion_lleva_a_entrar_y_el_pin_da_acceso(): void
    {
        $this->seed();
        $this->withoutVite();
        $this->get('/auxiliar')->assertRedirect('/auxiliar/entrar');
        $this->get('/auxiliar/entrar')->assertOk();
        $this->post('/auxiliar/entrar', ['pin' => '0000'])->assertSessionHasErrors('pin');
        $this->post('/auxiliar/entrar', ['pin' => '1234'])->assertRedirect('/auxiliar');
    }

    public function test_pantallas_y_registro(): void
    {
        $this->seed();
        $this->withoutVite();
        $lucia = User::where('name', 'Lucía Martín')->first();
        $this->actingAs($lucia, 'turno');
        $r = Residente::where('planta_id', $lucia->planta_id)->where('estado', 'en_centro')->first();

        foreach (['/auxiliar', '/auxiliar/residentes', '/auxiliar/mi-turno', '/auxiliar/cerrar', "/auxiliar/registrar/{$r->id}"] as $url) {
            $this->get($url)->assertOk();
        }

        $o = $this->postJson('/auxiliar/registrar/ordenar', ['texto' => 'Ha desayunado todo. Se ha duchado sin problema.'])->assertOk()->json();
        $this->assertNotEmpty($o['categorias']);

        $this->post("/auxiliar/registrar/{$r->id}", ['estado' => 'confirmado', 'texto' => 'Ha desayunado todo.', 'categorias' => $o['categorias'], 'propuesta' => $o['categorias']])
            ->assertRedirect('/auxiliar/residentes');
        $this->assertDatabaseHas('registros', ['residente_id' => $r->id, 'user_id' => $lucia->id, 'estado' => 'confirmado']);
        $this->assertDatabaseHas('sugerencias_ia', ['flujo' => 'registrar.ordenar', 'decision' => 'aceptada']);
    }
}
