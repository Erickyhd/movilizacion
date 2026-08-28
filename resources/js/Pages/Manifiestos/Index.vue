<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FileText, Plus, Users, ArrowRight, CheckCircle2, Clock, Search, Trash2, X } from 'lucide-vue-next';

const props = defineProps({
  manifiestos: Array,
  rutas: Array,
  vehiculos: Array,
  conductores: Array,
  trabajadores: Array,
});

const page = usePage();
const canWrite = computed(() => {
  const perm = page.props.auth?.user?.permisos?.manifiestos;
  return perm === 'ESCRITURA' || page.props.auth?.user?.rol === 'ADMIN';
});

const searchQuery = ref('');
const showModal = ref(false);
const selectedManifiesto = ref(null);

const origenSeleccionado = ref('');
const destinoSeleccionado = ref('');

// Computed list of unique origins
const origenesDisponibles = computed(() => {
  const list = (props.rutas || []).map(r => r.origen);
  return [...new Set(list)];
});

// Computed list of destinations matching selected origin
const destinosDisponibles = computed(() => {
  if (!origenSeleccionado.value) return [];
  const list = (props.rutas || [])
    .filter(r => r.origen === origenSeleccionado.value)
    .map(r => r.destino);
  return [...new Set(list)];
});

const filteredManifiestos = computed(() => {
  return (props.manifiestos || []).filter(m => {
    const term = searchQuery.value.toLowerCase();
    const codigo = m.codigo_manifiesto.toLowerCase();
    const origen = (m.ruta?.origen || '').toLowerCase();
    const destino = (m.ruta?.destino || '').toLowerCase();
    const placa = (m.vehiculo?.placa || '').toLowerCase();
    const conductor = `${m.conductor?.trabajador?.nombres || ''} ${m.conductor?.trabajador?.apellidos || ''}`.toLowerCase();

    return codigo.includes(term) || origen.includes(term) || destino.includes(term) || placa.includes(term) || conductor.includes(term);
  });
});

const form = useForm({
  ruta_id: '',
  vehiculo_id: '',
  conductor_id: '',
  fecha_salida_programada: '',
  pasajeros: [],
});

// Auto sync form.ruta_id when origin and destination change
watch([origenSeleccionado, destinoSeleccionado], ([origen, destino]) => {
  if (origen && destino) {
    const rutaEncontrada = (props.rutas || []).find(r => r.origen === origen && r.destino === destino);
    if (rutaEncontrada) {
      form.ruta_id = rutaEncontrada.id;
    } else {
      form.ruta_id = '';
    }
  } else {
    form.ruta_id = '';
  }
});

const openModal = () => {
  form.reset();
  origenSeleccionado.value = '';
  destinoSeleccionado.value = '';
  showModal.value = true;
};

const submit = () => {
  form.post(route('manifiestos.store'), {
    onSuccess: () => {
      form.reset();
      origenSeleccionado.value = '';
      destinoSeleccionado.value = '';
      showModal.value = false;
    },
  });
};

const cambiarEstado = (m, nuevoEstado) => {
  router.put(route('manifiestos.estado', m.id), { estado: nuevoEstado });
};

const cancelarManifiesto = (m) => {
  if (confirm(`¿Confirmas que deseas cancelar el manifiesto ${m.codigo_manifiesto}?`)) {
    router.delete(route('manifiestos.destroy', m.id));
  }
};

const getStatusBadge = (estado) => {
  switch (estado) {
    case 'BORRADOR': return 'bg-slate-100 text-slate-700 border-slate-200';
    case 'CONFIRMADO': return 'bg-blue-100 text-blue-800 border-blue-200';
    case 'EN_GARITA': return 'bg-amber-100 text-amber-800 border-amber-200';
    case 'EN_RUTA': return 'bg-purple-100 text-purple-800 border-purple-200';
    case 'FINALIZADO': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
    case 'CANCELADO': return 'bg-red-100 text-red-800 border-red-200';
    default: return 'bg-slate-100 text-slate-700';
  }
};
</script>

