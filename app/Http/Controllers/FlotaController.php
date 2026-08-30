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
        $magori = Empresa::whereRaw('LOWER(razon_social) LIKE ?', ['%magori%'])->first();

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
            'empresa_id' => 'nullable|exists:empresas,id',
            'placa' => 'required|string|max:10|unique:vehiculos,placa',
            'marca_modelo' => 'required|string|max:100',
            'capacidad_pasajeros' => 'required|integer|min:1',
            'soat_vencimiento' => 'nullable|date',
            'rt_vencimiento' => 'nullable|date',
        ]);

        $validated['placa'] = mb_strtoupper(trim($validated['placa']));
        $validated['marca_modelo'] = mb_strtoupper(trim($validated['marca_modelo']));
        $validated['activo'] = true;

        Vehiculo::create($validated);
        return back()->with('success', 'Vehículo registrado correctamente en la flota.');
    }

    public function updateVehiculo(Request $request, Vehiculo $vehiculo)
    {
        $validated = $request->validate([
            'empresa_id' => 'nullable|exists:empresas,id',
            'placa' => 'required|string|max:10|unique:vehiculos,placa,' . $vehiculo->id,
            'marca_modelo' => 'required|string|max:100',
            'capacidad_pasajeros' => 'required|integer|min:1',
            'soat_vencimiento' => 'nullable|date',
            'rt_vencimiento' => 'nullable|date',
        ]);

        $validated['placa'] = mb_strtoupper(trim($validated['placa']));
        $validated['marca_modelo'] = mb_strtoupper(trim($validated['marca_modelo']));

        $vehiculo->update($validated);
        return back()->with('success', 'Vehículo actualizado correctamente.');
    }

    public function destroyVehiculo(Vehiculo $vehiculo)
    {
        $vehiculo->update(['activo' => !$vehiculo->activo]);
        return back()->with('success', 'Estado del vehículo actualizado.');
    }

    public function storeConductor(Request $request)
    {
        $validated = $request->validate([
            'dni' => 'required|string|max:15|unique:conductores,dni',
            'nombres' => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'required|string|max:100',
            'fecha_nacimiento' => 'nullable|date',
            'numero_licencia' => 'required|string|max:20|unique:conductores,numero_licencia',
            'categoria_licencia' => 'required|string|in:A-I,A-IIa,A-IIb,A-IIIa,A-IIIb,A-IIIc',
            'rol_conductor' => 'required|string|in:CONDUCTOR,COPILOTO,AMBOS',
            'brevete_interno_vencimiento' => 'nullable|date',
        ]);

        $validated['dni'] = trim($validated['dni']);
        $validated['nombres'] = mb_strtoupper(trim($validated['nombres']));
        $validated['apellido_paterno'] = mb_strtoupper(trim($validated['apellido_paterno']));
        $validated['apellido_materno'] = mb_strtoupper(trim($validated['apellido_materno']));
        $validated['numero_licencia'] = mb_strtoupper(trim($validated['numero_licencia']));
        $validated['activo'] = true;

        // Auto link or create associated Trabajador record for Magori
        $magori = Empresa::whereRaw('LOWER(razon_social) LIKE ?', ['%magori%'])->first();
        $empresaId = $magori ? $magori->id : (Empresa::first()->id ?? 1);

        $apellidosCombined = trim("{$validated['apellido_paterno']} {$validated['apellido_materno']}");

        $trabajador = Trabajador::where('dni', $validated['dni'])->first();
        if (!$trabajador) {
            $trabajador = Trabajador::create([
                'dni' => $validated['dni'],
                'nombres' => $validated['nombres'],
                'apellido_paterno' => $validated['apellido_paterno'],
                'apellido_materno' => $validated['apellido_materno'],
                'apellidos' => $apellidosCombined,
                'empresa_id' => $empresaId,
                'area' => 'OPERACIONES',
                'cargo' => 'CONDUCTOR PROFESIONAL',
                'grupo_sanguineo' => 'O+',
                'estado_acreditacion' => 'APTO',
                'estado' => 1,
            ]);
        } else {
            $trabajador->update([
                'nombres' => $validated['nombres'],
                'apellido_paterno' => $validated['apellido_paterno'],
                'apellido_materno' => $validated['apellido_materno'],
                'apellidos' => $apellidosCombined,
            ]);
        }

        $validated['trabajador_id'] = $trabajador->id;

        Conductor::create($validated);
        return back()->with('success', 'Conductor registrado correctamente.');
    }

    public function updateConductor(Request $request, Conductor $conductor)
    {
        $validated = $request->validate([
            'dni' => 'required|string|max:15|unique:conductores,dni,' . $conductor->id,
            'nombres' => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'required|string|max:100',
            'fecha_nacimiento' => 'nullable|date',
            'numero_licencia' => 'required|string|max:20|unique:conductores,numero_licencia,' . $conductor->id,
            'categoria_licencia' => 'required|string|in:A-I,A-IIa,A-IIb,A-IIIa,A-IIIb,A-IIIc',
            'rol_conductor' => 'required|string|in:CONDUCTOR,COPILOTO,AMBOS',
            'brevete_interno_vencimiento' => 'nullable|date',
        ]);

        $validated['dni'] = trim($validated['dni']);
        $validated['nombres'] = mb_strtoupper(trim($validated['nombres']));
        $validated['apellido_paterno'] = mb_strtoupper(trim($validated['apellido_paterno']));
        $validated['apellido_materno'] = mb_strtoupper(trim($validated['apellido_materno']));
        $validated['numero_licencia'] = mb_strtoupper(trim($validated['numero_licencia']));

        if ($conductor->trabajador) {
            $apellidosCombined = trim("{$validated['apellido_paterno']} {$validated['apellido_materno']}");
            $conductor->trabajador->update([
                'dni' => $validated['dni'],
                'nombres' => $validated['nombres'],
                'apellido_paterno' => $validated['apellido_paterno'],
                'apellido_materno' => $validated['apellido_materno'],
                'apellidos' => $apellidosCombined,
            ]);
        }

        $conductor->update($validated);
        return back()->with('success', 'Conductor actualizado correctamente.');
    }

    public function destroyConductor(Conductor $conductor)
    {
        $conductor->update(['activo' => !$conductor->activo]);
        return back()->with('success', 'Estado del conductor actualizado.');
    }
}