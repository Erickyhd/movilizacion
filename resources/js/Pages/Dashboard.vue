<script setup>
import { ref, onMounted, watch, nextTick } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Chart from 'chart.js/auto';
import { 
  Users, 
  UserCheck, 
  FileText, 
  Bus, 
  Building2, 
  MapPin, 
  ShieldCheck, 
  ArrowUpRight, 
  Clock, 
  CheckCircle2, 
  AlertTriangle, 
  Calendar, 
  Printer, 
  TrendingUp, 
  ArrowRight,
  Sparkles,
  Activity,
  Layers,
  ChevronRight
} from 'lucide-vue-next';

const props = defineProps({
  periodo_actual: String,
  periodo_label: String,
  stats: Object,
  pipeline: Object,
  movilizacion_semanal: Array,
  top_rutas: Array,
  top_empresas: Array,
  manifiestos_recientes: Array,
});

const activePeriod = ref(props.periodo_actual || 'semana');

const changePeriod = (period) => {
  activePeriod.value = period;
  router.get(route('dashboard'), { periodo: period }, { preserveState: true, preserveScroll: true });
};

// Chart.js references
const weeklyChartCanvas = ref(null);
let weeklyChartInstance = null;

const renderWeeklyChart = () => {
  if (!weeklyChartCanvas.value) return;
  if (weeklyChartInstance) {
    weeklyChartInstance.destroy();
  }

  const ctx = weeklyChartCanvas.value.getContext('2d');
  const labels = (props.movilizacion_semanal || []).map(d => d.dia_corto);
  const dataPax = (props.movilizacion_semanal || []).map(d => d.pasajeros);
  const dataMan = (props.movilizacion_semanal || []).map(d => d.manifiestos);

  // Gradient for bars
  const gradientPax = ctx.createLinearGradient(0, 0, 0, 300);
  gradientPax.addColorStop(0, 'rgba(37, 99, 235, 0.85)');
  gradientPax.addColorStop(1, 'rgba(59, 130, 246, 0.25)');

  weeklyChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Pasajeros Movilizados',
          data: dataPax,
          backgroundColor: gradientPax,
          borderColor: '#2563eb',
          borderWidth: 2,
          borderRadius: 8,
          borderSkipped: false,
          yAxisID: 'y',
        },
        {
          label: 'Manifiestos / Viajes',
          data: dataMan,
          type: 'line',
          borderColor: '#8b5cf6',
          backgroundColor: '#8b5cf6',
          borderWidth: 3,
          pointBackgroundColor: '#8b5cf6',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          pointRadius: 5,
          pointHoverRadius: 7,
          tension: 0.35,
          yAxisID: 'y1',
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        mode: 'index',
        intersect: false,
      },
      plugins: {
        legend: {
          display: true,
          position: 'top',
          labels: {
            boxWidth: 12,
            boxHeight: 12,
            usePointStyle: true,
            font: { size: 11, weight: '700', family: "'Plus Jakarta Sans', sans-serif" },
            color: '#475569',
          }
        },
        tooltip: {
          backgroundColor: 'rgba(15, 23, 42, 0.95)',
          titleFont: { size: 12, weight: 'bold' },
          bodyFont: { size: 11 },
          padding: 10,
          cornerRadius: 8,
          boxPadding: 4,
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { font: { size: 11, weight: '600' }, color: '#64748b' }
        },
        y: {
          type: 'linear',
          display: true,
          position: 'left',
          beginAtZero: true,
          grid: { color: 'rgba(226, 232, 240, 0.6)' },
          ticks: { font: { size: 10, weight: '600' }, color: '#64748b', precision: 0 },
          title: { display: true, text: 'Nº Pasajeros', font: { size: 10, weight: 'bold' }, color: '#2563eb' }
        },
        y1: {
          type: 'linear',
          display: true,
          position: 'right',
          beginAtZero: true,
          grid: { drawOnChartArea: false },
          ticks: { font: { size: 10, weight: '600' }, color: '#8b5cf6', precision: 0 },
          title: { display: true, text: 'Nº Manifiestos', font: { size: 10, weight: 'bold' }, color: '#8b5cf6' }
        }
      }
    }
  });
};

onMounted(() => {
  nextTick(() => {
    renderWeeklyChart();
  });
});

watch(() => props.movilizacion_semanal, () => {
  nextTick(() => {
    renderWeeklyChart();
  });
}, { deep: true });

