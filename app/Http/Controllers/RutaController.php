<?php

namespace App\Http\Controllers;

use App\Models\Ruta;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RutaController extends Controller
{
    public function index()
    {
        return Inertia::render('Rutas/Index', [
            'rutas' => Ruta::latest()->get()
        ]);
    }

    public function getPuntosApi()
    {
        $puntos = Ruta::where('activa', true)
            ->pluck('origen')
            ->map(function($item) {
                return mb_strtoupper(trim($item));
            })
            ->unique()
            ->values();

        return response()->json([
            'success' => true,
            'puntos' => $puntos
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'origen' => 'required|string|max:100',
            'departamento' => 'nullable|string|max:100',
            'destino' => 'nullable|string|max:100',
            'duracion_estimada_minutos' => 'nullable|integer|min:1',
            'distancia_km' => 'nullable|integer|min:0',
            'observaciones' => 'nullable|string|max:500',
        ], [
            'origen.required' => 'El Punto / Localidad es obligatorio.',
        ]);

        $validated['origen'] = mb_strtoupper(trim($validated['origen']));
        if (!empty($validated['departamento'])) {
            $validated['departamento'] = mb_strtoupper(trim($validated['departamento']));
        }
        if (empty($validated['destino'])) {
            $validated['destino'] = $validated['origen'];
        } else {
            $validated['destino'] = mb_strtoupper(trim($validated['destino']));
        }

        // Check unique route origin/destino combination
        $exists = Ruta::where('origen', $validated['origen'])->where('destino', $validated['destino'])->exists();
        if ($exists) {
            return back()->withErrors(['origen' => 'Este Punto / Localidad de traslado ya se encuentra registrado.']);
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
            'departamento' => 'nullable|string|max:100',
            'destino' => 'nullable|string|max:100',
            'duracion_estimada_minutos' => 'nullable|integer|min:1',
            'distancia_km' => 'nullable|integer|min:0',
            'observaciones' => 'nullable|string|max:500',
        ], [
            'origen.required' => 'El Punto / Localidad es obligatorio.',
        ]);

        $validated['origen'] = mb_strtoupper(trim($validated['origen']));
        if (!empty($validated['departamento'])) {
            $validated['departamento'] = mb_strtoupper(trim($validated['departamento']));
        }
        if (empty($validated['destino'])) {
            $validated['destino'] = $validated['origen'];
        } else {
            $validated['destino'] = mb_strtoupper(trim($validated['destino']));
        }

        // Check unique route origin/destino combination for other IDs
        $exists = Ruta::where('origen', $validated['origen'])
            ->where('destino', $validated['destino'])
            ->where('id', '!=', $ruta->id)
            ->exists();
        if ($exists) {
            return back()->withErrors(['origen' => 'Este Punto / Localidad de traslado ya se encuentra registrado.']);
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