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
            'destino' => 'nullable|string|max:100',
            'duracion_estimada_minutos' => 'nullable|integer|min:1',
        ]);

        if (empty($validated['destino'])) {
            $validated['destino'] = $validated['origen'];
        }
        if (empty($validated['duracion_estimada_minutos'])) {
            $validated['duracion_estimada_minutos'] = 120;
        }

        $validated['activa'] = true;
        Ruta::create($validated);
        return back()->with('success', 'Punto / Localidad de traslado registrado exitosamente.');
    }

    public function update(Request $request, Ruta $ruta)
    {
        $validated = $request->validate([
            'origen' => 'required|string|max:100',
            'destino' => 'nullable|string|max:100',
            'duracion_estimada_minutos' => 'nullable|integer|min:1',
        ]);

        if (empty($validated['destino'])) {
            $validated['destino'] = $validated['origen'];
        }
        if (empty($validated['duracion_estimada_minutos'])) {
            $validated['duracion_estimada_minutos'] = 120;
        }

        $ruta->update($validated);
        return back()->with('success', 'Punto / Localidad actualizado exitosamente.');
    }

    public function destroy(Ruta $ruta)
    {
        $nuevaActiva = !$ruta->activa;
        $ruta->update(['activa' => $nuevaActiva]);
        return back()->with('success', 'Estado del punto actualizado.');
    }
}