<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use App\Models\Conductor;
use App\Models\Empresa;
use App\Models\Trabajador;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FlotaController extends Controller
{
    public function index()
    {
        return Inertia::render('Flota/Index', [
            'vehiculos' => Vehiculo::with('empresa')->latest()->get(),
            'conductores' => Conductor::with('trabajador')->latest()->get(),
            'empresas' => Empresa::all(),
            'trabajadores' => Trabajador::where('estado_acreditacion', 'APTO')->get(),
        ]);
    }

    public function storeVehiculo(Request $request)
    {
        $validated = $request->validate([
            'empresa_id' => 'required|exists:empresas,id',
            'placa' => 'required|string|max:10|unique:vehiculos,placa',
            'marca_modelo' => 'required|string|max:100',
            'capacidad_pasajeros' => 'required|integer|min:1',
            'soat_vencimiento' => 'required|date',
            'rt_vencimiento' => 'required|date',
        ]);

        Vehiculo::create($validated);
        return back()->with('success', 'Vehículo registrado correctamente.');
    }

    public function storeConductor(Request $request)
    {
        $validated = $request->validate([
            'trabajador_id' => 'required|exists:trabajadores,id',
            'numero_licencia' => 'required|string|max:20|unique:conductores,numero_licencia',
            'categoria_licencia' => 'required|string|max:10',
            'brevete_interno_vencimiento' => 'required|date',
        ]);

        Conductor::create($validated);
        return back()->with('success', 'Conductor registrado correctamente.');
    }
}