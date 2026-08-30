<?php

namespace App\Http\Controllers;

use App\Models\Trabajador;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TrabajadorController extends Controller
{
    public function index()
    {
        return Inertia::render('Trabajadores/Index', [
            'trabajadores' => Trabajador::with(['empresa', 'documentos'])->latest()->get(),
            'empresas' => Empresa::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'empresa_id' => 'required|exists:empresas,id',
            'dni' => 'required|string|max:15|unique:trabajadores,dni',
            'nombres' => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'required|string|max:100',
            'area' => 'required|string|max:100',
            'cargo' => 'nullable|string|max:100',
            'telefono_emergencia' => 'nullable|string|max:20',
        ]);

        $validated['nombres'] = mb_strtoupper(trim($validated['nombres']));
        $validated['apellido_paterno'] = mb_strtoupper(trim($validated['apellido_paterno']));
        $validated['apellido_materno'] = mb_strtoupper(trim($validated['apellido_materno']));
        $validated['area'] = mb_strtoupper(trim($validated['area']));
        $validated['apellidos'] = trim("{$validated['apellido_paterno']} {$validated['apellido_materno']}");
        $validated['grupo_sanguineo'] = $request->input('grupo_sanguineo', 'O+');
        $validated['estado_acreditacion'] = $request->input('estado_acreditacion', 'APTO');
        $validated['estado'] = 1;

        Trabajador::create($validated);
        return back()->with('success', 'Trabajador registrado exitosamente.');
    }

    public function update(Request $request, Trabajador $trabajador)
    {
        $validated = $request->validate([
            'empresa_id' => 'required|exists:empresas,id',
            'dni' => 'required|string|max:15|unique:trabajadores,dni,' . $trabajador->id,
            'nombres' => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'required|string|max:100',
            'area' => 'required|string|max:100',
            'cargo' => 'nullable|string|max:100',
            'telefono_emergencia' => 'nullable|string|max:20',
        ]);

        $validated['nombres'] = mb_strtoupper(trim($validated['nombres']));
        $validated['apellido_paterno'] = mb_strtoupper(trim($validated['apellido_paterno']));
        $validated['apellido_materno'] = mb_strtoupper(trim($validated['apellido_materno']));
        $validated['area'] = mb_strtoupper(trim($validated['area']));
        $validated['apellidos'] = trim("{$validated['apellido_paterno']} {$validated['apellido_materno']}");

        if ($request->filled('grupo_sanguineo')) {
            $validated['grupo_sanguineo'] = $request->input('grupo_sanguineo');
        }
        if ($request->filled('estado_acreditacion')) {
            $validated['estado_acreditacion'] = $request->input('estado_acreditacion');
        }

        $trabajador->update($validated);
        return back()->with('success', 'Trabajador actualizado exitosamente.');
    }

    public function destroy(Trabajador $trabajador)
    {
        $nuevoEstado = $trabajador->estado == 1 ? 0 : 1;
        $trabajador->update(['estado' => $nuevoEstado]);
        return back()->with('success', 'Estado del trabajador actualizado.');
    }
}