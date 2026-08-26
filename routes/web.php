<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\TrabajadorController;
use App\Http\Controllers\RutaController;
use App\Http\Controllers\FlotaController;
use App\Http\Controllers\ManifiestoController;

// Guest Auth Routes
Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store']);
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

// Protected Routes
Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Usuarios
    Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
    Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
    Route::post('/usuarios/{user}/reset-password', [UserController::class, 'resetPassword'])->name('usuarios.reset-password');

    // Empresas
    Route::get('/empresas', [EmpresaController::class, 'index'])->name('empresas.index');
    Route::post('/empresas', [EmpresaController::class, 'store'])->name('empresas.store');

    // Trabajadores
    Route::get('/trabajadores', [TrabajadorController::class, 'index'])->name('trabajadores.index');
    Route::post('/trabajadores', [TrabajadorController::class, 'store'])->name('trabajadores.store');

    // Rutas
    Route::get('/rutas', [RutaController::class, 'index'])->name('rutas.index');
    Route::post('/rutas', [RutaController::class, 'store'])->name('rutas.store');

    // Flota / Choferes
    Route::get('/flota', [FlotaController::class, 'index'])->name('flota.index');
    Route::post('/flota/vehiculos', [FlotaController::class, 'storeVehiculo'])->name('flota.vehiculos.store');
    Route::post('/flota/conductores', [FlotaController::class, 'storeConductor'])->name('flota.conductores.store');

    // Manifiestos
    Route::get('/manifiestos', [ManifiestoController::class, 'index'])->name('manifiestos.index');
    Route::post('/manifiestos', [ManifiestoController::class, 'store'])->name('manifiestos.store');
    Route::put('/manifiestos/{manifiesto}/estado', [ManifiestoController::class, 'updateEstado'])->name('manifiestos.estado');
});