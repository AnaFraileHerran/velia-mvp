<?php

use App\Http\Controllers\Auxiliar\AuxiliarController as A;
use App\Http\Middleware\AuxiliarConTurno;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Support\Facades\Route;

/* App de la auxiliar con shadcn-vue (25/11/2026). En paralelo a /turno (Filament) hasta validarla. */
Route::prefix('auxiliar')->name('auxiliar.')->middleware(HandleInertiaRequests::class)->group(function () {
    Route::get('entrar', [A::class, 'entrar'])->name('entrar');
    Route::post('entrar', [A::class, 'comprobarPin'])->middleware('throttle:30,1')->name('pin');

    Route::middleware(AuxiliarConTurno::class)->group(function () {
        Route::get('/', [A::class, 'resumen'])->name('resumen');
        Route::post('preguntas/{pregunta}', [A::class, 'responder'])->name('responder');
        Route::post('avisos/{aviso}/hecho', [A::class, 'hecho'])->name('hecho');
        Route::post('ausencias/{ausencia}/vuelto', [A::class, 'haVuelto'])->name('vuelto');
        Route::get('residentes', [A::class, 'residentes'])->name('residentes');
        Route::post('registrar/ordenar', [A::class, 'ordenar'])->name('ordenar');
        Route::get('registrar/{residente}', [A::class, 'registrar'])->name('registrar');
        Route::post('registrar/{residente}', [A::class, 'guardar'])->name('guardar');
        Route::get('mi-turno', [A::class, 'miTurno'])->name('mi-turno');
        Route::get('cerrar', [A::class, 'cerrarTurno'])->name('cerrar');
        Route::post('cerrar', [A::class, 'cerrar'])->name('cerrar.guardar');
        Route::post('salir', [A::class, 'salir'])->name('salir');
    });
});
