<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
  Users, 
  UserCheck, 
  FileText, 
  Bus, 
  Building2, 
  ArrowUpRight, 
  Clock, 
  CheckCircle2, 
  AlertCircle 
} from 'lucide-vue-next';

defineProps({
  stats: Object,
  manifiestos_recientes: Array,
});

const getStatusBadge = (estado) => {
  switch (estado) {
    case 'CONFIRMADO': return 'bg-blue-100 text-blue-800 border-blue-200';
    case 'EN_GARITA': return 'bg-amber-100 text-amber-800 border-amber-200';
    case 'EN_RUTA': return 'bg-purple-100 text-purple-800 border-purple-200';
    case 'FINALIZADO': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
    case 'CANCELADO': return 'bg-red-100 text-red-800 border-red-200';
    default: return 'bg-slate-100 text-slate-700 border-slate-200';
  }
};
</script>

<template>
  <AppLayout>
    <div class="w-full space-y-6">
      
      <!-- Mockup Welcome Card -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/80 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center space-x-3 mb-2">
            <h2 class="text-xl font-bold text-slate-900">Bienvenido al Panel de Control</h2>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 border border-emerald-200">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
              En Línea
            </span>
          </div>
          <p class="text-slate-600 text-sm max-w-2xl">
            Seleccione un módulo en la barra lateral para gestionar la información de viajes y acreditaciones.
          </p>
        </div>
      </div>

      <!-- KPI Stat Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <div class="bg-white rounded-xl p-5 border border-slate-200/80 shadow-sm flex items-center justify-between">
          <div>
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Trabajadores</span>
            <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ stats.total_trabajadores }}</div>
            <span class="text-xs text-emerald-600 font-medium mt-1 inline-flex items-center">
              <UserCheck class="w-3.5 h-3.5 mr-1" /> {{ stats.trabajadores_aptos }} Aptos HSEQ
            </span>
          </div>
          <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
            <Users class="w-6 h-6" />
          </div>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200/80 shadow-sm flex items-center justify-between">
          <div>
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Manifiestos Activos</span>
            <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ stats.manifiestos_activos }}</div>
            <span class="text-xs text-blue-600 font-medium mt-1 inline-flex items-center">
              En tránsito / Confirmados
            </span>
          </div>
          <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
            <FileText class="w-6 h-6" />
          </div>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200/80 shadow-sm flex items-center justify-between">
          <div>
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Vehículos Operativos</span>
            <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ stats.vehiculos_activos }}</div>
            <span class="text-xs text-slate-500 font-medium mt-1">Con SOAT y RT Vigente</span>
          </div>
          <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
            <Bus class="w-6 h-6" />
          </div>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200/80 shadow-sm flex items-center justify-between">
          <div>
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Empresas / Contratistas</span>
            <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ stats.empresas_total }}</div>
            <span class="text-xs text-slate-500 font-medium mt-1">Registradas</span>
          </div>
          <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
            <Building2 class="w-6 h-6" />
          </div>
        </div>

      </div>

      <!-- Recent Manifests Table -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
          <h3 class="font-bold text-slate-900 text-base">Últimos Manifiestos de Traslado</h3>
          <Link :href="route('manifiestos.index')" class="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center">
            Ver Todos <ArrowUpRight class="w-4 h-4 ml-1" />
          </Link>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase border-b border-slate-100">
              <tr>
                <th class="px-6 py-3">Código</th>
                <th class="px-6 py-3">Ruta</th>
                <th class="px-6 py-3">Vehículo / Placa</th>
                <th class="px-6 py-3">Conductor</th>
                <th class="px-6 py-3">Fecha Salida</th>
                <th class="px-6 py-3">Estado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="m in manifiestos_recientes" :key="m.id" class="hover:bg-slate-50/80 transition">
                <td class="px-6 py-4 font-bold text-slate-900">{{ m.codigo_manifiesto }}</td>
                <td class="px-6 py-4">
                  <span class="font-medium text-slate-800">{{ m.ruta?.origen }}</span>
                  <span class="text-slate-400 mx-1">➔</span>
                  <span class="font-medium text-slate-800">{{ m.ruta?.destino }}</span>
                </td>
                <td class="px-6 py-4">
                  <span class="font-semibold text-slate-900">{{ m.vehiculo?.placa }}</span>
                  <span class="text-xs text-slate-500 block">{{ m.vehiculo?.marca_modelo }}</span>
                </td>
                <td class="px-6 py-4">
                  {{ m.conductor?.trabajador?.nombres }} {{ m.conductor?.trabajador?.apellidos }}
                </td>
                <td class="px-6 py-4 text-xs text-slate-500">
                  {{ new Date(m.fecha_salida_programada).toLocaleString('es-PE') }}
                </td>
                <td class="px-6 py-4">
                  <span :class="['px-2.5 py-1 rounded-full text-xs font-bold border', getStatusBadge(m.estado)]">
                    {{ m.estado }}
                  </span>
                </td>
              </tr>
              <tr v-if="!manifiestos_recientes || manifiestos_recientes.length === 0">
                <td colspan="6" class="px-6 py-8 text-center text-slate-400 text-sm">
                  No hay manifiestos registrados recientemente.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </AppLayout>
</template>