const getStatusBadge = (estado) => {
  switch (estado) {
    case 'REGISTRADO': return 'bg-slate-100 text-slate-700 border-slate-300';
    case 'CONFIRMADO': return 'bg-blue-100 text-blue-800 border-blue-200';
    case 'EN_GARITA': return 'bg-amber-100 text-amber-800 border-amber-200';
    case 'EN_RUTA': return 'bg-purple-100 text-purple-800 border-purple-200';
    case 'FINALIZADO': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
    case 'CANCELADO': return 'bg-red-100 text-red-800 border-red-200';
    default: return 'bg-slate-100 text-slate-700 border-slate-200';
  }
};

const printPreimpresoSheet = (id) => {
  window.open(route('manifiestos.pdfPreimpreso', id), '_blank');
};
</script>

<template>
  <AppLayout>
    <div class="w-full space-y-6 pb-12">
      
      <!-- Top Header & Period Filter Controls -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/80 flex flex-col xl:flex-row items-start xl:items-center justify-between gap-5">
        <div>
          <div class="flex items-center space-x-3 mb-1.5">
            <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Centro de Control de Movilización y Transporte</h2>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 border border-emerald-200 shadow-xs">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
              En Línea
            </span>
          </div>
          <p class="text-slate-500 text-xs font-medium">
            Monitor ejecutivo de viajes, personal acreditado, flota vehicular y estados de traslado en tiempo real.
          </p>
        </div>

        <!-- Period Selector Buttons -->
        <div class="flex flex-wrap items-center gap-2 bg-slate-100 p-1.5 rounded-xl border border-slate-200 shadow-xs">
          <button 
            @click="changePeriod('hoy')"
            :class="['px-3 py-1.5 rounded-lg text-xs font-extrabold transition cursor-pointer', activePeriod === 'hoy' ? 'bg-white text-blue-700 shadow-xs' : 'text-slate-600 hover:text-slate-900']"
          >
            Hoy
          </button>
          <button 
            @click="changePeriod('semana')"
            :class="['px-3 py-1.5 rounded-lg text-xs font-extrabold transition cursor-pointer', activePeriod === 'semana' ? 'bg-white text-blue-700 shadow-xs' : 'text-slate-600 hover:text-slate-900']"
          >
            Esta Semana
          </button>
          <button 
            @click="changePeriod('mes')"
            :class="['px-3 py-1.5 rounded-lg text-xs font-extrabold transition cursor-pointer', activePeriod === 'mes' ? 'bg-white text-blue-700 shadow-xs' : 'text-slate-600 hover:text-slate-900']"
          >
            Este Mes
          </button>
          <button 
            @click="changePeriod('30dias')"
            :class="['px-3 py-1.5 rounded-lg text-xs font-extrabold transition cursor-pointer', activePeriod === '30dias' ? 'bg-white text-blue-700 shadow-xs' : 'text-slate-600 hover:text-slate-900']"
          >
            Últimos 30 Días
          </button>
          <button 
            @click="changePeriod('todo')"
            :class="['px-3 py-1.5 rounded-lg text-xs font-extrabold transition cursor-pointer', activePeriod === 'todo' ? 'bg-white text-blue-700 shadow-xs' : 'text-slate-600 hover:text-slate-900']"
          >
            Histórico Total
          </button>
        </div>
      </div>

      <!-- 6 High-Impact Interactive KPI Stat Cards (Clickable) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        
        <!-- 1. Trabajadores -->
        <Link 
          :href="route('trabajadores.index')" 
          class="bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm hover:shadow-md hover:border-blue-300 transition-all duration-200 group flex flex-col justify-between cursor-pointer"
        >
          <div class="flex items-center justify-between mb-3">
            <span class="text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Trabajadores</span>
            <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center group-hover:scale-110 group-hover:bg-blue-600 group-hover:text-white transition">
              <Users class="w-4 h-4" />
            </div>
          </div>
          <div>
            <div class="text-2xl font-black text-slate-900 tracking-tight">{{ stats.total_trabajadores }}</div>
            <div class="flex items-center justify-between text-xs mt-1.5 pt-2 border-t border-slate-100">
              <span class="text-emerald-600 font-bold flex items-center text-[11px]">
                <UserCheck class="w-3 h-3 mr-1" /> {{ stats.trabajadores_aptos }} Aptos
              </span>
              <ArrowRight class="w-3.5 h-3.5 text-slate-400 group-hover:text-blue-600 group-hover:translate-x-0.5 transition" />
            </div>
          </div>
        </Link>

        <!-- 2. Manifiestos -->
        <Link 
          :href="route('manifiestos.index')" 
          class="bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm hover:shadow-md hover:border-indigo-300 transition-all duration-200 group flex flex-col justify-between cursor-pointer"
        >
          <div class="flex items-center justify-between mb-3">
            <span class="text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Manifiestos</span>
            <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center group-hover:scale-110 group-hover:bg-indigo-600 group-hover:text-white transition">
              <FileText class="w-4 h-4" />
            </div>
          </div>
          <div>
            <div class="text-2xl font-black text-slate-900 tracking-tight">{{ stats.manifiestos_periodo }}</div>
            <div class="flex items-center justify-between text-xs mt-1.5 pt-2 border-t border-slate-100">
              <span class="text-indigo-600 font-bold text-[11px]">
                {{ stats.total_manifiestos }} Total Viajes
              </span>
              <ArrowRight class="w-3.5 h-3.5 text-slate-400 group-hover:text-indigo-600 group-hover:translate-x-0.5 transition" />
            </div>
          </div>
        </Link>

        <!-- 3. Pasajeros Movilizados -->
        <Link 
          :href="route('manifiestos.index')" 
          class="bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm hover:shadow-md hover:border-emerald-300 transition-all duration-200 group flex flex-col justify-between cursor-pointer"
        >
          <div class="flex items-center justify-between mb-3">
            <span class="text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Movilizados</span>
            <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:scale-110 group-hover:bg-emerald-600 group-hover:text-white transition">
              <TrendingUp class="w-4 h-4" />
            </div>
          </div>
          <div>
            <div class="text-2xl font-black text-emerald-600 tracking-tight">{{ stats.pasajeros_movilizados_periodo }}</div>
            <div class="flex items-center justify-between text-xs mt-1.5 pt-2 border-t border-slate-100">
              <span class="text-slate-500 font-medium text-[11px]">
                {{ stats.pasajeros_movilizados_total }} Histórico
              </span>
              <ArrowRight class="w-3.5 h-3.5 text-slate-400 group-hover:text-emerald-600 group-hover:translate-x-0.5 transition" />
            </div>
          </div>
        </Link>

        <!-- 4. Vehículos -->
        <Link 
          :href="route('flota.index')" 
          class="bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm hover:shadow-md hover:border-purple-300 transition-all duration-200 group flex flex-col justify-between cursor-pointer"
        >
          <div class="flex items-center justify-between mb-3">
            <span class="text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Flota Operativa</span>
            <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center group-hover:scale-110 group-hover:bg-purple-600 group-hover:text-white transition">
              <Bus class="w-4 h-4" />
            </div>
          </div>
          <div>
            <div class="text-2xl font-black text-slate-900 tracking-tight">{{ stats.vehiculos_activos }} <span class="text-xs font-semibold text-slate-400">/ {{ stats.vehiculos_total }}</span></div>
            <div class="flex items-center justify-between text-xs mt-1.5 pt-2 border-t border-slate-100">
              <span class="text-purple-600 font-bold text-[11px]">Buses</span>
              <ArrowRight class="w-3.5 h-3.5 text-slate-400 group-hover:text-purple-600 group-hover:translate-x-0.5 transition" />
            </div>
          </div>
        </Link>

        <!-- 5. Conductores -->
        <Link 
          :href="route('flota.index')" 
          class="bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm hover:shadow-md hover:border-amber-300 transition-all duration-200 group flex flex-col justify-between cursor-pointer"
        >
          <div class="flex items-center justify-between mb-3">
            <span class="text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Conductores</span>
            <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center group-hover:scale-110 group-hover:bg-amber-600 group-hover:text-white transition">
              <ShieldCheck class="w-4 h-4" />
            </div>
          </div>
          <div>
            <div class="text-2xl font-black text-slate-900 tracking-tight">{{ stats.conductores_activos }} <span class="text-xs font-semibold text-slate-400">/ {{ stats.conductores_total }}</span></div>
            <div class="flex items-center justify-between text-xs mt-1.5 pt-2 border-t border-slate-100">
              <span v-if="stats.conductores_alerta_brevete > 0" class="text-red-600 font-bold text-[10px] flex items-center">
                <AlertTriangle class="w-3 h-3 mr-0.5" /> {{ stats.conductores_alerta_brevete }} Alerta Brevete
              </span>
              <span v-else class="text-emerald-600 font-bold text-[11px]">Habilitados</span>
              <ArrowRight class="w-3.5 h-3.5 text-slate-400 group-hover:text-amber-600 group-hover:translate-x-0.5 transition" />
            </div>
          </div>
        </Link>

        <!-- 6. Rutas y Contratistas -->
        <Link 
          :href="route('rutas.index')" 
          class="bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm hover:shadow-md hover:border-cyan-300 transition-all duration-200 group flex flex-col justify-between cursor-pointer"
        >
          <div class="flex items-center justify-between mb-3">
            <span class="text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Rutas & Empresas</span>
            <div class="w-9 h-9 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center group-hover:scale-110 group-hover:bg-cyan-600 group-hover:text-white transition">
              <MapPin class="w-4 h-4" />
            </div>
          </div>
          <div>
            <div class="text-2xl font-black text-slate-900 tracking-tight">{{ stats.rutas_total }} <span class="text-xs font-semibold text-slate-400">Rutas</span></div>
            <div class="flex items-center justify-between text-xs mt-1.5 pt-2 border-t border-slate-100">
              <span class="text-cyan-700 font-bold text-[11px]">{{ stats.empresas_total }} Empresas</span>
              <ArrowRight class="w-3.5 h-3.5 text-slate-400 group-hover:text-cyan-600 group-hover:translate-x-0.5 transition" />
            </div>
          </div>
        </Link>

      </div>

      <!-- Pipeline de Estados de Manifiesto (Funnel Horizontal) -->
      <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center space-x-2.5">
            <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
              <Layers class="w-4 h-4" />
            </div>
            <h3 class="font-extrabold text-slate-900 text-sm tracking-tight">Estados de Manifiestos</h3>
          </div>
          <span class="text-xs font-bold text-slate-400">{{ periodo_label }}</span>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 gap-3">
          
          <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/70">
            <div class="text-[10px] font-extrabold uppercase text-slate-500 tracking-wider">1. Registrado</div>
            <div class="text-2xl font-black text-slate-800 mt-1">{{ pipeline.REGISTRADO || 0 }}</div>
            <div class="text-[10px] text-slate-400 font-semibold mt-0.5">Borrador / Preparación</div>
          </div>

          <div class="p-4 rounded-xl border border-blue-200 bg-blue-50/50">
            <div class="text-[10px] font-extrabold uppercase text-blue-700 tracking-wider">2. Confirmado</div>
            <div class="text-2xl font-black text-blue-700 mt-1">{{ pipeline.CONFIRMADO || 0 }}</div>
            <div class="text-[10px] text-blue-600/70 font-semibold mt-0.5">Aprobado para Salida</div>
          </div>

          <!-- <div class="p-4 rounded-xl border border-amber-200 bg-amber-50/50">
            <div class="text-[10px] font-extrabold uppercase text-amber-700 tracking-wider">3. En Garita</div>
            <div class="text-2xl font-black text-amber-700 mt-1">{{ pipeline.EN_GARITA || 0 }}</div>
            <div class="text-[10px] text-amber-600/70 font-semibold mt-0.5">Control de Embarque</div>
          </div>

          <div class="p-4 rounded-xl border border-purple-200 bg-purple-50/50">
            <div class="text-[10px] font-extrabold uppercase text-purple-700 tracking-wider">4. En Ruta</div>
            <div class="text-2xl font-black text-purple-700 mt-1">{{ pipeline.EN_RUTA || 0 }}</div>
            <div class="text-[10px] text-purple-600/70 font-semibold mt-0.5">Unidad en Tránsito</div>
          </div>

          <div class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/50">
            <div class="text-[10px] font-extrabold uppercase text-emerald-700 tracking-wider">5. Finalizado</div>
            <div class="text-2xl font-black text-emerald-700 mt-1">{{ pipeline.FINALIZADO || 0 }}</div>
            <div class="text-[10px] text-emerald-600/70 font-semibold mt-0.5">Llegada a Destino</div>
          </div> -->

          <div class="p-4 rounded-xl border border-red-200 bg-red-50/50">
            <div class="text-[10px] font-extrabold uppercase text-red-700 tracking-wider">6. Cancelado</div>
            <div class="text-2xl font-black text-red-700 mt-1">{{ pipeline.CANCELADO || 0 }}</div>
            <div class="text-[10px] text-red-600/70 font-semibold mt-0.5">Viaje No Realizado</div>
          </div>

        </div>
      </div>

      <!-- Charts & Ranked Distribution Section -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left: Movilización Diaria Chart (2 cols) -->
        <div class="lg:col-span-2 bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-2.5">
              <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <Activity class="w-4 h-4" />
              </div>
              <div>
                <h3 class="font-extrabold text-slate-900 text-sm tracking-tight">Movilización de Pasajeros por Día</h3>
                <p class="text-[11px] text-slate-400 font-medium">Volumen diario de trabajadores trasladados en la semana actual</p>
              </div>
            </div>
            <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-200">
              {{ periodo_label }}
            </span>
          </div>

          <div class="h-64 w-full relative">
            <canvas ref="weeklyChartCanvas"></canvas>
          </div>
        </div>

        <!-- Right: Top Rutas & Contratistas -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between space-y-5">
          
          <!-- Top Rutas -->
          <div>
            <div class="flex items-center justify-between mb-3">
              <h4 class="font-extrabold text-slate-900 text-xs uppercase tracking-wider flex items-center">
                <MapPin class="w-3.5 h-3.5 mr-1.5 text-blue-600" /> Top Rutas Transitadas
              </h4>
              <Link :href="route('rutas.index')" class="text-[11px] font-bold text-blue-600 hover:underline">Ver Rutas</Link>
            </div>

            <div class="space-y-2">
              <div 
                v-for="(r, idx) in top_rutas" 
                :key="r.id"
                class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/70 flex items-center justify-between text-xs"
              >
                <div class="flex items-center space-x-2">
                  <span class="w-5 h-5 rounded-md bg-blue-100 text-blue-800 font-black text-[10px] flex items-center justify-center">
                    {{ idx + 1 }}
                  </span>
                  <span class="font-bold text-slate-800 truncate max-w-[140px]">{{ r.nombre }}</span>
                </div>
                <div class="text-right">
                  <strong class="text-slate-900 font-extrabold block">{{ r.total_pasajeros }} pax</strong>
                  <span class="text-[10px] text-slate-400">{{ r.total_viajes }} viajes</span>
                </div>
              </div>

              <div v-if="!top_rutas || top_rutas.length === 0" class="text-center py-4 text-xs text-slate-400">
                No hay viajes registrados en este período.
              </div>
            </div>
          </div>

          <!-- Top Contratistas -->
          <div class="pt-4 border-t border-slate-100">
            <div class="flex items-center justify-between mb-3">
              <h4 class="font-extrabold text-slate-900 text-xs uppercase tracking-wider flex items-center">
                <Building2 class="w-3.5 h-3.5 mr-1.5 text-amber-600" /> Empresas con Mayor Personal
              </h4>
              <Link :href="route('empresas.index')" class="text-[11px] font-bold text-amber-600 hover:underline">Ver Empresas</Link>
            </div>

            <div class="space-y-2">
              <div 
                v-for="e in top_empresas.slice(0, 3)" 
                :key="e.id"
                class="p-2.5 rounded-xl bg-amber-50/40 border border-amber-200/50 flex items-center justify-between text-xs"
              >
                <div class="truncate max-w-[170px]">
                  <span class="font-bold text-slate-800 block truncate">{{ e.razon_social }}</span>
                  <span class="text-[10px] text-slate-400">{{ e.total_trabajadores }} trabajadores en padrón</span>
                </div>
                <span class="font-black text-amber-700 bg-amber-100/80 px-2 py-0.5 rounded-md text-xs">
                  {{ e.pasajeros_movilizados }} pax
                </span>
              </div>

              <div v-if="!top_empresas || top_empresas.length === 0" class="text-center py-4 text-xs text-slate-400">
                No hay contratistas con traslados activos.
              </div>
            </div>
          </div>

        </div>

      </div>

      <!-- Recent / Weekly Manifests Master Table -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
          <div>
            <div class="flex items-center space-x-2">
              <FileText class="w-4 h-4 text-blue-600" />
              <h3 class="font-extrabold text-slate-900 text-sm">Manifiestos de Traslado Recientes ({{ periodo_label }})</h3>
            </div>
            <p class="text-[11px] text-slate-500 font-medium mt-0.5">Control de viajes programados con acceso inmediato a descarga de PDF preimpreso</p>
          </div>

          <Link 
            :href="route('manifiestos.index')" 
            class="inline-flex items-center text-xs font-extrabold text-blue-600 hover:text-blue-700 bg-blue-50 px-3 py-1.5 rounded-xl border border-blue-200 shadow-xs transition"
          >
            Ver Todos los Manifiestos <ArrowUpRight class="w-4 h-4 ml-1" />
          </Link>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50/90 text-xs font-bold text-slate-500 uppercase border-b border-slate-200/80">
              <tr>
                <th class="px-5 py-3.5">Código</th>
                <th class="px-5 py-3.5">Ruta de Transporte</th>
                <th class="px-5 py-3.5">Vehículo / Placa</th>
                <th class="px-5 py-3.5">Conductor Responsable</th>
                <th class="px-5 py-3.5 text-center">Pasajeros</th>
                <th class="px-5 py-3.5">Fecha y Hora Programada</th>
                <th class="px-5 py-3.5">Estado</th>
                <th class="px-5 py-3.5 text-right">Acción</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="m in manifiestos_recientes" :key="m.id" class="hover:bg-slate-50/80 transition text-xs">
                
                <!-- Código -->
                <td class="px-5 py-4 font-mono font-black text-slate-900 text-sm">
                  {{ m.codigo_manifiesto }}
                </td>

                <!-- Ruta -->
                <td class="px-5 py-4">
                  <div class="flex items-center space-x-1.5 font-bold text-slate-800">
                    <span>{{ m.ruta?.origen }}</span>
                    <span class="text-blue-500">➔</span>
                    <span>{{ m.ruta?.destino }}</span>
                  </div>
                </td>

                <!-- Vehículo -->
                <td class="px-5 py-4">
                  <div class="font-mono font-extrabold text-purple-700 text-xs">{{ m.vehiculo?.placa || 'S/P' }}</div>
                  <span class="text-[11px] text-slate-400 font-medium block">{{ m.vehiculo?.marca_modelo || 'Bus Interprovincial' }}</span>
                </td>

                <!-- Conductor -->
                <td class="px-5 py-4">
                  <span class="font-bold text-slate-800 block uppercase">
                    {{ m.conductor?.nombres || m.conductor?.trabajador?.nombres }} {{ m.conductor?.apellido_paterno || m.conductor?.trabajador?.apellido_paterno }}
                  </span>
                  <span v-if="m.copiloto" class="text-[10px] text-slate-400 block">
                    Copiloto: {{ m.copiloto?.nombres || m.copiloto?.trabajador?.nombres }}
                  </span>
                </td>

                <!-- Conteo Pasajeros -->
                <td class="px-5 py-4 text-center">
                  <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-black bg-blue-50 text-blue-700 border border-blue-200 shadow-xs">
                    <Users class="w-3 h-3 mr-1" /> {{ m.detalles_count || (m.detalles ? m.detalles.length : 0) }}
                  </span>
                </td>

                <!-- Fecha Salida -->
                <td class="px-5 py-4 text-slate-600 font-semibold">
                  <div class="flex items-center space-x-1">
                    <Calendar class="w-3.5 h-3.5 text-slate-400" />
                    <span>{{ new Date(m.fecha_salida_programada).toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' }) }}</span>
                  </div>
                  <div class="text-[11px] text-slate-400 pl-4 font-mono">
                    {{ new Date(m.fecha_salida_programada).toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' }) }}
                  </div>
                </td>

                <!-- Estado -->
                <td class="px-5 py-4">
                  <span :class="['px-2.5 py-1 rounded-full text-[11px] font-extrabold border inline-flex items-center shadow-xs', getStatusBadge(m.estado)]">
                    {{ m.estado }}
                  </span>
                </td>

                <!-- Quick PDF Action -->
                <td class="px-5 py-4 text-right whitespace-nowrap">
                  <button 
                    @click="printPreimpresoSheet(m.id)"
                    class="bg-green-600 hover:bg-green-500 text-white text-[11px] font-extrabold px-3 py-1.5 rounded-xl shadow-xs flex items-center space-x-1.5 transition cursor-pointer ml-auto"
                    title="Imprimir Manifiesto en 1 Hoja A4"
                  >
                    <Printer class="w-3.5 h-3.5" />
                    <span>PDF</span>
                  </button>
                </td>

              </tr>

              <tr v-if="!manifiestos_recientes || manifiestos_recientes.length === 0">
                <td colspan="8" class="px-6 py-10 text-center text-slate-400 text-sm">
                  No hay manifiestos registrados en este período.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </AppLayout>
</template>
