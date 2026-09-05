<?php

namespace App\Http\Controllers;

use App\Models\Manifiesto;
use App\Models\ManifiestoDetalle;
use App\Models\Trabajador;
use App\Models\Vehiculo;
use App\Models\Conductor;
use App\Models\Ruta;
use App\Models\Empresa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $periodo = $request->query('periodo', 'semana');
        $now = Carbon::now();

        // Calculate Date Range
        switch ($periodo) {
            case 'hoy':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $periodoLabel = 'Hoy (' . $now->locale('es')->isoFormat('D MMM YYYY') . ')';
                break;
            case 'mes':
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                $periodoLabel = 'Este Mes (' . ucfirst($now->locale('es')->isoFormat('MMMM YYYY')) . ')';
                break;
            case '30dias':
                $startDate = $now->copy()->subDays(29)->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $periodoLabel = 'Últimos 30 Días';
                break;
            case 'todo':
                $startDate = Carbon::create(2020, 1, 1)->startOfDay();
                $endDate = $now->copy()->addYears(5)->endOfDay();
                $periodoLabel = 'Histórico Completo';
                break;
            case 'semana':
            default:
                $periodo = 'semana';
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfWeek();
                $periodoLabel = 'Esta Semana (' . $startDate->locale('es')->isoFormat('D MMM') . ' - ' . $endDate->locale('es')->isoFormat('D MMM') . ')';
                break;
        }

        $startStr = $startDate->toDateTimeString();
        $endStr = $endDate->toDateTimeString();

        // 1. General & Filtered Stats
        $totalTrabajadores = Trabajador::count();
        $trabajadoresAptos = Trabajador::where('estado_acreditacion', 'APTO')->count();
        $trabajadoresObservados = Trabajador::where('estado_acreditacion', '!=', 'APTO')->count();

        $totalManifiestos = Manifiesto::count();
        $manifiestosPeriodoQuery = Manifiesto::whereBetween('fecha_salida_programada', [$startStr, $endStr]);
        $manifiestosPeriodoCount = (clone $manifiestosPeriodoQuery)->count();

        $manifiestoIdsPeriodo = (clone $manifiestosPeriodoQuery)->pluck('id');
        $pasajerosMovilizadosPeriodo = ManifiestoDetalle::whereIn('manifiesto_id', $manifiestoIdsPeriodo)->count();
        $pasajerosMovilizadosTotal = ManifiestoDetalle::count();

        $vehiculosTotal = Vehiculo::count();
        $vehiculosActivos = Vehiculo::where('activo', true)->count();

        $conductoresTotal = Conductor::count();
        $conductoresActivos = Conductor::where('activo', true)->count();
        
        // Conductores con brevete por vencer o vencido
        $conductoresAlertaBrevete = Conductor::whereNotNull('brevete_interno_vencimiento')
            ->where('brevete_interno_vencimiento', '<=', $now->copy()->addDays(30)->toDateString())
            ->count();

        $rutasTotal = Ruta::where('activa', true)->count();
        $empresasTotal = Empresa::count();

        // 2. Pipeline de Estados de Manifiestos en el Periodo
        $estados = ['REGISTRADO', 'CONFIRMADO', 'EN_GARITA', 'EN_RUTA', 'FINALIZADO', 'CANCELADO'];
        $pipelineData = [];
        foreach ($estados as $est) {
            $count = Manifiesto::where('estado', $est)
                ->whereBetween('fecha_salida_programada', [$startStr, $endStr])
                ->count();
            $pipelineData[$est] = $count;
        }

        // 3. Movilización Diaria de la Semana (7 Días de Lunes a Domingo)
        $semanaChartStart = $now->copy()->startOfWeek();
        $movilizacionSemanal = [];
        for ($i = 0; $i < 7; $i++) {
            $dayDate = $semanaChartStart->copy()->addDays($i);
            $dayStart = $dayDate->copy()->startOfDay()->toDateTimeString();
            $dayEnd = $dayDate->copy()->endOfDay()->toDateTimeString();

            $dayManifiestoIds = Manifiesto::whereBetween('fecha_salida_programada', [$dayStart, $dayEnd])->pluck('id');
            $paxDay = ManifiestoDetalle::whereIn('manifiesto_id', $dayManifiestoIds)->count();

            $movilizacionSemanal[] = [
                'dia_corto' => ucfirst($dayDate->locale('es')->isoFormat('ddd D')),
                'fecha' => $dayDate->toDateString(),
                'pasajeros' => $paxDay,
                'manifiestos' => count($dayManifiestoIds),
                'es_hoy' => $dayDate->isToday(),
            ];
        }

        // 4. Top Rutas con más Pasajeros y Viajes
        $topRutas = Ruta::withCount(['manifiestos' => function ($q) use ($startStr, $endStr) {
                $q->whereBetween('fecha_salida_programada', [$startStr, $endStr]);
            }])
            ->get()
            ->map(function ($r) use ($startStr, $endStr) {
                $manIds = Manifiesto::where('ruta_id', $r->id)
                    ->whereBetween('fecha_salida_programada', [$startStr, $endStr])
                    ->pluck('id');
                $pax = ManifiestoDetalle::whereIn('manifiesto_id', $manIds)->count();
                return [
                    'id' => $r->id,
                    'nombre' => $r->nombre_ruta ?: ($r->origen . ' ➔ ' . $r->destino),
                    'origen' => $r->origen,
                    'destino' => $r->destino,
                    'total_viajes' => $r->manifiestos_count,
                    'total_pasajeros' => $pax,
                ];
            })
            ->sortByDesc('total_pasajeros')
            ->values()
            ->take(5);

        // 5. Distribución de Pasajeros por Empresa Contratista
        $topEmpresas = Empresa::withCount('trabajadores')
            ->get()
            ->map(function ($emp) use ($manifiestoIdsPeriodo) {
                $trabajadorIds = $emp->trabajadores()->pluck('id');
                $pax = ManifiestoDetalle::whereIn('manifiesto_id', $manifiestoIdsPeriodo)
                    ->whereIn('trabajador_id', $trabajadorIds)
                    ->count();
                return [
                    'id' => $emp->id,
                    'razon_social' => $emp->razon_social,
                    'total_trabajadores' => $emp->trabajadores_count,
                    'pasajeros_movilizados' => $pax,
                ];
            })
            ->sortByDesc('pasajeros_movilizados')
            ->values()
            ->take(6);

        // 6. Listado de Manifiestos de la Semana / Recientes
        $manifiestosRecientes = Manifiesto::with(['ruta', 'vehiculo', 'conductor.trabajador', 'copiloto.trabajador'])
            ->withCount('detalles')
            ->whereBetween('fecha_salida_programada', [$startStr, $endStr])
            ->latest('fecha_salida_programada')
            ->take(10)
            ->get();

        if ($manifiestosRecientes->isEmpty()) {
            $manifiestosRecientes = Manifiesto::with(['ruta', 'vehiculo', 'conductor.trabajador', 'copiloto.trabajador'])
                ->withCount('detalles')
                ->latest('fecha_salida_programada')
                ->take(10)
                ->get();
        }

        return Inertia::render('Dashboard', [
            'periodo_actual' => $periodo,
            'periodo_label' => $periodoLabel,
            'stats' => [
                'total_trabajadores' => $totalTrabajadores,
                'trabajadores_aptos' => $trabajadoresAptos,
                'trabajadores_observados' => $trabajadoresObservados,
                'total_manifiestos' => $totalManifiestos,
                'manifiestos_periodo' => $manifiestosPeriodoCount,
                'pasajeros_movilizados_periodo' => $pasajerosMovilizadosPeriodo,
                'pasajeros_movilizados_total' => $pasajerosMovilizadosTotal,
                'vehiculos_total' => $vehiculosTotal,
                'vehiculos_activos' => $vehiculosActivos,
                'conductores_total' => $conductoresTotal,
                'conductores_activos' => $conductoresActivos,
                'conductores_alerta_brevete' => $conductoresAlertaBrevete,
                'rutas_total' => $rutasTotal,
                'empresas_total' => $empresasTotal,
            ],
            'pipeline' => $pipelineData,
            'movilizacion_semanal' => $movilizacionSemanal,
            'top_rutas' => $topRutas,
            'top_empresas' => $topEmpresas,
            'manifiestos_recientes' => $manifiestosRecientes,
        ]);
    }
}
