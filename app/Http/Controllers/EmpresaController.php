<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmpresaController extends Controller
{
    public function index()
    {
        return Inertia::render('Empresas/Index', [
            'empresas' => Empresa::withCount('trabajadores')->latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ruc' => 'required|string|size:11|unique:empresas,ruc',
            'razon_social' => 'required|string|max:150',
            'es_contratista' => 'boolean',
        ]);

        $validated['estado'] = 1;
        Empresa::create($validated);
        return back()->with('success', 'Empresa registrada correctamente.');
    }

    public function update(Request $request, Empresa $empresa)
    {
        $validated = $request->validate([
            'ruc' => 'required|string|size:11|unique:empresas,ruc,' . $empresa->id,
            'razon_social' => 'required|string|max:150',
            'es_contratista' => 'boolean',
        ]);

        $empresa->update($validated);
        return back()->with('success', 'Empresa actualizada correctamente.');
    }

    public function destroy(Empresa $empresa)
    {
        $nuevoEstado = $empresa->estado == 1 ? 0 : 1;
        $empresa->update(['estado' => $nuevoEstado]);
        return back()->with('success', 'Estado de la empresa actualizado.');
    }
}