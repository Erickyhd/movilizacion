<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use App\Models\Conductor;
use App\Models\Empresa;
use App\Models\Trabajador;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
            'empresa_id' => 'nullable|exists:empresas,id',
            'placa' => 'required|string|max:10|unique:vehiculos,placa',
            'marca_modelo' => 'required|string|max:100',
            'capacidad_pasajeros' => 'required|integer|min:1',
            'soat_vencimiento' => 'nullable|date',
            'rt_vencimiento' => 'nullable|date',
        ], [
            'placa.required' => 'La placa del vehículo es obligatoria.',
            'placa.unique' => 'La placa ingresada ya se encuentra registrada en la flota.',
            'marca_modelo.required' => 'La marca / modelo es obligatoria.',
            'capacidad_pasajeros.required' => 'La capacidad de pasajeros es obligatoria.',
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
            'placa' => ['required', 'string', 'max:10', Rule::unique('vehiculos', 'placa')->ignore($vehiculo->id)],
            'marca_modelo' => 'required|string|max:100',
            'capacidad_pasajeros' => 'required|integer|min:1',
            'soat_vencimiento' => 'nullable|date',
            'rt_vencimiento' => 'nullable|date',
        ], [
            'placa.required' => 'La placa del vehículo es obligatoria.',
            'placa.unique' => 'La placa ingresada ya se encuentra registrada en la flota.',
            'marca_modelo.required' => 'La marca / modelo es obligatoria.',
            'capacidad_pasajeros.required' => 'La capacidad de pasajeros es obligatoria.',
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
        ], [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.unique' => 'El DNI ingresado ya se encuentra registrado para otro conductor.',
            'numero_licencia.required' => 'El número de licencia es obligatorio.',
            'numero_licencia.unique' => 'El número de licencia ingresado ya se encuentra registrado.',
            'nombres.required' => 'Los nombres son obligatorios.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'apellido_materno.required' => 'El apellido materno es obligatorio.',
        ]);

        $validated['dni'] = trim($validated['dni']);
        $validated['nombres'] = mb_strtoupper(trim($validated['nombres']));
        $validated['apellido_paterno'] = mb_strtoupper(trim($validated['apellido_paterno']));
        $validated['apellido_materno'] = mb_strtoupper(trim($validated['apellido_materno']));
        $validated['numero_licencia'] = mb_strtoupper(trim($validated['numero_licencia']));
        $validated['activo'] = true;

        if (empty($validated['brevete_interno_vencimiento'])) {
            $validated['brevete_interno_vencimiento'] = null;
        }

        // Link with existing trabajador_id if present for reference
        $trabajador = Trabajador::where('dni', $validated['dni'])->first();
        if ($trabajador) {
            $validated['trabajador_id'] = $trabajador->id;
        }

        Conductor::create($validated);
        return back()->with('success', 'Conductor registrado correctamente.');
    }

    public function updateConductor(Request $request, Conductor $conductor)
    {
        $validated = $request->validate([
            'dni' => ['required', 'string', 'max:15', Rule::unique('conductores', 'dni')->ignore($conductor->id)],
            'nombres' => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'required|string|max:100',
            'fecha_nacimiento' => 'nullable|date',
            'numero_licencia' => ['required', 'string', 'max:20', Rule::unique('conductores', 'numero_licencia')->ignore($conductor->id)],
            'categoria_licencia' => 'required|string|in:A-I,A-IIa,A-IIb,A-IIIa,A-IIIb,A-IIIc',
            'rol_conductor' => 'required|string|in:CONDUCTOR,COPILOTO,AMBOS',
            'brevete_interno_vencimiento' => 'nullable|date',
        ], [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.unique' => 'El DNI ingresado ya se encuentra registrado para otro conductor.',
            'numero_licencia.required' => 'El número de licencia es obligatorio.',
            'numero_licencia.unique' => 'El número de licencia ingresado ya se encuentra registrado.',
            'nombres.required' => 'Los nombres son obligatorios.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'apellido_materno.required' => 'El apellido materno es obligatorio.',
        ]);

        $validated['dni'] = trim($validated['dni']);
        $validated['nombres'] = mb_strtoupper(trim($validated['nombres']));
        $validated['apellido_paterno'] = mb_strtoupper(trim($validated['apellido_paterno']));
        $validated['apellido_materno'] = mb_strtoupper(trim($validated['apellido_materno']));
        $validated['numero_licencia'] = mb_strtoupper(trim($validated['numero_licencia']));

        if (empty($validated['brevete_interno_vencimiento'])) {
            $validated['brevete_interno_vencimiento'] = null;
        }

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