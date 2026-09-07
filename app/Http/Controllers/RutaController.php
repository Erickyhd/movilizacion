<?php

namespace App\Http\Controllers;

use App\Models\Ruta;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
        try {
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
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener catálogo de puntos de traslado: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'origen' => 'required|string|max:100',
                'departamento' => 'nullable|string|max:100',
                'destino' => 'nullable|string|max:100',
                'duracion_estimada_minutos' => 'nullable|integer|min:1',
                'distancia_km' => 'nullable|integer|min:0',
                'observaciones' => 'nullable|string|max:500',
            ], [
                'origen.required' => 'El Punto / Localidad de origen es obligatorio.',
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

            // Validar que no exista una ruta idÃ©ntica
            $exists = Ruta::where('origen', $validated['origen'])
                ->where('destino', $validated['destino'])
                ->exists();
            if ($exists) {
                return back()->with('error', 'El Punto / Localidad de traslado (' . $validated['origen'] . ') ya se encuentra registrado.')
                    ->withErrors(['origen' => 'Este Punto / Localidad de traslado ya se encuentra registrado.'])
                    ->withInput();
            }

            if (empty($validated['duracion_estimada_minutos'])) {
                $validated['duracion_estimada_minutos'] = 120;
            }

            $validated['activa'] = true;
            Ruta::create($validated);

            return back()->with('success', 'Punto / Localidad de traslado registrado exitosamente.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al registrar la ruta: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, Ruta $ruta)
    {
        try {
            $validated = $request->validate([
                'origen' => 'required|string|max:100',
                'departamento' => 'nullable|string|max:100',
                'destino' => 'nullable|string|max:100',
                'duracion_estimada_minutos' => 'nullable|integer|min:1',
                'distancia_km' => 'nullable|integer|min:0',
                'observaciones' => 'nullable|string|max:500',
            ], [
                'origen.required' => 'El Punto / Localidad de origen es obligatorio.',
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

            // Validar duplicidad excluyendo el registro actual
            $exists = Ruta::where('origen', $validated['origen'])
                ->where('destino', $validated['destino'])
                ->where('id', '!=', $ruta->id)
                ->exists();
            if ($exists) {
                return back()->with('error', 'El Punto / Localidad de traslado (' . $validated['origen'] . ') ya se encuentra registrado.')
                    ->withErrors(['origen' => 'Este Punto / Localidad de traslado ya se encuentra registrado.'])
                    ->withInput();
            }

            if (empty($validated['duracion_estimada_minutos'])) {
                $validated['duracion_estimada_minutos'] = 120;
            }

            $ruta->update($validated);
            return back()->with('success', 'Punto / Localidad actualizado exitosamente.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al actualizar la ruta: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Ruta $ruta)
    {
        try {
            $nuevaActiva = !$ruta->activa;
            $ruta->update(['activa' => $nuevaActiva]);

            $mensaje = $nuevaActiva ? 'Punto de traslado activado exitosamente.' : 'Punto de traslado desactivado exitosamente.';
            return back()->with('success', $mensaje);
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al cambiar estado del punto: ' . $e->getMessage());
        }
    }
}