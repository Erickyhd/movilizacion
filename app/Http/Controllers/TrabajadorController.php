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
            'apellidos' => 'required|string|max:100',
            'cargo' => 'nullable|string|max:100',
            'grupo_sanguineo' => 'nullable|string|max:5',
            'telefono_emergencia' => 'nullable|string|max:20',
            'estado_acreditacion' => 'required|in:APTO,OBSERVADO,BLOQUEADO',
        ]);

        Trabajador::create($validated);
        return back()->with('success', 'Trabajador registrado exitosamente.');
    }
}