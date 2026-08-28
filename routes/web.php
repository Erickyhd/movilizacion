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

    // Usuarios CRUD
    Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
    Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
    Route::put('/usuarios/{user}', [UserController::class, 'update'])->name('usuarios.update');
    Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('usuarios.destroy');

    // Empresas CRUD
    Route::get('/empresas', [EmpresaController::class, 'index'])->name('empresas.index');
    Route::post('/empresas', [EmpresaController::class, 'store'])->name('empresas.store');
    Route::put('/empresas/{empresa}', [EmpresaController::class, 'update'])->name('empresas.update');
    Route::delete('/empresas/{empresa}', [EmpresaController::class, 'destroy'])->name('empresas.destroy');

    // Trabajadores CRUD
    Route::get('/trabajadores', [TrabajadorController::class, 'index'])->name('trabajadores.index');
    Route::post('/trabajadores', [TrabajadorController::class, 'store'])->name('trabajadores.store');
    Route::put('/trabajadores/{trabajador}', [TrabajadorController::class, 'update'])->name('trabajadores.update');
    Route::delete('/trabajadores/{trabajador}', [TrabajadorController::class, 'destroy'])->name('trabajadores.destroy');

    // Rutas CRUD
    Route::get('/rutas', [RutaController::class, 'index'])->name('rutas.index');
    Route::post('/rutas', [RutaController::class, 'store'])->name('rutas.store');
    Route::put('/rutas/{ruta}', [RutaController::class, 'update'])->name('rutas.update');
    Route::delete('/rutas/{ruta}', [RutaController::class, 'destroy'])->name('rutas.destroy');

    // Flota / Choferes CRUD
    Route::get('/flota', [FlotaController::class, 'index'])->name('flota.index');
    Route::post('/flota/vehiculos', [FlotaController::class, 'storeVehiculo'])->name('flota.vehiculos.store');
    Route::put('/flota/vehiculos/{vehiculo}', [FlotaController::class, 'updateVehiculo'])->name('flota.vehiculos.update');
    Route::delete('/flota/vehiculos/{vehiculo}', [FlotaController::class, 'destroyVehiculo'])->name('flota.vehiculos.destroy');

    Route::post('/flota/conductores', [FlotaController::class, 'storeConductor'])->name('flota.conductores.store');
    Route::put('/flota/conductores/{conductor}', [FlotaController::class, 'updateConductor'])->name('flota.conductores.update');
    Route::delete('/flota/conductores/{conductor}', [FlotaController::class, 'destroyConductor'])->name('flota.conductores.destroy');

    // Manifiestos CRUD
    Route::get('/manifiestos', [ManifiestoController::class, 'index'])->name('manifiestos.index');
    Route::post('/manifiestos', [ManifiestoController::class, 'store'])->name('manifiestos.store');
    Route::put('/manifiestos/{manifiesto}/estado', [ManifiestoController::class, 'updateEstado'])->name('manifiestos.estado');
    Route::delete('/manifiestos/{manifiesto}', [ManifiestoController::class, 'destroy'])->name('manifiestos.destroy');
});