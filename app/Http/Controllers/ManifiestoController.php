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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Smalot\PdfParser\Parser;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ManifiestoController extends Controller
{
    public function index()
    {
        try {
            $today = now()->toDateString();
            
            // Pasajeros ya asignados hoy
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
        } catch (\Throwable $e) {
            return Inertia::render('Manifiestos/Index', [
                'manifiestos' => [],
                'rutas' => [],
                'vehiculos' => [],
                'conductores' => [],
                'trabajadores' => [],
                'pasajeros_asignados_hoy' => [],
            ])->with('error', 'Error al cargar manifiestos: ' . $e->getMessage());
        }
    }

    public function parsePdf(Request $request)
    {
        try {
            $request->validate([
                'pdf_file' => 'required|file|mimes:pdf,xlsx,xls,csv,txt|max:10240',
            ], [
                'pdf_file.required' => 'Debe seleccionar un archivo para procesar.',
                'pdf_file.mimes' => 'El formato del archivo debe ser PDF, Excel (XLSX, XLS), CSV o TXT.',
                'pdf_file.max' => 'El tamaño del archivo no puede superar los 10 MB.',
            ]);

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
                            'empresa' => $empresaNombre ?: 'CONTRATISTA GENERAL',
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

                $empresaNombre = trim($r['empresa'] ?? '');
                if (!$empresaNombre) $empresaNombre = 'CONTRATISTA GENERAL';

                $empresaFound = $this->findMatchingEmpresa($empresaNombre, $empresasDB);
                $empresaId = $empresaFound ? $empresaFound->id : null;
                $empresaRazonSocial = $empresaFound ? $empresaFound->razon_social : mb_strtoupper($empresaNombre);

                if (!$empresaFound && !in_array($empresaRazonSocial, $unregisteredEmpresas)) {
                    $unregisteredEmpresas[] = $empresaRazonSocial;
                }

                $dbWorker = Trabajador::where('dni', $dni)->with('empresa')->first();

                if ($dbWorker) {
                    $registeredWorkers[] = [
                        'id' => $dbWorker->id,
                        'dni' => $dbWorker->dni,
                        'nombres' => $dbWorker->nombres,
                        'apellidos' => $dbWorker->apellidos ?: trim("{$dbWorker->apellido_paterno} {$dbWorker->apellido_materno}"),
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

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->validator->errors()->first()
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al procesar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function autoRegisterTrabajadores(Request $request)
    {
        try {
            if (!auth()->user()->hasWritePermission('manifiestos') && !auth()->user()->hasWritePermission('trabajadores')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Acceso denegado: Su cuenta no tiene permisos de escritura.'
                ], 403);
            }
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

            DB::transaction(function () use ($validated, &$createdWorkers) {
                $empresasDB = Empresa::all();

                foreach ($validated['trabajadores'] as $w) {
                    $dni = trim($w['dni']);
                    $existing = Trabajador::where('dni', $dni)->first();
                    if ($existing) {
                        $createdWorkers[] = $existing->load('empresa');
                        continue;
                    }

                    // Match or resolve Empresa without creating duplicates
                    $empresaId = $w['empresa_id'] ?? null;
                    if ($empresaId) {
                        $empresa = $empresasDB->find($empresaId) ?: Empresa::find($empresaId);
                    } else {
                        $empName = trim($w['empresa_nombre'] ?? '');
                        $empresa = $this->resolveOrCreateEmpresa($empName, $empresasDB);
                    }

                    if (!$empresa) {
                        $firstCompany = $empresasDB->first() ?: Empresa::first();
                        $empresaId = $firstCompany ? $firstCompany->id : 1;
                    } else {
                        $empresaId = $empresa->id;
                    }

                    $paterno = mb_strtoupper(trim($w['apellido_paterno']));
                    $materno = mb_strtoupper(trim($w['apellido_materno']));
                    $nombres = mb_strtoupper(trim($w['nombres']));
                    $apellidos = trim("$paterno $materno");
                    $area = !empty($w['area']) ? mb_strtoupper(trim($w['area'])) : 'OPERACIONES';

                    $newTrabajador = Trabajador::create([
                        'dni' => $dni,
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
            });

            return response()->json([
                'success' => true,
                'message' => count($createdWorkers) . ' trabajador(es) integrados exitosamente.',
                'created_workers' => $createdWorkers,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->validator->errors()->first()
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al auto-registrar trabajadores: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            if (!auth()->user()->hasWritePermission('manifiestos')) {
                return back()->with('error', 'Acceso denegado: Su cuenta no tiene permisos de escritura en el módulo de Manifiestos.');
            }
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
            ], [
                'vehiculo_id.required' => 'Debe seleccionar un vehículo para el manifiesto.',
                'vehiculo_id.exists' => 'El vehículo seleccionado no existe.',
                'conductor_id.required' => 'Debe seleccionar un conductor responsable.',
                'conductor_id.exists' => 'El conductor seleccionado no existe.',
                'tipo_movilizacion.required' => 'Debe seleccionar el tipo de movilización (Ingreso / Salida / Interno).',
            ]);

            date_default_timezone_set('America/Lima');

            if (empty($validated['fecha_salida_programada'])) {
                $validated['fecha_salida_programada'] = now()->toDateTimeString();
            } else {
                $validated['fecha_salida_programada'] = date('Y-m-d H:i:s', strtotime($validated['fecha_salida_programada']));
            }

            $today = date('Y-m-d', strtotime($validated['fecha_salida_programada']));

            // Verificar capacidad del vehículo
            $vehiculo = Vehiculo::findOrFail($validated['vehiculo_id']);
            $capacidadMax = $vehiculo->capacidad_pasajeros ?? 46;

            $codigo = '';
            $skippedCount = 0;

            DB::transaction(function () use ($validated, $today, $capacidadMax, &$codigo, &$skippedCount) {
                // Trabajadores ya asignados hoy
                $existingWorkersOnDate = ManifiestoDetalle::whereHas('manifiesto', function($q) use ($today) {
                    $q->where('estado', '!=', 'CANCELADO')
                      ->whereDate('fecha_salida_programada', $today);
                })->pluck('trabajador_id')->toArray();

                // Resolver Ruta sin duplicar
                $ruta = $this->resolveRuta($validated['origen'] ?? '', $validated['destino'] ?? '', $validated['ruta_id'] ?? null);
                $rutaId = $ruta ? $ruta->id : (Ruta::first()->id ?? 1);

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
                $assignedWorkersInThisManifest = [];
                $empresasDB = Empresa::all();

                // 1. Process Standard ID List
                if (!empty($validated['pasajeros'])) {
                    foreach ($validated['pasajeros'] as $trabajadorId) {
                        if (is_numeric($trabajadorId)) {
                            if ($asientoNum > $capacidadMax) {
                                break;
                            }
                            if (in_array($trabajadorId, $existingWorkersOnDate) || in_array($trabajadorId, $assignedWorkersInThisManifest)) {
                                $skippedCount++;
                                continue;
                            }

                            $assignedWorkersInThisManifest[] = $trabajadorId;

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
                        if ($asientoNum > $capacidadMax) {
                            break;
                        }

                        $dni = trim($row['dni'] ?? '');
                        if (!$dni) continue;

                        $empresaNombre = trim($row['empresa'] ?? 'Contratista General');
                        $empresa = $this->resolveOrCreateEmpresa($empresaNombre, $empresasDB);

                        $embarque = trim($row['embarque'] ?? $validated['origen'] ?? 'Origen');
                        $campamento = trim($row['campamento'] ?? $validated['destino'] ?? 'Destino');

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
                                'empresa_id' => $empresa ? $empresa->id : 1,
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
                            if ($empresa && $trabajador->empresa_id != $empresa->id) $updates['empresa_id'] = $empresa->id;

                            if (!empty($updates)) {
                                $trabajador->update($updates);
                            }
                        }

                        if (in_array($trabajador->id, $existingWorkersOnDate) || in_array($trabajador->id, $assignedWorkersInThisManifest)) {
                            $skippedCount++;
                            continue;
                        }

                        $assignedWorkersInThisManifest[] = $trabajador->id;

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
            });

            $msg = "Manifiesto $codigo registrado exitosamente.";
            if ($skippedCount > 0) {
                $msg .= " Nota: Se omitieron $skippedCount pasajeros que ya estaban asignados a un manifiesto hoy o duplicados.";
            }

            return back()->with('success', $msg);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al registrar el manifiesto: ' . $e->getMessage())->withInput();
        }
    }

    public function addPasajeros(Request $request, Manifiesto $manifiesto)
    {
        try {
            if (!auth()->user()->hasWritePermission('manifiestos')) {
                return back()->with('error', 'Acceso denegado: Su cuenta no tiene permisos de escritura en el módulo de Manifiestos.');
            }
            if ($manifiesto->estado !== 'REGISTRADO') {
                return back()->with('error', 'No se puede agregar pasajeros a un manifiesto que ya ha sido CONFIRMADO o CANCELADO.')
                    ->withErrors(['error' => 'No se puede agregar pasajeros a un manifiesto que ya ha sido CONFIRMADO o CANCELADO.']);
            }

            $validated = $request->validate([
                'trabajador_ids' => 'required|array|min:1',
                'trabajador_ids.*' => 'exists:trabajadores,id'
            ], [
                'trabajador_ids.required' => 'Debe seleccionar al menos un trabajador.',
                'trabajador_ids.array' => 'Formato de trabajadores inválido.',
            ]);

            $vehiculo = $manifiesto->vehiculo;
            $capacidadMax = $vehiculo ? $vehiculo->capacidad_pasajeros : 46;

            $today = date('Y-m-d', strtotime($manifiesto->fecha_salida_programada));

            $added = 0;

            DB::transaction(function () use ($manifiesto, $validated, $today, $capacidadMax, &$added) {
                $existingWorkersOnDate = ManifiestoDetalle::whereHas('manifiesto', function($q) use ($today) {
                    $q->where('estado', '!=', 'CANCELADO')
                      ->whereDate('fecha_salida_programada', $today);
                })->pluck('trabajador_id')->toArray();

                $alreadyInManifest = ManifiestoDetalle::where('manifiesto_id', $manifiesto->id)
                    ->pluck('trabajador_id')->toArray();

                $asientoNum = (ManifiestoDetalle::where('manifiesto_id', $manifiesto->id)->max('numero_asiento') ?? 0) + 1;

                foreach ($validated['trabajador_ids'] as $trabajadorId) {
                    if ($asientoNum > $capacidadMax) {
                        break;
                    }

                    if (in_array($trabajadorId, $existingWorkersOnDate) || in_array($trabajadorId, $alreadyInManifest)) {
                        continue;
                    }

                    $alreadyInManifest[] = $trabajadorId;

                    ManifiestoDetalle::create([
                        'manifiesto_id' => $manifiesto->id,
                        'trabajador_id' => $trabajadorId,
                        'numero_asiento' => $asientoNum++,
                        'estado_embarque' => 'PENDIENTE',
                    ]);

                    $added++;
                }
            });

            return back()->with('success', "Se agregaron $added pasajero(s) al manifiesto.");
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al agregar pasajeros: ' . $e->getMessage());
        }
    }

    public function removePasajero(Manifiesto $manifiesto, ManifiestoDetalle $detalle)
    {
        try {
            if (!auth()->user()->hasWritePermission('manifiestos')) {
                return back()->with('error', 'Acceso denegado: Su cuenta no tiene permisos de escritura en el módulo de Manifiestos.');
            }
            if ($manifiesto->estado !== 'REGISTRADO') {
                return back()->with('error', 'No se puede modificar pasajeros en un manifiesto que ya no está en estado REGISTRADO.');
            }

            if ($detalle->manifiesto_id !== $manifiesto->id) {
                return back()->with('error', 'El detalle no pertenece a este manifiesto.');
            }

            $detalle->delete();

            // Reindexar números de asiento secuencialmente
            $detalles = ManifiestoDetalle::where('manifiesto_id', $manifiesto->id)
                ->orderBy('numero_asiento')
                ->get();

            foreach ($detalles as $idx => $d) {
                $d->update(['numero_asiento' => $idx + 1]);
            }

            return back()->with('success', 'Pasajero removido del manifiesto correctamente.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al remover pasajero: ' . $e->getMessage());
        }
    }

    public function updateEstado(Request $request, Manifiesto $manifiesto)
    {
        try {
            if (!auth()->user()->hasWritePermission('manifiestos')) {
                return back()->with('error', 'Acceso denegado: Su cuenta no tiene permisos de escritura en el módulo de Manifiestos.');
            }
            $validated = $request->validate([
                'estado' => 'required|in:REGISTRADO,CONFIRMADO,CANCELADO',
            ], [
                'estado.required' => 'El estado es obligatorio.',
                'estado.in' => 'El estado seleccionado no es válido.',
            ]);

            $manifiesto->update(['estado' => $validated['estado']]);

            $mensaje = 'Estado de manifiesto actualizado a ' . $validated['estado'] . '.';
            return back()->with('success', $mensaje);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al actualizar estado: ' . $e->getMessage());
        }
    }

    public function destroy(Manifiesto $manifiesto)
    {
        try {
            if (!auth()->user()->hasWritePermission('manifiestos')) {
                return back()->with('error', 'Acceso denegado: Su cuenta no tiene permisos de escritura en el módulo de Manifiestos.');
            }
            $manifiesto->update(['estado' => 'CANCELADO']);
            return back()->with('success', 'Manifiesto cancelado exitosamente.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al cancelar manifiesto: ' . $e->getMessage());
        }
    }

    public function imprimirOficial(Manifiesto $manifiesto)
    {
        try {
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
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al generar manifiesto oficial: ' . $e->getMessage());
        }
    }

    public function pdfPreimpreso(Manifiesto $manifiesto)
    {
        try {
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

            return $pdf->stream($filename);
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al generar PDF preimpreso: ' . $e->getMessage());
        }
    }

    // ==========================================
    // MÉTODOS PRIVADOS DE NORMALIZACIÓN Y LOOKUP
    // ==========================================

    private function cleanString($str)
    {
        $str = mb_strtolower(trim($str));
        $str = str_replace(['á','é','í','ó','ú','ñ','ü'], ['a','e','i','o','u','n','u'], $str);
        return preg_replace('/[^a-z0-9]/', '', $str);
    }

    private function findMatchingEmpresa($empresaNombre, $empresasDB = null)
    {
        if (empty($empresaNombre)) return null;
        $empresaNombre = trim($empresaNombre);
        if ($empresasDB === null) {
            $empresasDB = Empresa::all();
        }

        // 1. Coincidencia exacta insensible a mayúsculas/minúsculas
        $exact = $empresasDB->first(function($e) use ($empresaNombre) {
            return strcasecmp(trim($e->razon_social), $empresaNombre) === 0;
        });
        if ($exact) return $exact;

        // 2. Coincidencia alfanumérica normalizada (ignora puntos, comas, guiones, espacios, S.A.C vs SAC, tildes)
        $cleanInput = $this->cleanString($empresaNombre);
        if ($cleanInput !== '') {
            $normalized = $empresasDB->first(function($e) use ($cleanInput) {
                return $this->cleanString($e->razon_social) === $cleanInput;
            });
            if ($normalized) return $normalized;
        }

        // 3. Coincidencia por subcadena / contención de nombre significativo
        if (strlen($cleanInput) >= 4) {
            $contains = $empresasDB->first(function($e) use ($cleanInput) {
                $cleanDB = $this->cleanString($e->razon_social);
                if (strlen($cleanDB) < 4) return false;
                return str_contains($cleanDB, $cleanInput) || str_contains($cleanInput, $cleanDB);
            });
            if ($contains) return $contains;
        }

        return null;
    }

    private function resolveOrCreateEmpresa($empresaNombre, &$empresasDB = null)
    {
        $empresaNombre = trim($empresaNombre ?? '');
        if (!$empresaNombre) {
            return ($empresasDB ? $empresasDB->first() : null) ?: Empresa::first();
        }

        if ($empresasDB === null) {
            $empresasDB = Empresa::all();
        }

        $existing = $this->findMatchingEmpresa($empresaNombre, $empresasDB);
        if ($existing) {
            return $existing;
        }

        // Doble verificación en BD
        $dbExisting = Empresa::whereRaw('LOWER(TRIM(razon_social)) = ?', [mb_strtolower($empresaNombre)])->first();
        if ($dbExisting) {
            $empresasDB->push($dbExisting);
            return $dbExisting;
        }

        $newEmpresa = Empresa::create([
            'ruc' => null,
            'razon_social' => mb_strtoupper($empresaNombre),
            'es_contratista' => 1,
            'estado' => 1,
        ]);

        $empresasDB->push($newEmpresa);
        return $newEmpresa;
    }

    private function resolveRuta($origen, $destino = null, $rutaId = null)
    {
        if ($rutaId) {
            $ruta = Ruta::find($rutaId);
            if ($ruta) return $ruta;
        }

        $origen = mb_strtoupper(trim($origen ?? ''));
        $destino = mb_strtoupper(trim($destino ?: $origen));

        if (!$origen) {
            return Ruta::where('activa', true)->first() ?: Ruta::first();
        }

        // 1. Coincidencia exacta insensible a mayúsculas/minúsculas para origen & destino
        $ruta = Ruta::whereRaw('LOWER(TRIM(origen)) = ? AND LOWER(TRIM(destino)) = ?', [mb_strtolower($origen), mb_strtolower($destino)])->first();
        if ($ruta) return $ruta;

        // 2. Coincidencia por origen si destino es igual a origen (punto de traslado)
        $ruta = Ruta::whereRaw('LOWER(TRIM(origen)) = ?', [mb_strtolower($origen)])->first();
        if ($ruta) return $ruta;

        // 3. Coincidencia alfanumérica normalizada
        $cleanOrigen = $this->cleanString($origen);
        $cleanDestino = $this->cleanString($destino);
        $allRutas = Ruta::all();
        $matched = $allRutas->first(function($r) use ($cleanOrigen, $cleanDestino) {
            return $this->cleanString($r->origen) === $cleanOrigen && $this->cleanString($r->destino) === $cleanDestino;
        });
        if ($matched) return $matched;

        // 4. Crear solo una ruta limpia si realmente es un nuevo punto/ruta
        return Ruta::create([
            'origen' => $origen,
            'destino' => $destino,
            'duracion_estimada_minutos' => 120,
            'activa' => true,
        ]);
    }
}