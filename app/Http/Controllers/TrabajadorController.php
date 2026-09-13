<?php

namespace App\Http\Controllers;

use App\Models\Trabajador;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TrabajadorController extends Controller
{
    public function index()
    {
        return Inertia::render('Trabajadores/Index', [
            'trabajadores' => Trabajador::with(['empresa', 'documentos'])->latest()->get(),
            'empresas' => Empresa::where('estado', 1)->get(),
        ]);
    }

    public function store(Request $request)
    {
        try {
            if (!auth()->user()->hasWritePermission('trabajadores')) {
                return back()->with('error', 'Acceso denegado: Su cuenta no tiene permisos de escritura en el módulo de Trabajadores.');
            }

            $validated = $request->validate([
                'empresa_id' => 'required|exists:empresas,id',
                'dni' => 'required|string|max:15|unique:trabajadores,dni',
                'nombres' => 'required|string|max:100',
                'apellido_paterno' => 'required|string|max:100',
                'apellido_materno' => 'required|string|max:100',
                'area' => 'required|string|max:100',
                'cargo' => 'nullable|string|max:100',
                'telefono_emergencia' => 'nullable|string|max:20',
            ], [
                'dni.required' => 'El DNI del trabajador es obligatorio.',
                'dni.unique' => 'El DNI ingresado ya se encuentra registrado en el sistema.',
                'nombres.required' => 'Los nombres son obligatorios.',
                'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
                'apellido_materno.required' => 'El apellido materno es obligatorio.',
                'area.required' => 'El área de trabajo es obligatoria.',
                'empresa_id.required' => 'Debe seleccionar una empresa válida.',
                'empresa_id.exists' => 'La empresa seleccionada no existe.',
            ]);

            $validated['dni'] = trim($validated['dni']);
            $validated['nombres'] = mb_strtoupper(trim($validated['nombres']));
            $validated['apellido_paterno'] = mb_strtoupper(trim($validated['apellido_paterno']));
            $validated['apellido_materno'] = mb_strtoupper(trim($validated['apellido_materno']));
            $validated['area'] = mb_strtoupper(trim($validated['area']));
            if (!empty($validated['cargo'])) {
                $validated['cargo'] = mb_strtoupper(trim($validated['cargo']));
            }
            $validated['apellidos'] = trim("{$validated['apellido_paterno']} {$validated['apellido_materno']}");
            $validated['grupo_sanguineo'] = $request->input('grupo_sanguineo', 'O+');
            $validated['estado_acreditacion'] = $request->input('estado_acreditacion', 'APTO');
            $validated['estado'] = 1;

            Trabajador::create($validated);
            return back()->with('success', 'Trabajador registrado exitosamente.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al registrar el trabajador: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, Trabajador $trabajador)
    {
        try {
            if (!auth()->user()->hasWritePermission('trabajadores')) {
                return back()->with('error', 'Acceso denegado: Su cuenta no tiene permisos de escritura en el módulo de Trabajadores.');
            }

            $validated = $request->validate([
                'empresa_id' => 'required|exists:empresas,id',
                'dni' => ['required', 'string', 'max:15', Rule::unique('trabajadores', 'dni')->ignore($trabajador->id)],
                'nombres' => 'required|string|max:100',
                'apellido_paterno' => 'required|string|max:100',
                'apellido_materno' => 'required|string|max:100',
                'area' => 'required|string|max:100',
                'cargo' => 'nullable|string|max:100',
                'telefono_emergencia' => 'nullable|string|max:20',
            ], [
                'dni.required' => 'El DNI del trabajador es obligatorio.',
                'dni.unique' => 'El DNI ingresado ya se encuentra registrado en el sistema.',
                'nombres.required' => 'Los nombres son obligatorios.',
                'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
                'apellido_materno.required' => 'El apellido materno es obligatorio.',
                'area.required' => 'El área de trabajo es obligatoria.',
                'empresa_id.required' => 'Debe seleccionar una empresa válida.',
                'empresa_id.exists' => 'La empresa seleccionada no existe.',
            ]);

            $validated['dni'] = trim($validated['dni']);
            $validated['nombres'] = mb_strtoupper(trim($validated['nombres']));
            $validated['apellido_paterno'] = mb_strtoupper(trim($validated['apellido_paterno']));
            $validated['apellido_materno'] = mb_strtoupper(trim($validated['apellido_materno']));
            $validated['area'] = mb_strtoupper(trim($validated['area']));
            if (!empty($validated['cargo'])) {
                $validated['cargo'] = mb_strtoupper(trim($validated['cargo']));
            }
            $validated['apellidos'] = trim("{$validated['apellido_paterno']} {$validated['apellido_materno']}");

            if ($request->filled('grupo_sanguineo')) {
                $validated['grupo_sanguineo'] = $request->input('grupo_sanguineo');
            }
            if ($request->filled('estado_acreditacion')) {
                $validated['estado_acreditacion'] = $request->input('estado_acreditacion');
            }

            $trabajador->update($validated);
            return back()->with('success', 'Trabajador actualizado exitosamente.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al actualizar el trabajador: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Trabajador $trabajador)
    {
        try {
            if (!auth()->user()->hasWritePermission('trabajadores')) {
                return back()->with('error', 'Acceso denegado: Su cuenta no tiene permisos de escritura en el módulo de Trabajadores.');
            }

            $nuevoEstado = $trabajador->estado == 1 ? 0 : 1;
            $trabajador->update(['estado' => $nuevoEstado]);

            $mensaje = $nuevoEstado == 1 ? 'Trabajador activado correctamente.' : 'Trabajador desactivado correctamente.';
            return back()->with('success', $mensaje);
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al cambiar estado del trabajador: ' . $e->getMessage());
        }
    }
}
