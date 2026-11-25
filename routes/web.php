<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// App de la auxiliar con shadcn-vue (en paralelo a /turno)
require __DIR__.'/auxiliar.php';
