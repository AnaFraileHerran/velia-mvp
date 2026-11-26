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

        foreach (['/auxiliar', '/auxiliar/mi-turno', '/auxiliar/antes-de-irte', "/auxiliar/residente/{$r->id}", "/auxiliar/registrar/{$r->id}"] as $url) {
            $this->get($url)->assertOk();
        }
        // Residentes se fusiona con el resumen en Mi planta (26/11)
        $this->get('/auxiliar/residentes')->assertRedirect('/auxiliar');

        $o = $this->postJson('/auxiliar/registrar/ordenar', ['texto' => 'Ha desayunado todo. Se ha duchado sin problema.'])->assertOk()->json();
        $this->assertNotEmpty($o['categorias']);

        $this->post("/auxiliar/registrar/{$r->id}", ['estado' => 'confirmado', 'texto' => 'Ha desayunado todo.', 'categorias' => $o['categorias'], 'propuesta' => $o['categorias']])
            ->assertRedirect('/auxiliar');
        $this->assertDatabaseHas('registros', ['residente_id' => $r->id, 'user_id' => $lucia->id, 'estado' => 'confirmado']);
        $this->assertDatabaseHas('sugerencias_ia', ['flujo' => 'registrar.ordenar', 'decision' => 'aceptada']);

        // «Sin novedad» en bloque al final (decisión 3): crea un registro «sin_novedad» por cada residente pendiente elegido
        $otro = Residente::where('planta_id', $lucia->planta_id)->where('estado', 'en_centro')->whereKeyNot($r->id)
            ->whereDoesntHave('registros', fn ($q) => $q->where('user_id', $lucia->id))->first();
        if ($otro) {
            $this->post('/auxiliar/sin-novedad', ['ids' => [$otro->id]])->assertRedirect();
            $this->assertDatabaseHas('registros', ['residente_id' => $otro->id, 'estado' => 'sin_novedad']);
        }
    }

    /** 26/11/2026: tras una caída confirmada, la dirección recibe «Informar a la familia» y la comunicación queda anotada. */
    public function test_una_caida_pide_informar_a_la_familia(): void
    {
        $this->seed();
        $this->withoutVite();
        $lucia = User::where('name', 'Lucía Martín')->first();
        $this->actingAs($lucia, 'turno');
        $r = Residente::where('planta_id', $lucia->planta_id)->where('estado', 'en_centro')->first();

        $this->post("/auxiliar/registrar/{$r->id}", ['estado' => 'confirmado', 'texto' => 'Se ha caído al levantarse.',
            'categorias' => ['Incidencias' => 'Se ha caído al levantarse.'], 'incidencia' => true, 'tipo' => 'Caídas y mareos'])->assertRedirect();

        $aviso = \App\Models\Aviso::where('tipo', 'informar_familia')->where('residente_id', $r->id)->first();
        $this->assertNotNull($aviso);

        $c = \App\Models\Comunicacion::create(['residente_id' => $r->id, 'a_quien' => 'Familia', 'motivo' => 'caida', 'canal' => 'telefono',
            'que' => 'Se le informa de la caída.', 'fecha' => now(), 'aviso_id' => $aviso->id]);
        $this->assertSame($c->id, $r->comunicaciones()->first()->id);
    }

    public function test_el_turno_termina_solo_al_acabar_el_horario(): void
    {
        $this->seed();
        $this->withoutVite();
        $lucia = User::where('name', 'Lucía Martín')->first();
        $lucia->turnos()->whereNull('fin')->update(['fin' => now()]);
        $viejo = $lucia->turnos()->create(['planta_id' => $lucia->planta_id, 'inicio' => now()->subHours(12)]);
        $this->actingAs($lucia, 'turno')->get('/auxiliar')->assertOk();
        $this->assertNotNull($viejo->fresh()->fin);
        $this->assertNotEquals($viejo->id, $lucia->fresh()->turnoAbierto()?->id);
    }
}
