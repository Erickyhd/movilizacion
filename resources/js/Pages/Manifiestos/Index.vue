<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FileText, Plus, QrCode, CheckCircle2, AlertCircle, Bus } from 'lucide-vue-next';

defineProps({
  manifiestos: Array,
  rutas: Array,
  vehiculos: Array,
  conductores: Array,
  trabajadores: Array,
});

const showModal = ref(false);
const selectedManifiesto = ref(null);

const form = useForm({
  ruta_id: '',
  vehiculo_id: '',
  conductor_id: '',
  fecha_salida_programada: '',
  pasajeros: [],
});

const submit = () => {
  form.post(route('manifiestos.store'), {
    onSuccess: () => {
      form.reset();
      showModal.value = false;
    },
  });
};

const cambiarEstado = (m, nuevoEstado) => {
  router.put(route('manifiestos.estado', m.id), { estado: nuevoEstado });
};

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
    <div class="max-w-7xl mx-auto space-y-6">
      
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
          <h2 class="text-xl font-bold text-slate-900 flex items-center">
            <FileText class="w-6 h-6 text-blue-600 mr-2" /> Manifiestos de Traslado y Pasajeros
          </h2>
          <p class="text-sm text-slate-500 mt-1">Generación de listas de embarque, asignación de asientos y control de garita.</p>
        </div>
        <button 
          @click="showModal = true"
          class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm flex items-center space-x-2 transition"
        >
          <Plus class="w-4 h-4" />
          <span>Nuevo Manifiesto</span>
        </button>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase border-b border-slate-100">
              <tr>
                <th class="px-6 py-3">Código</th>
                <th class="px-6 py-3">Ruta</th>
                <th class="px-6 py-3">Vehículo & Conductor</th>
                <th class="px-6 py-3">Pasajeros</th>
                <th class="px-6 py-3">Fecha Salida</th>
                <th class="px-6 py-3">Estado</th>
                <th class="px-6 py-3 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="m in manifiestos" :key="m.id" class="hover:bg-slate-50/80 transition">
                <td class="px-6 py-4 font-mono font-bold text-slate-900">{{ m.codigo_manifiesto }}</td>
                <td class="px-6 py-4">
                  <span class="font-semibold text-slate-800 block">{{ m.ruta?.origen }} ➔</span>
                  <span class="text-xs text-slate-500">{{ m.ruta?.destino }}</span>
                </td>
                <td class="px-6 py-4">
                  <span class="font-bold text-slate-900 block">{{ m.vehiculo?.placa }}</span>
                  <span class="text-xs text-slate-500 block">{{ m.conductor?.trabajador?.nombres }} {{ m.conductor?.trabajador?.apellidos }}</span>
                </td>
                <td class="px-6 py-4 font-bold text-blue-600">
                  {{ m.detalles?.length || 0 }} asignados
                </td>
                <td class="px-6 py-4 text-xs text-slate-500">
                  {{ new Date(m.fecha_salida_programada).toLocaleString('es-PE') }}
                </td>
                <td class="px-6 py-4">
                  <span :class="['px-2.5 py-1 rounded-full text-xs font-bold border', getStatusBadge(m.estado)]">
                    {{ m.estado }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right space-x-1">
                  <button 
                    @click="selectedManifiesto = m"
                    class="px-2.5 py-1 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg"
                  >
                    Ver Pasajeros
                  </button>
                  <select 
                    :value="m.estado" 
                    @change="e => cambiarEstado(m, e.target.value)"
                    class="text-xs font-semibold bg-white border border-slate-300 rounded-lg px-2 py-1 focus:ring-1 focus:ring-blue-500 outline-none"
                  >
                    <option value="BORRADOR">BORRADOR</option>
                    <option value="CONFIRMADO">CONFIRMADO</option>
                    <option value="EN_GARITA">EN GARITA</option>
                    <option value="EN_RUTA">EN RUTA</option>
                    <option value="FINALIZADO">FINALIZADO</option>
                    <option value="CANCELADO">CANCELADO</option>
                  </select>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal Nuevo Manifiesto -->
      <div v-if="showModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="font-bold text-slate-900 text-lg">Generar Manifiesto de Traslado</h3>
            <button @click="showModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
          </div>
          <form @submit.prevent="submit" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Ruta</label>
                <select v-model="form.ruta_id" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                  <option value="" disabled>Seleccione Ruta</option>
                  <option v-for="r in rutas" :key="r.id" :value="r.id">{{ r.origen }} ➔ {{ r.destino }}</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Vehículo / Bus</label>
                <select v-model="form.vehiculo_id" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                  <option value="" disabled>Seleccione Bus</option>
                  <option v-for="v in vehiculos" :key="v.id" :value="v.id">{{ v.placa }} - {{ v.marca_modelo }} (Cap: {{ v.capacidad_pasajeros }})</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Conductor Asignado</label>
                <select v-model="form.conductor_id" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                  <option value="" disabled>Seleccione Conductor</option>
                  <option v-for="c in conductores" :key="c.id" :value="c.id">{{ c.trabajador?.nombres }} {{ c.trabajador?.apellidos }} ({{ c.categoria_licencia }})</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Fecha y Hora Programada</label>
                <input v-model="form.fecha_salida_programada" type="datetime-local" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Seleccionar Pasajeros Acreditados (HSEQ APTO)</label>
              <div class="max-h-40 overflow-y-auto border border-slate-200 rounded-lg p-2 space-y-1">
                <label v-for="t in trabajadores" :key="t.id" class="flex items-center space-x-2 text-xs p-1.5 hover:bg-slate-50 rounded cursor-pointer">
                  <input type="checkbox" :value="t.id" v-model="form.pasajeros" class="rounded text-blue-600 border-slate-300" />
                  <span class="font-semibold text-slate-800">{{ t.nombres }} {{ t.apellidos }}</span>
                  <span class="text-slate-400 font-mono">DNI: {{ t.dni }}</span>
                </label>
              </div>
            </div>

            <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100">
              <button type="button" @click="showModal = false" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg">Cancelar</button>
              <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-500">Generar Manifiesto</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal Detalle Pasajeros -->
      <div v-if="selectedManifiesto" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
              <h3 class="font-bold text-slate-900 text-lg">Pasajeros: {{ selectedManifiesto.codigo_manifiesto }}</h3>
              <p class="text-xs text-slate-500">{{ selectedManifiesto.ruta?.origen }} ➔ {{ selectedManifiesto.ruta?.destino }}</p>
            </div>
            <button @click="selectedManifiesto = null" class="text-slate-400 hover:text-slate-600">✕</button>
          </div>
          <div class="max-h-60 overflow-y-auto divide-y divide-slate-100">
            <div v-for="d in selectedManifiesto.detalles" :key="d.id" class="py-2.5 flex items-center justify-between">
              <div>
                <span class="font-bold text-slate-900 text-sm block">Asiento {{ d.numero_asiento }}: {{ d.trabajador?.nombres }} {{ d.trabajador?.apellidos }}</span>
                <span class="text-xs text-slate-500 font-mono">DNI: {{ d.trabajador?.dni }}</span>
              </div>
              <span class="text-xs font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700">{{ d.estado_embarque }}</span>
            </div>
            <div v-if="!selectedManifiesto.detalles || selectedManifiesto.detalles.length === 0" class="py-4 text-center text-xs text-slate-400">
              No hay pasajeros asignados a este manifiesto.
            </div>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>