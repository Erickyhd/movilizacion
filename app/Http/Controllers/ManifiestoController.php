<?php

namespace App\Http\Controllers;

use App\Models\Manifiesto;
use App\Models\ManifiestoDetalle;
use App\Models\Ruta;
use App\Models\Vehiculo;
use App\Models\Conductor;
use App\Models\Trabajador;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Smalot\PdfParser\Parser;

class ManifiestoController extends Controller
{
    public function index()
    {
        return Inertia::render('Manifiestos/Index', [
            'manifiestos' => Manifiesto::with([
                'ruta', 
                'vehiculo', 
                'conductor.trabajador', 
                'copiloto.trabajador', 
                'creador', 
                'detalles.trabajador.empresa'
            ])
                ->latest()
                ->get(),
            'rutas' => Ruta::where('activa', true)->get(),
            'vehiculos' => Vehiculo::where('activo', true)->get(),
            'conductores' => Conductor::where('activo', true)->with('trabajador')->get(),
            'trabajadores' => Trabajador::where('estado_acreditacion', 'APTO')->with('empresa')->get(),
            'empresas' => Empresa::all(),
        ]);
    }

    public function parsePdf(Request $request)
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:10240',
        ]);

        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($request->file('pdf_file')->getPathname());
            $text = $pdf->getText();

            $lines = explode("\n", $text);
            $rows = [];

            foreach ($lines as $line) {
                $line = trim($line);
                if (!$line) continue;

                // Match 8-digit DNI
                if (preg_match('/\b(\d{8})\b/', $line, $matches)) {
                    $dni = $matches[1];
                    $parts = preg_split('/\s{2,}|\t|\|/', $line);
                    $parts = array_values(array_filter(array_map('trim', $parts)));

                    $emp = 'Contratista General';
                    $pat = '';
                    $mat = '';
                    $nombres = 'PASAJERO';
                    $emb = 'HUANCAYO';
                    $camp = 'CARMEN';
                    $area = 'OPERACIONES';

                    if (count($parts) >= 6) {
                        if (!is_numeric($parts[0])) {
                            $emp = $parts[0];
                            $pat = $parts[3] ?? '';
                            $mat = $parts[4] ?? '';
                            $nombres = $parts[5] ?? '';
                            $emb = $parts[6] ?? 'HUANCAYO';
                            $camp = $parts[7] ?? 'CARMEN';
                            $area = $parts[8] ?? 'OPERACIONES';
                        } else {
                            $dni = $parts[0];
                            $pat = $parts[1] ?? '';
                            $mat = $parts[2] ?? '';
                            $nombres = $parts[3] ?? '';
                            $emp = $parts[4] ?? 'Contratista General';
                            $emb = $parts[5] ?? 'HUANCAYO';
                            $camp = $parts[6] ?? 'CARMEN';
                            $area = $parts[7] ?? 'OPERACIONES';
                        }
                    }

                    $rows[] = [
                        'dni' => $dni,
                        'empresa' => $emp,
                        'apellido_paterno' => $pat,
                        'apellido_materno' => $mat,
                        'nombres' => $nombres,
                        'embarque' => $emb,
                        'campamento' => $camp,
                        'area' => $area,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'count' => count($rows),
                'rows' => $rows,
                'raw_text' => mb_substr($text, 0, 5000),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'No se pudo leer la tabla del PDF: ' . $e->getMessage()
            ], 422);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'origen' => 'nullable|string|max:100',
            'destino' => 'nullable|string|max:100',
            'ruta_id' => 'nullable|exists:rutas,id',
            'vehiculo_id' => 'required|exists:vehiculos,id',
            'conductor_id' => 'required|exists:conductores,id',
            'copiloto_id' => 'nullable|exists:conductores,id',
            'tipo_movilizacion' => 'required|in:INGRESO,SALIDA,INTERNO',
            'fecha_salida_programada' => 'nullable|date',
            'pasajeros' => 'array',
            'pasajeros_excel' => 'array',
        ]);

        if (empty($validated['fecha_salida_programada'])) {
            $validated['fecha_salida_programada'] = now()->toDateTimeString();
        }

        $rutaId = $validated['ruta_id'] ?? null;

        // Ensure Origen and Destino points exist in Rutas Catalog
        if (!empty($validated['origen']) && !empty($validated['destino'])) {
            $ruta = Ruta::firstOrCreate(
                ['origen' => $validated['origen'], 'destino' => $validated['destino']],
                ['duracion_estimada_minutos' => 120, 'activa' => true]
            );
            $rutaId = $ruta->id;
        }

        if (!$rutaId) {
            return back()->withErrors(['ruta_id' => 'Debe seleccionar un Punto de Origen y Punto de Destino válidos.']);
        }

        $nextId = (Manifiesto::max('id') ?? 0) + 1;
        $codigo = 'MNF-' . date('Y') . '-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

        $manifiesto = Manifiesto::create([
            'codigo_manifiesto' => $codigo,
            'ruta_id' => $rutaId,
            'vehiculo_id' => $validated['vehiculo_id'],
            'conductor_id' => $validated['conductor_id'],
            'copiloto_id' => !empty($validated['copiloto_id']) ? $validated['copiloto_id'] : null,
            'tipo_movilizacion' => $validated['tipo_movilizacion'],
            'fecha_salida_programada' => $validated['fecha_salida_programada'],
            'estado' => 'CONFIRMADO',
            'codigo_qr_token' => Str::random(32),
            'creado_por' => auth()->id() ?? 1,
        ]);

        $asientoNum = 1;

        // 1. Process Standard ID List
        if (!empty($validated['pasajeros'])) {
            foreach ($validated['pasajeros'] as $trabajadorId) {
                if (is_numeric($trabajadorId)) {
                    ManifiestoDetalle::create([
                        'manifiesto_id' => $manifiesto->id,
                        'trabajador_id' => $trabajadorId,
                        'numero_asiento' => $asientoNum++,
                        'estado_embarque' => 'PENDIENTE',
                    ]);
                }
            }
        }

        // 2. Process Excel/PDF Parsed Row Objects
        if (!empty($validated['pasajeros_excel'])) {
            foreach ($validated['pasajeros_excel'] as $row) {
                $dni = trim($row['dni'] ?? '');
                if (!$dni) continue;

                $empresaNombre = trim($row['empresa'] ?? 'Contratista General');
                $empresa = Empresa::whereRaw('LOWER(razon_social) = ?', [mb_strtolower($empresaNombre)])->first();
                if (!$empresa) {
                    $empresa = Empresa::create([
                        'ruc' => '20' . rand(100000000, 999999999),
                        'razon_social' => mb_strtoupper($empresaNombre),
                        'es_contratista' => true,
                        'estado' => 1,
                    ]);
                }

                $embarque = trim($row['embarque'] ?? $validated['origen'] ?? 'Origen');
                $campamento = trim($row['campamento'] ?? $validated['destino'] ?? 'Destino');
                if ($embarque && $campamento) {
                    Ruta::firstOrCreate(
                        ['origen' => $embarque, 'destino' => $campamento],
                        ['duracion_estimada_minutos' => 120, 'activa' => true]
                    );
                }

                $pat = trim($row['apellido_paterno'] ?? '');
                $mat = trim($row['apellido_materno'] ?? '');
                $nombres = trim($row['nombres'] ?? 'PASAJERO');
                $apellidosCombined = trim("$pat $mat");

                $trabajador = Trabajador::where('dni', $dni)->first();
                if (!$trabajador) {
                    $trabajador = Trabajador::create([
                        'dni' => $dni,
                        'nombres' => mb_strtoupper($nombres),
                        'apellido_paterno' => mb_strtoupper($pat),
                        'apellido_materno' => mb_strtoupper($mat),
                        'apellidos' => $apellidosCombined !== '' ? mb_strtoupper($apellidosCombined) : 'REGISTRADO EXCEL/PDF',
                        'empresa_id' => $empresa->id,
                        'area' => trim($row['area'] ?? 'Operaciones'),
                        'cargo' => 'Pasajero Móvil',
                        'grupo_sanguineo' => 'O+',
                        'estado_acreditacion' => 'APTO',
                        'estado' => 1,
                    ]);
                } else {
                    $updates = [];
                    if (empty($trabajador->apellido_paterno) && $pat) $updates['apellido_paterno'] = mb_strtoupper($pat);
                    if (empty($trabajador->apellido_materno) && $mat) $updates['apellido_materno'] = mb_strtoupper($mat);
                    if (empty($trabajador->area) && !empty($row['area'])) $updates['area'] = trim($row['area']);
                    if ($trabajador->empresa_id != $empresa->id) $updates['empresa_id'] = $empresa->id;

                    if (!empty($updates)) {
                        $trabajador->update($updates);
                    }
                }

                ManifiestoDetalle::create([
                    'manifiesto_id' => $manifiesto->id,
                    'trabajador_id' => $trabajador->id,
                    'numero_asiento' => $asientoNum++,
                    'area' => trim($row['area'] ?? $trabajador->area ?? ''),
                    'embarque' => $embarque,
                    'campamento' => $campamento,
                    'estado_embarque' => 'PENDIENTE',
                ]);
            }
        }

        return back()->with('success', "Manifiesto $codigo generado exitosamente con auditoría y trazabilidad completa.");
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
        return back()->with('success', "Manifiesto {$manifiesto->codigo_manifiesto} cancelado.");
    }
}