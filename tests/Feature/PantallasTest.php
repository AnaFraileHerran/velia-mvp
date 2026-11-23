<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Prueba de humo: cada pantalla de los dos paneles carga sin error (200 o redirección). */
class PantallasTest extends TestCase
{
    use RefreshDatabase;

    public function test_todas_las_pantallas_cargan(): void
    {
        $this->seed();
        $fallos = [];
        foreach (['direccion' => User::where('rol', 'direccion')->first(), 'turno' => User::where('rol', 'auxiliar')->whereNotNull('pin')->first()] as $panel => $user) {
            foreach (Route::getRoutes() as $r) {
                $nombre = $r->getName() ?? '';
                if (! str_starts_with($nombre, "filament.$panel.") || ! in_array('GET', $r->methods())) {
                    continue;
                }
                if (str_contains($nombre, 'auth.') || str_contains($nombre, 'logout')) {
                    continue;
                }
                $params = [];
                foreach ($r->parameterNames() as $p) {
                    if ($p === 'record') {
                        $modelo = $r->getAction('controller');
                        $params[$p] = str_contains($nombre, 'equipo') ? User::where('rol', 'auxiliar')->value('id') : 1;
                    } else {
                        continue 2;
                    }
                }
                if ($nombre === 'filament.turno.pages.registrar') {
                    $params['residente'] = \App\Models\Residente::value('id');
                }
                $this->app['auth']->forgetGuards();
                $res = $this->actingAs($user, $panel)->get(route($nombre, $params));
                $estado = $res->getStatusCode();
                if ($estado >= 400) {
                    $msg = $res->exception ? get_class($res->exception).': '.$res->exception->getMessage().' @ '.str_replace(base_path().'/', '', $res->exception->getFile()).':'.$res->exception->getLine() : '';
                    $fallos[] = "$estado $nombre $msg";
                } else {
                    fwrite(STDOUT, "OK $estado $nombre ".($res->headers->get("Location") ?? "")."\n");
                }
            }
        }
        $this->assertSame([], $fallos, implode("\n", $fallos));
    }
}
