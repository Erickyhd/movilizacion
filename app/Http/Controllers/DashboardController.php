<?php

namespace App\Http\Controllers;

use App\Models\Manifiesto;
use App\Models\Trabajador;
use App\Models\Vehiculo;
use App\Models\Empresa;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'total_trabajadores' => Trabajador::count(),
                'trabajadores_aptos' => Trabajador::where('estado_acreditacion', 'APTO')->count(),
                'manifiestos_activos' => Manifiesto::whereIn('estado', ['CONFIRMADO', 'EN_GARITA', 'EN_RUTA'])->count(),
                'vehiculos_activos' => Vehiculo::where('activo', true)->count(),
                'empresas_total' => Empresa::count(),
            ],
            'manifiestos_recientes' => Manifiesto::with(['ruta', 'vehiculo', 'conductor.trabajador'])
                ->latest()
                ->take(5)
                ->get()
        ]);
    }
}