<template>
  <AppLayout>
    <div class="w-full space-y-6">
      
      <!-- Top Banner & Actions -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
          <h2 class="text-xl font-extrabold text-slate-900 flex items-center">
            <FileText class="w-6 h-6 text-teal-600 mr-2.5" /> Manifiestos de Traslado de Personal
          </h2>
          <p class="text-sm text-slate-500 mt-1">Generación de guías de despacho, asignación de asientos y control de garita.</p>
        </div>
        <button 
          v-if="canWrite"
          @click="openModal"
          class="bg-teal-600 hover:bg-teal-500 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-md hover:shadow-teal-500/20 flex items-center space-x-2 transition cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>Generar Manifiesto</span>
        </button>
      </div>

      <!-- Search Bar -->
      <div class="flex justify-between items-center">
        <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">
          Total Manifiestos: {{ filteredManifiestos.length }}
        </div>
        <div class="relative w-full sm:w-72">
          <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Buscar Código, Ruta, Bus o Conductor..." 
            class="w-full bg-white border border-slate-300 rounded-xl pl-9 pr-4 py-2 text-xs text-slate-900 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-teal-500 outline-none shadow-sm"
          />
        </div>
      </div>

      <!-- Manifiestos Table -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase border-b border-slate-100">
              <tr>
                <th class="px-6 py-3.5">Código</th>
                <th class="px-6 py-3.5">Ruta</th>
                <th class="px-6 py-3.5">Vehículo / Conductor</th>
                <th class="px-6 py-3.5">Pasajeros</th>
                <th class="px-6 py-3.5">Fecha Programada</th>
                <th class="px-6 py-3.5">Estado</th>
                <th class="px-6 py-3.5 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="m in filteredManifiestos" :key="m.id" :class="['hover:bg-slate-50/80 transition', m.estado === 'CANCELADO' ? 'bg-red-50/30 opacity-75' : '']">
                <td class="px-6 py-4 font-mono font-extrabold text-teal-700">{{ m.codigo_manifiesto }}</td>
                <td class="px-6 py-4">
                  <span class="font-bold text-slate-800 block">{{ m.ruta?.origen }} ➔ {{ m.ruta?.destino }}</span>
                </td>
                <td class="px-6 py-4">
                  <span class="font-bold text-slate-900 block">{{ m.vehiculo?.placa }}</span>
                  <span class="text-xs text-slate-500 block">{{ m.conductor?.trabajador?.nombres }} {{ m.conductor?.trabajador?.apellidos }}</span>
                </td>
                <td class="px-6 py-4 font-bold text-blue-600">
                  {{ m.detalles?.length || 0 }} asignados
                </td>
                <td class="px-6 py-4 text-xs font-semibold text-slate-600">
                  {{ new Date(m.fecha_salida_programada).toLocaleString('es-PE') }}
                </td>
                <td class="px-6 py-4">
                  <span :class="['px-2.5 py-1 rounded-full text-xs font-bold border', getStatusBadge(m.estado)]">
                    {{ m.estado }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right space-x-1.5 whitespace-nowrap">
                  <button 
                    @click="selectedManifiesto = m"
                    class="px-3 py-1.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition cursor-pointer"
                  >
                    Ver Pasajeros
                  </button>
                  <select 
                    v-if="canWrite"
                    :value="m.estado" 
                    @change="e => cambiarEstado(m, e.target.value)"
                    class="text-xs font-bold bg-white border border-slate-300 rounded-xl px-2.5 py-1.5 focus:ring-2 focus:ring-teal-500 outline-none cursor-pointer"
                  >
                    <option value="BORRADOR">BORRADOR</option>
                    <option value="CONFIRMADO">CONFIRMADO</option>
                    <option value="EN_GARITA">EN GARITA</option>
                    <option value="EN_RUTA">EN RUTA</option>
                    <option value="FINALIZADO">FINALIZADO</option>
                    <option value="CANCELADO">CANCELADO</option>
                  </select>
                  <button 
                    v-if="canWrite && m.estado !== 'CANCELADO'"
                    @click="cancelarManifiesto(m)"
                    title="Cancelar manifiesto"
                    class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50/80 rounded-lg transition cursor-pointer inline-flex items-center"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </td>
              </tr>
              <tr v-if="!filteredManifiestos || filteredManifiestos.length === 0">
                <td colspan="7" class="px-6 py-8 text-center text-slate-400 text-sm">
                  No se encontraron manifiestos en la búsqueda.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Teleported Modal Nuevo Manifiesto -->
      <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 z-[9999] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
          <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl space-y-4 border border-slate-200">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <h3 class="font-extrabold text-slate-900 text-lg">Generar Manifiesto de Traslado</h3>
              <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer"><X class="w-5 h-5" /></button>
            </div>
            <form @submit.prevent="submit" class="space-y-4">
              
              <!-- 2 Separate Selects for Origin and Destination -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Punto Origen</label>
                  <select v-model="origenSeleccionado" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-teal-500 outline-none bg-white">
                    <option value="" disabled>Seleccione Origen</option>
                    <option v-for="orig in origenesDisponibles" :key="orig" :value="orig">{{ orig }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Punto Destino</label>
                  <select v-model="destinoSeleccionado" :disabled="!origenSeleccionado" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-teal-500 outline-none bg-white disabled:bg-slate-100 disabled:text-slate-400">
                    <option value="" disabled>{{ origenSeleccionado ? 'Seleccione Destino' : 'Primero elija Origen' }}</option>
                    <option v-for="dest in destinosDisponibles" :key="dest" :value="dest">{{ dest }}</option>
                  </select>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Vehículo / Bus</label>
                  <select v-model="form.vehiculo_id" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-teal-500 outline-none bg-white">
                    <option value="" disabled>Seleccione Bus</option>
                    <option v-for="v in vehiculos" :key="v.id" :value="v.id">{{ v.placa }} - {{ v.marca_modelo }} (Cap: {{ v.capacidad_pasajeros }})</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Conductor Asignado</label>
                  <select v-model="form.conductor_id" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-teal-500 outline-none bg-white">
                    <option value="" disabled>Seleccione Conductor</option>
                    <option v-for="c in conductores" :key="c.id" :value="c.id">{{ c.trabajador?.nombres }} {{ c.trabajador?.apellidos }} ({{ c.categoria_licencia }})</option>
                  </select>
                </div>
              </div>

              <div>
                <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Fecha y Hora Programada</label>
                <input v-model="form.fecha_salida_programada" type="datetime-local" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-teal-500 outline-none" />
              </div>

              <div>
                <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Seleccionar Pasajeros Acreditados (HSEQ APTO)</label>
                <div class="max-h-40 overflow-y-auto border border-slate-200 rounded-xl p-2 space-y-1">
                  <label v-for="t in trabajadores" :key="t.id" class="flex items-center space-x-2 text-xs p-1.5 hover:bg-slate-50 rounded-lg cursor-pointer">
                    <input type="checkbox" :value="t.id" v-model="form.pasajeros" class="rounded text-teal-600 border-slate-300 cursor-pointer" />
                    <span class="font-bold text-slate-800">{{ t.nombres }} {{ t.apellidos }}</span>
                    <span class="text-slate-400 font-mono">DNI: {{ t.dni }}</span>
                  </label>
                </div>
              </div>

              <div class="flex justify-end space-x-3 pt-3 border-t border-slate-100">
                <button type="button" @click="showModal = false" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancelar</button>
                <button type="submit" :disabled="form.processing || !form.ruta_id" class="px-5 py-2 text-sm bg-teal-600 text-white font-bold rounded-xl hover:bg-teal-500 shadow-md disabled:opacity-50">Generar Manifiesto</button>
              </div>
            </form>
          </div>
        </div>
      </Teleport>

      <!-- Teleported Modal Detalle Pasajeros -->
      <Teleport to="body">
        <div v-if="selectedManifiesto" class="fixed inset-0 z-[9999] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
          <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 border border-slate-200">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <div>
                <h3 class="font-extrabold text-slate-900 text-lg">Pasajeros: {{ selectedManifiesto.codigo_manifiesto }}</h3>
                <p class="text-xs text-slate-500">{{ selectedManifiesto.ruta?.origen }} ➔ {{ selectedManifiesto.ruta?.destino }}</p>
              </div>
              <button @click="selectedManifiesto = null" class="text-slate-400 hover:text-slate-600 cursor-pointer"><X class="w-5 h-5" /></button>
            </div>
            <div class="max-h-60 overflow-y-auto divide-y divide-slate-100">
              <div v-for="d in selectedManifiesto.detalles" :key="d.id" class="py-2.5 flex items-center justify-between">
                <div>
                  <span class="font-bold text-slate-900 text-sm block">Asiento {{ d.numero_asiento }}: {{ d.trabajador?.nombres }} {{ d.trabajador?.apellidos }}</span>
                  <span class="text-xs text-slate-500 font-mono">DNI: {{ d.trabajador?.dni }}</span>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 border border-slate-200">{{ d.estado_embarque }}</span>
              </div>
              <div v-if="!selectedManifiesto.detalles || selectedManifiesto.detalles.length === 0" class="py-4 text-center text-xs text-slate-400">
                No hay pasajeros asignados a este manifiesto.
              </div>
            </div>
          </div>
        </div>
      </Teleport>

    </div>
  </AppLayout>
</template>