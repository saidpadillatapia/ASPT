<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Todas las rutas de la API ahora están en routes/api.php
// protegidas con Sanctum (auth:sanctum)
