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
use PhpOffice\PhpSpreadsheet\IOFactory;

class ManifiestoController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        
        // Get list of worker IDs already assigned to an active manifesto today
        $pasajerosAsignadosHoy = ManifiestoDetalle::whereHas('manifiesto', function($q) use ($today) {
            $q->where('estado', '!=', 'CANCELADO')
              ->where(function($qDate) use ($today) {
                  $qDate->whereDate('fecha_salida_programada', $today)
                        ->orWhereDate('created_at', $today);
              });
        })->pluck('trabajador_id')->unique()->values();

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
            'trabajadores' => Trabajador::where('estado_acreditacion', 'APTO')
                ->where('estado', 1)
                ->with('empresa')
                ->get(),
            'pasajeros_asignados_hoy' => $pasajerosAsignadosHoy,
        ]);
    }

    public function parsePdf(Request $request)
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf,xlsx,xls,csv,txt|max:10240',
        ]);

        try {
            $file = $request->file('pdf_file');
            $extension = strtolower($file->getClientOriginalExtension());
            $pathname = $file->getPathname();

            $extractedRows = [];

            if (in_array($extension, ['xlsx', 'xls', 'csv'])) {
                $spreadsheet = IOFactory::load($pathname);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray(null, true, true, true);

                $headerMap = [];

                foreach ($rows as $row) {
                    $rowValues = array_values(array_filter(array_map('trim', $row)));
                    if (empty($rowValues)) continue;

                    $rowString = mb_strtolower(implode(' ', $rowValues));

                    // Detect Header Row
                    if (str_contains($rowString, 'dni') || str_contains($rowString, 'empresa')) {
                        foreach ($row as $colLetter => $cellVal) {
                            $val = mb_strtolower(trim((string)$cellVal));
                            if (str_contains($val, 'empresa')) $headerMap['empresa'] = $colLetter;
                            else if (str_contains($val, 'dni')) $headerMap['dni'] = $colLetter;
                            else if (str_contains($val, 'paterno')) $headerMap['paterno'] = $colLetter;
                            else if (str_contains($val, 'materno')) $headerMap['materno'] = $colLetter;
                            else if (str_contains($val, 'nombre')) $headerMap['nombres'] = $colLetter;
                            else if (str_contains($val, 'área') || str_contains($val, 'area')) $headerMap['area'] = $colLetter;
                            else if (str_contains($val, 'embarque')) $headerMap['embarque'] = $colLetter;
                            else if (str_contains($val, 'campamento')) $headerMap['campamento'] = $colLetter;
                        }
                        continue;
                    }

                    // Extract DNI
                    $dniCell = isset($headerMap['dni']) ? trim((string)($row[$headerMap['dni']] ?? '')) : '';
                    if (!preg_match('/^\d{8}$/', $dniCell)) {
                        foreach ($row as $cell) {
                            $cellStr = trim((string)$cell);
                            if (preg_match('/^\d{8}$/', $cellStr)) {
                                $dniCell = $cellStr;
                                break;
                            }
                        }
                    }

                    if (preg_match('/^\d{8}$/', $dniCell)) {
                        $empresaVal = isset($headerMap['empresa']) ? trim((string)($row[$headerMap['empresa']] ?? '')) : '';
                        $paternoVal = isset($headerMap['paterno']) ? trim((string)($row[$headerMap['paterno']] ?? '')) : '';
                        $maternoVal = isset($headerMap['materno']) ? trim((string)($row[$headerMap['materno']] ?? '')) : '';
                        $nombresVal = isset($headerMap['nombres']) ? trim((string)($row[$headerMap['nombres']] ?? '')) : '';
                        $areaVal = isset($headerMap['area']) ? trim((string)($row[$headerMap['area']] ?? '')) : 'OPERACIONES';

                        if (!$nombresVal && !$paternoVal) {
                            $nameCell = isset($headerMap['nombres']) ? trim((string)($row[$headerMap['nombres']] ?? '')) : '';
                            $parts = explode(' ', $nameCell);
                            $paternoVal = $parts[0] ?? '';
                            $maternoVal = $parts[1] ?? '';
                            $nombresVal = implode(' ', array_slice($parts, 2)) ?: $paternoVal;
                        }

                        $extractedRows[] = [
                            'dni' => $dniCell,
                            'empresa' => $empresaVal ?: 'CONTRATISTA GENERAL',
                            'apellido_paterno' => mb_strtoupper($paternoVal ?: 'S/A'),
                            'apellido_materno' => mb_strtoupper($maternoVal ?: 'S/A'),
                            'nombres' => mb_strtoupper($nombresVal ?: 'PASAJERO'),
                            'area' => mb_strtoupper($areaVal ?: 'OPERACIONES'),
                        ];
                    }
                }
            } else {
                $parser = new Parser();
                $pdf = $parser->parseFile($pathname);
                $text = $pdf->getText();
                $lines = explode("\n", $text);

                $knownEmbarques = ['HUANCAYO', 'LIMA', 'AREQUIPA', 'HOTEL STAFF', 'PUNO', 'CUSCO', 'TACNA', 'PASCO', 'LA OROYA'];
                $knownCampamentos = ['CARMEN', 'ELOIDA', 'POTOSI', 'HOTEL STAFF', 'SAMAYWASI 1', 'MINA'];

                foreach ($lines as $line) {
                    $lineClean = trim($line);
                    if (!$lineClean) continue;

                    $dni = null;
                    $empresaNombre = 'CONTRATISTA GENERAL';
                    $rest = '';

                    if (preg_match('/^(.*?)\s+(\d{2}\/\d{2}\/\d{4})\s+(INGRESO|SALIDA|REINGRESO|SALIDA\/INGRESO)\s+(\d{8})\s+(.*)$/u', $lineClean, $matches)) {
                        $empresaNombre = trim($matches[1]);
                        $dni = $matches[4];
                        $rest = trim($matches[5]);
                    } else if (preg_match('/^(\d+)?\s*(\d{8})\s+(.*)$/u', $lineClean, $matches)) {
                        $dni = $matches[2];
                        $rest = trim($matches[3]);
                    }

                    if ($dni) {
                        $tokens = preg_split('/\s+/u', $rest);
                        $paterno = mb_strtoupper(trim($tokens[0] ?? ''));
                        $materno = mb_strtoupper(trim($tokens[1] ?? ''));
                        $middleTokens = array_slice($tokens, 2);

                        $embarqueIdx = -1;
                        foreach ($middleTokens as $idx => $token) {
                            if (in_array(strtoupper($token), $knownEmbarques)) {
                                $embarqueIdx = $idx;
                                break;
                            }
                        }

                        if ($embarqueIdx !== -1) {
                            $nombresTokens = array_slice($middleTokens, 0, $embarqueIdx);
                            $nombres = mb_strtoupper(trim(implode(' ', $nombresTokens)));
                            $afterEmbarque = array_slice($middleTokens, $embarqueIdx + 1);
                            if (!empty($afterEmbarque) && in_array(strtoupper($afterEmbarque[0]), $knownCampamentos)) {
                                $areaTokens = array_slice($afterEmbarque, 1);
                            } else {
                                $areaTokens = $afterEmbarque;
                            }
                            $area = mb_strtoupper(trim(implode(' ', $areaTokens))) ?: 'OPERACIONES';
                        } else {
                            $nombres = mb_strtoupper(trim(implode(' ', $middleTokens)));
                            $area = 'OPERACIONES';
                        }

                        $extractedRows[] = [
                            'dni' => $dni,
                            'empresa' => $empresaNombre,
                            'apellido_paterno' => $paterno ?: 'S/A',
                            'apellido_materno' => $materno ?: 'S/A',
                            'nombres' => $nombres ?: 'PASAJERO',
                            'area' => $area ?: 'OPERACIONES',
                        ];
                    }
                }
            }

            $empresasDB = Empresa::all();
            $registeredWorkers = [];
            $unregisteredWorkers = [];
            $unregisteredEmpresas = [];
            $processedDnis = [];

            foreach ($extractedRows as $r) {
                $dni = $r['dni'];
                if (in_array($dni, $processedDnis)) continue;
                $processedDnis[] = $dni;

                $empresaNombre = $r['empresa'];
                $empresaFound = $empresasDB->first(function($e) use ($empresaNombre) {
                    return strcasecmp($e->razon_social, $empresaNombre) === 0 || 
                           str_contains(strtolower($e->razon_social), strtolower($empresaNombre)) ||
                           str_contains(strtolower($empresaNombre), strtolower($e->razon_social));
                });

                $empresaId = $empresaFound ? $empresaFound->id : null;
                $empresaRazonSocial = $empresaFound ? $empresaFound->razon_social : $empresaNombre;

                if (!$empresaFound && !in_array($empresaNombre, $unregisteredEmpresas)) {
                    $unregisteredEmpresas[] = $empresaNombre;
                }

                $dbWorker = Trabajador::where('dni', $dni)->with('empresa')->first();

                if ($dbWorker) {
                    $registeredWorkers[] = [
                        'id' => $dbWorker->id,
                        'dni' => $dbWorker->dni,
                        'nombres' => $dbWorker->nombres,
                        'apellidos' => $dbWorker->apellidos,
                        'empresa_id' => $dbWorker->empresa_id,
                        'empresa_nombre' => $dbWorker->empresa ? $dbWorker->empresa->razon_social : $empresaRazonSocial,
                        'area' => $dbWorker->area ?: $r['area'],
                        'already_in_db' => true,
                    ];
                } else {
                    $unregisteredWorkers[] = [
                        'dni' => $dni,
                        'nombres' => $r['nombres'],
                        'apellido_paterno' => $r['apellido_paterno'],
                        'apellido_materno' => $r['apellido_materno'],
                        'empresa_id' => $empresaId,
                        'empresa_nombre' => $empresaRazonSocial,
                        'area' => $r['area'],
                        'already_in_db' => false,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'total_extracted' => count($processedDnis),
                'registered_count' => count($registeredWorkers),
                'unregistered_count' => count($unregisteredWorkers),
                'unregistered_empresas' => $unregisteredEmpresas,
                'registered_workers' => $registeredWorkers,
                'unregistered_workers' => $unregisteredWorkers,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al procesar el archivo: ' . $e->getMessage()
            ], 422);
        }
    }

    public function autoRegisterTrabajadores(Request $request)
    {
        $validated = $request->validate([
            'trabajadores' => 'required|array|min:1',
            'trabajadores.*.dni' => 'required|string|max:15',
            'trabajadores.*.nombres' => 'required|string|max:100',
            'trabajadores.*.apellido_paterno' => 'required|string|max:100',
            'trabajadores.*.apellido_materno' => 'required|string|max:100',
            'trabajadores.*.empresa_id' => 'nullable',
            'trabajadores.*.empresa_nombre' => 'nullable|string|max:150',
            'trabajadores.*.area' => 'nullable|string|max:100',
        ]);

        $createdWorkers = [];
        $firstCompany = Empresa::first();

        foreach ($validated['trabajadores'] as $w) {
            $existing = Trabajador::where('dni', $w['dni'])->first();
            if ($existing) {
                $createdWorkers[] = $existing->load('empresa');
                continue;
            }

            // Match or create Empresa
            $empresaId = $w['empresa_id'] ?? null;
            if (!$empresaId && !empty($w['empresa_nombre'])) {
                $empName = trim($w['empresa_nombre']);
                $empresa = Empresa::where('razon_social', 'LIKE', "%{$empName}%")->first();
                if (!$empresa) {
                    do {
                        $randomRuc = '20' . rand(100000001, 999999999);
                    } while (Empresa::where('ruc', $randomRuc)->exists());

                    $empresa = Empresa::create([
                        'ruc' => $randomRuc,
                        'razon_social' => mb_strtoupper($empName),
                        'es_contratista' => 1,
                        'estado' => 1,
                    ]);
                }
                $empresaId = $empresa->id;
            }

            if (!$empresaId) {
                $empresaId = $firstCompany ? $firstCompany->id : 1;
            }

            $paterno = mb_strtoupper(trim($w['apellido_paterno']));
            $materno = mb_strtoupper(trim($w['apellido_materno']));
            $nombres = mb_strtoupper(trim($w['nombres']));
            $apellidos = trim("$paterno $materno");
            $area = !empty($w['area']) ? mb_strtoupper(trim($w['area'])) : 'OPERACIONES';

            $newTrabajador = Trabajador::create([
                'dni' => trim($w['dni']),
                'nombres' => $nombres,
                'apellido_paterno' => $paterno,
                'apellido_materno' => $materno,
                'apellidos' => $apellidos,
                'empresa_id' => $empresaId,
                'area' => $area,
                'cargo' => 'OPERARIO',
                'grupo_sanguineo' => 'O+',
                'estado_acreditacion' => 'APTO',
                'estado' => 1,
            ]);

            $createdWorkers[] = $newTrabajador->load('empresa');
        }

        return response()->json([
            'success' => true,
            'message' => count($createdWorkers) . ' trabajador(es) e integrados exitosamente.',
            'created_workers' => $createdWorkers,
        ]);
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
            'fecha_salida_programada' => 'nullable|string',
            'pasajeros' => 'array',
            'pasajeros_excel' => 'array',
        ]);

        date_default_timezone_set('America/Lima');

        if (empty($validated['fecha_salida_programada'])) {
            $validated['fecha_salida_programada'] = now()->toDateTimeString();
        } else {
            $validated['fecha_salida_programada'] = date('Y-m-d H:i:s', strtotime($validated['fecha_salida_programada']));
        }

        $today = date('Y-m-d', strtotime($validated['fecha_salida_programada']));

        // Get worker IDs already assigned to an active manifesto on this target date
        $existingWorkersOnDate = ManifiestoDetalle::whereHas('manifiesto', function($q) use ($today) {
            $q->where('estado', '!=', 'CANCELADO')
              ->whereDate('fecha_salida_programada', $today);
        })->pluck('trabajador_id')->toArray();

        $rutaId = $validated['ruta_id'] ?? null;

        if (!empty($validated['origen']) && !empty($validated['destino'])) {
            $ruta = Ruta::firstOrCreate(
                ['origen' => $validated['origen'], 'destino' => $validated['destino']],
                ['duracion_estimada_minutos' => 120, 'activa' => true]
            );
            $rutaId = $ruta->id;
        }

        if (!$rutaId) {
            $rutaId = Ruta::first()->id ?? 1;
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
            'estado' => 'REGISTRADO',
            'codigo_qr_token' => Str::random(32),
            'creado_por' => auth()->id() ?? 1,
        ]);

        $asientoNum = 1;
        $skippedCount = 0;

        // 1. Process Standard ID List
        if (!empty($validated['pasajeros'])) {
            foreach ($validated['pasajeros'] as $trabajadorId) {
                if (is_numeric($trabajadorId)) {
                    if (in_array($trabajadorId, $existingWorkersOnDate)) {
                        $skippedCount++;
                        continue;
                    }

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

                if (in_array($trabajador->id, $existingWorkersOnDate)) {
                    $skippedCount++;
                    continue;
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

        $msg = "Manifiesto $codigo registrado exitosamente.";
        if ($skippedCount > 0) {
            $msg .= " Nota: Se omitieron $skippedCount pasajeros que ya estaban asignados a un manifiesto el día de hoy.";
        }

        return back()->with('success', $msg);
    }

    public function addPasajeros(Request $request, Manifiesto $manifiesto)
    {
        if ($manifiesto->estado !== 'REGISTRADO') {
            return back()->withErrors(['error' => 'No se puede agregar pasajeros a un manifiesto que ya ha sido CONFIRMADO o CANCELADO.']);
        }

        $validated = $request->validate([
            'trabajador_ids' => 'required|array',
            'trabajador_ids.*' => 'exists:trabajadores,id'
        ]);

        $today = date('Y-m-d', strtotime($manifiesto->fecha_salida_programada));

        $existingWorkersOnDate = ManifiestoDetalle::whereHas('manifiesto', function($q) use ($today) {
            $q->where('estado', '!=', 'CANCELADO')
              ->whereDate('fecha_salida_programada', $today);
        })->pluck('trabajador_id')->toArray();

        $asientoNum = (ManifiestoDetalle::where('manifiesto_id', $manifiesto->id)->max('numero_asiento') ?? 0) + 1;
        $added = 0;

        foreach ($validated['trabajador_ids'] as $trabajadorId) {
            if (in_array($trabajadorId, $existingWorkersOnDate)) {
                continue;
            }

            ManifiestoDetalle::create([
                'manifiesto_id' => $manifiesto->id,
                'trabajador_id' => $trabajadorId,
                'numero_asiento' => $asientoNum++,
                'estado_embarque' => 'PENDIENTE',
            ]);
            $added++;
        }

        return back()->with('success', "Se agregaron $added nuevos pasajeros al manifiesto {$manifiesto->codigo_manifiesto}.");
    }

    public function removePasajero(Manifiesto $manifiesto, ManifiestoDetalle $detalle)
    {
        if ($manifiesto->estado !== 'REGISTRADO') {
            return back()->withErrors(['error' => 'No se puede quitar pasajeros de un manifiesto que ya ha sido CONFIRMADO o CANCELADO.']);
        }

        if ($detalle->manifiesto_id == $manifiesto->id) {
            $detalle->delete();
            return back()->with('success', 'Pasajero removido del manifiesto.');
        }

        return back()->withErrors(['error' => 'No se pudo remover el pasajero.']);
    }

    public function updateEstado(Request $request, Manifiesto $manifiesto)
    {
        $validated = $request->validate([
            'estado' => 'required|in:REGISTRADO,CONFIRMADO,CANCELADO'
        ]);

        $manifiesto->update(['estado' => $validated['estado']]);

        return back()->with('success', 'Estado del manifiesto actualizado.');
    }

    public function destroy(Manifiesto $manifiesto)
    {
        $manifiesto->update(['estado' => 'CANCELADO']);
        return back()->with('success', "Manifiesto {$manifiesto->codigo_manifiesto} cancelado.");
    }

    public function imprimirOficial(Manifiesto $manifiesto)
    {
        $manifiesto->load([
            'ruta',
            'vehiculo',
            'conductor.trabajador',
            'copiloto.trabajador',
            'detalles.trabajador.empresa'
        ]);

        $capacidad = $manifiesto->vehiculo ? $manifiesto->vehiculo->capacidad_pasajeros : 46;
        if ($capacidad < 46) $capacidad = 46;

        return view('pdf.manifiesto_oficial', [
            'manifiesto' => $manifiesto,
            'totalFilas' => $capacidad
        ]);
    }

    public function pdfPreimpreso(Manifiesto $manifiesto)
    {
        date_default_timezone_set('America/Lima');

        $manifiesto->load([
            'ruta',
            'vehiculo',
            'conductor.trabajador',
            'copiloto.trabajador',
            'detalles.trabajador.empresa'
        ]);

        $ahora = \Carbon\Carbon::now('America/Lima');
        $fechaSalida = $ahora->format('d/m/Y');
        $horaSalida = $ahora->format('H:i');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.manifiesto_preimpreso', [
            'manifiesto' => $manifiesto,
            'fechaSalida' => $fechaSalida,
            'horaSalida' => $horaSalida,
        ])->setPaper('legal', 'portrait');

        $filename = 'Manifiesto_' . $manifiesto->codigo_manifiesto . '_' . $ahora->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}