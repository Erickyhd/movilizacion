<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class EmpresaController extends Controller
{
    public function index()
    {
        return Inertia::render('Empresas/Index', [
            'empresas' => Empresa::latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'ruc' => 'nullable|string|max:20|unique:empresas,ruc',
                'razon_social' => 'required|string|max:150|unique:empresas,razon_social',
                'observaciones' => 'nullable|string|max:500',
            ], [
                'ruc.unique' => 'El RUC ingresado ya se encuentra registrado para otra empresa.',
                'razon_social.unique' => 'La Razón Social ingresada ya se encuentra registrada.',
                'razon_social.required' => 'La Razón Social de la empresa es obligatoria.',
            ]);

            $validated['razon_social'] = mb_strtoupper(trim($validated['razon_social']));
            if (!empty($validated['ruc'])) {
                $validated['ruc'] = trim($validated['ruc']);
            }
            $validated['es_contratista'] = true;
            $validated['estado'] = 1;

            Empresa::create($validated);

            return back()->with('success', 'Empresa registrada exitosamente.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al registrar la empresa: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, Empresa $empresa)
    {
        try {
            $validated = $request->validate([
                'ruc' => ['nullable', 'string', 'max:20', Rule::unique('empresas', 'ruc')->ignore($empresa->id)],
                'razon_social' => ['required', 'string', 'max:150', Rule::unique('empresas', 'razon_social')->ignore($empresa->id)],
                'observaciones' => 'nullable|string|max:500',
            ], [
                'ruc.unique' => 'El RUC ingresado ya se encuentra registrado para otra empresa.',
                'razon_social.unique' => 'La Razón Social ingresada ya se encuentra registrada.',
                'razon_social.required' => 'La Razón Social de la empresa es obligatoria.',
            ]);

            $validated['razon_social'] = mb_strtoupper(trim($validated['razon_social']));
            if (!empty($validated['ruc'])) {
                $validated['ruc'] = trim($validated['ruc']);
            }

            $empresa->update($validated);

            return back()->with('success', 'Empresa actualizada exitosamente.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al actualizar la empresa: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Empresa $empresa)
    {
        try {
            $nuevoEstado = $empresa->estado == 1 ? 0 : 1;
            $empresa->update(['estado' => $nuevoEstado]);

            $mensaje = $nuevoEstado == 1 ? 'Empresa activada correctamente.' : 'Empresa desactivada correctamente.';
            return back()->with('success', $mensaje);
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al cambiar estado de la empresa: ' . $e->getMessage());
        }
    }
}