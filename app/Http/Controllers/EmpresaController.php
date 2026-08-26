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

        Empresa::create($validated);
        return back()->with('success', 'Empresa registrada correctamente.');
    }
}