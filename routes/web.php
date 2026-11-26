<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// App de la auxiliar con shadcn-vue (en paralelo a /turno)
require __DIR__.'/auxiliar.php';

// La versión H de dirección se llama ahora «Nueva versión» (25/11/2026): los enlaces antiguos siguen funcionando.
Route::get('direccion-h/{resto?}', fn (?string $resto = null) => redirect('/nueva-version'.($resto ? '/'.$resto : ''), 301))->where('resto', '.*');
