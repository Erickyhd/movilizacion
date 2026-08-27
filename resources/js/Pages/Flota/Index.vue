<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Bus, User, Plus, ShieldCheck, AlertCircle } from 'lucide-vue-next';

defineProps({
  vehiculos: Array,
  conductores: Array,
  empresas: Array,
  trabajadores: Array,
});

const activeTab = ref('vehiculos');
const showVehiculoModal = ref(false);
const showConductorModal = ref(false);

const vehiculoForm = useForm({
  empresa_id: '',
  placa: '',
  marca_modelo: '',
  capacidad_pasajeros: 40,
  soat_vencimiento: '',
  rt_vencimiento: '',
});

const conductorForm = useForm({
  trabajador_id: '',
  numero_licencia: '',
  categoria_licencia: 'A-IIIc',
  brevete_interno_vencimiento: '',
});

const submitVehiculo = () => {
  vehiculoForm.post(route('flota.vehiculos.store'), {
    onSuccess: () => {
      vehiculoForm.reset();
      showVehiculoModal.value = false;
    },
  });
};

const submitConductor = () => {
  conductorForm.post(route('flota.conductores.store'), {
    onSuccess: () => {
      conductorForm.reset();
      showConductorModal.value = false;
    },
  });
};
</script>

<template>
  <AppLayout>
    <div class="w-full space-y-6">
      
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
          <h2 class="text-xl font-bold text-slate-900 flex items-center">
            <Bus class="w-6 h-6 text-purple-600 mr-2" /> Flota y Conductores
          </h2>
          <p class="text-sm text-slate-500 mt-1">Control de unidades de transporte, vencimiento SOAT/RT y brevete de conductores.</p>
        </div>
        <div class="flex space-x-2">
          <button 
            @click="showVehiculoModal = true"
            class="bg-purple-600 hover:bg-purple-500 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm flex items-center space-x-2 transition"
          >
            <Plus class="w-4 h-4" />
            <span>Nuevo Vehículo</span>
          </button>
          <button 
            @click="showConductorModal = true"
            class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm flex items-center space-x-2 transition"
          >
            <Plus class="w-4 h-4" />
            <span>Nuevo Conductor</span>
          </button>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="flex border-b border-slate-200 space-x-6">
        <button 
          @click="activeTab = 'vehiculos'"
          :class="['pb-3 font-semibold text-sm border-b-2 transition', activeTab === 'vehiculos' ? 'border-purple-600 text-purple-600' : 'border-transparent text-slate-500 hover:text-slate-700']"
        >
          Vehículos / Buses ({{ vehiculos.length }})
        </button>
        <button 
          @click="activeTab = 'conductores'"
          :class="['pb-3 font-semibold text-sm border-b-2 transition', activeTab === 'conductores' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700']"
        >
          Conductores Registrados ({{ conductores.length }})
        </button>
      </div>

      <!-- Vehiculos Table -->
      <div v-if="activeTab === 'vehiculos'" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase border-b border-slate-100">
              <tr>
                <th class="px-6 py-3">Placa</th>
                <th class="px-6 py-3">Marca y Modelo</th>
                <th class="px-6 py-3">Capacidad</th>
                <th class="px-6 py-3">Venc. SOAT</th>
                <th class="px-6 py-3">Venc. RT</th>
                <th class="px-6 py-3">Empresa</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="v in vehiculos" :key="v.id" class="hover:bg-slate-50/80 transition">
                <td class="px-6 py-4 font-mono font-bold text-slate-900 bg-slate-50/50">{{ v.placa }}</td>
                <td class="px-6 py-4 font-semibold text-slate-800">{{ v.marca_modelo }}</td>
                <td class="px-6 py-4 font-bold text-purple-700">{{ v.capacidad_pasajeros }} asientos</td>
                <td class="px-6 py-4 text-xs font-medium text-slate-700">{{ v.soat_vencimiento }}</td>
                <td class="px-6 py-4 text-xs font-medium text-slate-700">{{ v.rt_vencimiento }}</td>
                <td class="px-6 py-4 text-slate-600">{{ v.empresa?.razon_social }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Conductores Table -->
      <div v-if="activeTab === 'conductores'" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase border-b border-slate-100">
              <tr>
                <th class="px-6 py-3">Nombre del Conductor</th>
                <th class="px-6 py-3">Nº Licencia MTC</th>
                <th class="px-6 py-3">Categoría</th>
                <th class="px-6 py-3">Brevete Interno</th>
                <th class="px-6 py-3">Estado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="c in conductores" :key="c.id" class="hover:bg-slate-50/80 transition">
                <td class="px-6 py-4 font-semibold text-slate-900">
                  {{ c.trabajador?.nombres }} {{ c.trabajador?.apellidos }}
                </td>
                <td class="px-6 py-4 font-mono font-bold text-indigo-700">{{ c.numero_licencia }}</td>
                <td class="px-6 py-4 font-bold text-slate-800">{{ c.categoria_licencia }}</td>
                <td class="px-6 py-4 text-xs text-slate-600">{{ c.brevete_interno_vencimiento }}</td>
                <td class="px-6 py-4">
                  <span v-if="c.activo" class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Habilitado</span>
                  <span v-else class="px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">Suspendido</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal Vehiculo -->
      <div v-if="showVehiculoModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="font-bold text-slate-900 text-lg">Registrar Vehículo</h3>
            <button @click="showVehiculoModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
          </div>
          <form @submit.prevent="submitVehiculo" class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Placa</label>
              <input v-model="vehiculoForm.placa" type="text" maxlength="10" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 outline-none font-mono" placeholder="F1A-892" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Empresa</label>
              <select v-model="vehiculoForm.empresa_id" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 outline-none bg-white">
                <option value="" disabled>Seleccione Empresa</option>
                <option v-for="e in empresas" :key="e.id" :value="e.id">{{ e.razon_social }}</option>
              </select>
            </div>
            <div class="col-span-2">
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Marca y Modelo</label>
              <input v-model="vehiculoForm.marca_modelo" type="text" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 outline-none" placeholder="Volvo Bus B450R 6x2" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Capacidad Pasajeros</label>
              <input v-model="vehiculoForm.capacidad_pasajeros" type="number" min="1" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 outline-none" placeholder="45" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Vencimiento SOAT</label>
              <input v-model="vehiculoForm.soat_vencimiento" type="date" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 outline-none" />
            </div>
            <div class="col-span-2">
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Vencimiento Revisión Técnica</label>
              <input v-model="vehiculoForm.rt_vencimiento" type="date" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 outline-none" />
            </div>
            <div class="col-span-2 flex justify-end space-x-2 pt-3 border-t border-slate-100">
              <button type="button" @click="showVehiculoModal = false" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg">Cancelar</button>
              <button type="submit" :disabled="vehiculoForm.processing" class="px-4 py-2 text-sm bg-purple-600 text-white font-semibold rounded-lg hover:bg-purple-500">Guardar</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal Conductor -->
      <div v-if="showConductorModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="font-bold text-slate-900 text-lg">Registrar Conductor</h3>
            <button @click="showConductorModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
          </div>
          <form @submit.prevent="submitConductor" class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Trabajador Acreditado</label>
              <select v-model="conductorForm.trabajador_id" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                <option value="" disabled>Seleccione Conductor</option>
                <option v-for="t in trabajadores" :key="t.id" :value="t.id">{{ t.nombres }} {{ t.apellidos }} (DNI: {{ t.dni }})</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Número de Licencia MTC</label>
              <input v-model="conductorForm.numero_licencia" type="text" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none font-mono" placeholder="Q-74567890" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Categoría Licencia</label>
              <input v-model="conductorForm.categoria_licencia" type="text" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="A-IIIc" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Vencimiento Brevete Interno</label>
              <input v-model="conductorForm.brevete_interno_vencimiento" type="date" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" />
            </div>
            <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100">
              <button type="button" @click="showConductorModal = false" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg">Cancelar</button>
              <button type="submit" :disabled="conductorForm.processing" class="px-4 py-2 text-sm bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-500">Guardar Conductor</button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </AppLayout>
</template>