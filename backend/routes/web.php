<?php

use Illuminate\Support\Facades\Route;

// WealthOS usa SPA (Vue 3) para el frontend.
// Las rutas web solo son necesarias si se sirve el frontend desde Laravel.
Route::get('/', function () {
    return response()->json(['service' => 'WealthOS API', 'version' => '1.0.0', 'status' => 'ok']);
});
