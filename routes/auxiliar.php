<?php

use App\Http\Controllers\Auxiliar\AuxiliarController as A;
use App\Http\Middleware\AuxiliarConTurno;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Support\Facades\Route;

/* App de la auxiliar con shadcn-vue (25/11/2026; flujo «Mi planta» del 26/11). En paralelo a /turno (Filament) hasta validarla. */
Route::prefix('auxiliar')->name('auxiliar.')->middleware(HandleInertiaRequests::class)->group(function () {
    Route::get('entrar', [A::class, 'entrar'])->name('entrar');
    Route::post('entrar', [A::class, 'comprobarPin'])->middleware('throttle:30,1')->name('pin');

    Route::middleware(AuxiliarConTurno::class)->group(function () {
        Route::get('/', [A::class, 'miPlanta'])->name('planta');
        Route::get('residente/{residente}', [A::class, 'ficha'])->name('ficha');
        Route::post('sin-novedad', [A::class, 'sinNovedadBloque'])->name('sin-novedad');
        Route::post('preguntas/{pregunta}', [A::class, 'responder'])->name('responder');
        Route::post('avisos/{aviso}/hecho', [A::class, 'hecho'])->name('hecho');
        Route::post('ausencias/{ausencia}/vuelto', [A::class, 'haVuelto'])->name('vuelto');
        Route::get('residentes', [A::class, 'residentes'])->name('residentes');
        Route::post('registrar/ordenar', [A::class, 'ordenar'])->name('ordenar');
        Route::get('grabar', [A::class, 'grabarSinElegir'])->name('grabar');
        Route::get('registrar/{residente}', [A::class, 'registrar'])->name('registrar');
        Route::post('registrar/{residente}', [A::class, 'guardar'])->name('guardar');
        Route::get('mi-turno', [A::class, 'miTurno'])->name('mi-turno');
        Route::get('antes-de-irte', [A::class, 'cerrarTurno'])->name('cerrar');
        Route::redirect('cerrar', '/auxiliar/antes-de-irte');
        Route::post('antes-de-irte', [A::class, 'cerrar'])->name('cerrar.guardar');
        Route::post('guia-vista', [A::class, 'guiaVista'])->name('guia');
        Route::post('salir', [A::class, 'salir'])->name('salir');
    });
});
