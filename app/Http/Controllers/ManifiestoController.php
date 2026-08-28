<?php

namespace App\Http\Controllers;

use App\Models\Manifiesto;
use App\Models\ManifiestoDetalle;
use App\Models\Ruta;
use App\Models\Vehiculo;
use App\Models\Conductor;
use App\Models\Trabajador;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ManifiestoController extends Controller
{
    public function index()
    {
        return Inertia::render('Manifiestos/Index', [
            'manifiestos' => Manifiesto::with(['ruta', 'vehiculo', 'conductor.trabajador', 'creador', 'detalles.trabajador'])
                ->latest()
                ->get(),
            'rutas' => Ruta::where('activa', true)->get(),
            'vehiculos' => Vehiculo::where('activo', true)->get(),
            'conductores' => Conductor::where('activo', true)->with('trabajador')->get(),
            'trabajadores' => Trabajador::where('estado_acreditacion', 'APTO')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ruta_id' => 'required|exists:rutas,id',
            'vehiculo_id' => 'required|exists:vehiculos,id',
            'conductor_id' => 'required|exists:conductores,id',
            'fecha_salida_programada' => 'required|date',
            'pasajeros' => 'array',
            'pasajeros.*' => 'exists:trabajadores,id',
        ]);

        $codigo = 'MNF-' . date('Y') . '-' . str_pad(Manifiesto::count() + 1, 3, '0', STR_PAD_LEFT);

        $manifiesto = Manifiesto::create([
            'codigo_manifiesto' => $codigo,
            'ruta_id' => $validated['ruta_id'],
            'vehiculo_id' => $validated['vehiculo_id'],
            'conductor_id' => $validated['conductor_id'],
            'fecha_salida_programada' => $validated['fecha_salida_programada'],
            'estado' => 'CONFIRMADO',
            'codigo_qr_token' => Str::random(32),
            'creado_por' => auth()->id() ?? 1,
        ]);

        if (!empty($validated['pasajeros'])) {
            foreach ($validated['pasajeros'] as $index => $trabajadorId) {
                ManifiestoDetalle::create([
                    'manifiesto_id' => $manifiesto->id,
                    'trabajador_id' => $trabajadorId,
                    'numero_asiento' => $index + 1,
                    'estado_embarque' => 'PENDIENTE',
                ]);
            }
        }

        return back()->with('success', 'Manifiesto generado exitosamente.');
    }

    public function updateEstado(Request $request, Manifiesto $manifiesto)
    {
        $validated = $request->validate([
            'estado' => 'required|in:BORRADOR,CONFIRMADO,EN_GARITA,EN_RUTA,FINALIZADO,CANCELADO'
        ]);

        $manifiesto->update(['estado' => $validated['estado']]);

        return back()->with('success', 'Estado del manifiesto actualizado.');
    }

    public function destroy(Manifiesto $manifiesto)
    {
        $manifiesto->update(['estado' => 'CANCELADO']);
        return back()->with('success', 'Manifiesto cancelado.');
    }
}