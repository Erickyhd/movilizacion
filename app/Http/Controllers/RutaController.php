<?php

namespace App\Http\Controllers;

use App\Models\Ruta;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RutaController extends Controller
{
    public function index()
    {
        return Inertia::render('Rutas/Index', [
            'rutas' => Ruta::latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'origen' => 'required|string|max:100',
            'destino' => 'required|string|max:100',
            'duracion_estimada_minutos' => 'required|integer|min:1',
        ]);

        $validated['activa'] = true;
        Ruta::create($validated);
        return back()->with('success', 'Ruta creada exitosamente.');
    }

    public function update(Request $request, Ruta $ruta)
    {
        $validated = $request->validate([
            'origen' => 'required|string|max:100',
            'destino' => 'required|string|max:100',
            'duracion_estimada_minutos' => 'required|integer|min:1',
        ]);

        $ruta->update($validated);
        return back()->with('success', 'Ruta actualizada exitosamente.');
    }

    public function destroy(Ruta $ruta)
    {
        $nuevaActiva = !$ruta->activa;
        $ruta->update(['activa' => $nuevaActiva]);
        return back()->with('success', 'Estado de la ruta actualizado.');
    }
}