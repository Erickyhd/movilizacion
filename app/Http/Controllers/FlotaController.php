<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use App\Models\Conductor;
use App\Models\Empresa;
use App\Models\Trabajador;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class FlotaController extends Controller
{
    public function index()
    {
        return Inertia::render('Flota/Index', [
            'vehiculos' => Vehiculo::with('empresa')->latest()->get(),
            'conductores' => Conductor::with('trabajador')->latest()->get(),
            'empresas' => Empresa::where('estado', 1)->get(),
            'trabajadores' => Trabajador::where('estado_acreditacion', 'APTO')->where('estado', 1)->get(),
        ]);
    }

    public function storeVehiculo(Request $request)
    {
        try {
            $validated = $request->validate([
                'empresa_id' => 'nullable|exists:empresas,id',
                'placa' => 'required|string|max:10|unique:vehiculos,placa',
                'marca_modelo' => 'required|string|max:100',
                'capacidad_pasajeros' => 'required|integer|min:1|max:100',
                'soat_vencimiento' => 'nullable|date',
                'rt_vencimiento' => 'nullable|date',
            ], [
                'placa.required' => 'La placa del vehículo es obligatoria.',
                'placa.unique' => 'La placa ingresada ya se encuentra registrada en la flota.',
                'marca_modelo.required' => 'La marca / modelo del vehículo es obligatoria.',
                'capacidad_pasajeros.required' => 'La capacidad de pasajeros es obligatoria.',
                'capacidad_pasajeros.min' => 'La capacidad debe ser de al menos 1 pasajero.',
                'capacidad_pasajeros.max' => 'La capacidad máxima no puede exceder los 100 pasajeros.',
                'soat_vencimiento.date' => 'La fecha de vencimiento de SOAT no es válida.',
                'rt_vencimiento.date' => 'La fecha de vencimiento de Revisión Técnica no es válida.',
            ]);

            $validated['placa'] = mb_strtoupper(trim($validated['placa']));
            $validated['marca_modelo'] = mb_strtoupper(trim($validated['marca_modelo']));
            $validated['activo'] = true;

            Vehiculo::create($validated);
            return back()->with('success', 'VehÃ­culo registrado correctamente en la flota.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al registrar el vehículo: ' . $e->getMessage())->withInput();
        }
    }

    public function updateVehiculo(Request $request, Vehiculo $vehiculo)
    {
        try {
            $validated = $request->validate([
                'empresa_id' => 'nullable|exists:empresas,id',
                'placa' => ['required', 'string', 'max:10', Rule::unique('vehiculos', 'placa')->ignore($vehiculo->id)],
                'marca_modelo' => 'required|string|max:100',
                'capacidad_pasajeros' => 'required|integer|min:1|max:100',
                'soat_vencimiento' => 'nullable|date',
                'rt_vencimiento' => 'nullable|date',
            ], [
                'placa.required' => 'La placa del vehículo es obligatoria.',
                'placa.unique' => 'La placa ingresada ya se encuentra registrada en la flota.',
                'marca_modelo.required' => 'La marca / modelo del vehículo es obligatoria.',
                'capacidad_pasajeros.required' => 'La capacidad de pasajeros es obligatoria.',
                'capacidad_pasajeros.min' => 'La capacidad debe ser de al menos 1 pasajero.',
                'capacidad_pasajeros.max' => 'La capacidad máxima no puede exceder los 100 pasajeros.',
            ]);

            $validated['placa'] = mb_strtoupper(trim($validated['placa']));
            $validated['marca_modelo'] = mb_strtoupper(trim($validated['marca_modelo']));

            $vehiculo->update($validated);
            return back()->with('success', 'VehÃ­culo actualizado correctamente.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al actualizar el vehículo: ' . $e->getMessage())->withInput();
        }
    }

    public function destroyVehiculo(Vehiculo $vehiculo)
    {
        try {
            $nuevoActivo = !$vehiculo->activo;
            $vehiculo->update(['activo' => $nuevoActivo]);

            $mensaje = $nuevoActivo ? 'VehÃ­culo activado en la flota.' : 'VehÃ­culo desactivado de la flota.';
            return back()->with('success', $mensaje);
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al cambiar estado del vehículo: ' . $e->getMessage());
        }
    }

    public function storeConductor(Request $request)
    {
        try {
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
                'dni.required' => 'El DNI del conductor es obligatorio.',
                'dni.unique' => 'El DNI ingresado ya se encuentra registrado para otro conductor.',
                'numero_licencia.required' => 'El número de licencia es obligatorio.',
                'numero_licencia.unique' => 'El número de licencia ingresado ya se encuentra registrado.',
                'nombres.required' => 'Los nombres son obligatorios.',
                'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
                'apellido_materno.required' => 'El apellido materno es obligatorio.',
                'categoria_licencia.required' => 'Seleccione una categoría de licencia válida.',
                'rol_conductor.required' => 'Seleccione el rol del conductor (Conductor / Copiloto / Ambos).',
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

            // Vincular con trabajador si existe
            $trabajador = Trabajador::where('dni', $validated['dni'])->first();
            if ($trabajador) {
                $validated['trabajador_id'] = $trabajador->id;
            }

            Conductor::create($validated);
            return back()->with('success', 'Conductor registrado correctamente.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al registrar el conductor: ' . $e->getMessage())->withInput();
        }
    }

    public function updateConductor(Request $request, Conductor $conductor)
    {
        try {
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
                'dni.required' => 'El DNI del conductor es obligatorio.',
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
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al actualizar el conductor: ' . $e->getMessage())->withInput();
        }
    }

    public function destroyConductor(Conductor $conductor)
    {
        try {
            $nuevoActivo = !$conductor->activo;
            $conductor->update(['activo' => $nuevoActivo]);

            $mensaje = $nuevoActivo ? 'Conductor activado correctamente.' : 'Conductor desactivado correctamente.';
            return back()->with('success', $mensaje);
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al cambiar estado del conductor: ' . $e->getMessage());
        }
    }
}