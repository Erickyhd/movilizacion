<script setup>
import { ref, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Bus, Plus, Users, ShieldCheck, AlertCircle, FileCheck, Search, Edit3, Trash2, RotateCcw, X } from 'lucide-vue-next';

const props = defineProps({
  vehiculos: Array,
  conductores: Array,
  empresas: Array,
  trabajadores: Array,
});

const page = usePage();
const canWrite = computed(() => {
  const perm = page.props.auth?.user?.permisos?.flota;
  return perm === 'ESCRITURA' || page.props.auth?.user?.rol === 'ADMIN';
});

const searchQuery = ref('');
const activeTab = ref('vehiculos'); // 'vehiculos' | 'conductores'
const isVehiculoDrawerOpen = ref(false);
const isConductorDrawerOpen = ref(false);
const editingVehiculo = ref(null);
const editingConductor = ref(null);

const filteredVehiculos = computed(() => {
  return (props.vehiculos || []).filter(v => {
    const term = searchQuery.value.toLowerCase();
    return v.placa.toLowerCase().includes(term) ||
           v.marca_modelo.toLowerCase().includes(term) ||
           (v.empresa?.razon_social || '').toLowerCase().includes(term);
  });
});

const filteredConductores = computed(() => {
  return (props.conductores || []).filter(c => {
    const term = searchQuery.value.toLowerCase();
    const name = `${c.trabajador?.nombres || ''} ${c.trabajador?.apellidos || ''}`.toLowerCase();
    const lic = (c.numero_licencia || '').toLowerCase();
    return name.includes(term) || lic.includes(term);
  });
});

// Find Magori empresa or principal empresa
const magoriEmpresa = computed(() => {
  const list = props.empresas || [];
  return list.find(e => e.razon_social.toUpperCase().includes('MAGORI')) || list.find(e => !e.es_contratista) || list[0];
});

const vehiculoForm = useForm({
  empresa_id: '',
  placa: '',
  marca_modelo: '',
  capacidad_pasajeros: 45,
  soat_vencimiento: '',
  rt_vencimiento: '',
});

const conductorForm = useForm({
  trabajador_id: '',
  numero_licencia: '',
  categoria_licencia: 'A-IIIc',
  brevete_interno_vencimiento: '',
});

const openCreateVehiculoDrawer = () => {
  editingVehiculo.value = null;
  vehiculoForm.reset();
  vehiculoForm.empresa_id = magoriEmpresa.value ? magoriEmpresa.value.id : '';
  vehiculoForm.capacidad_pasajeros = 45;
  isVehiculoDrawerOpen.value = true;
};

const openEditVehiculoDrawer = (v) => {
  editingVehiculo.value = v;
  vehiculoForm.empresa_id = v.empresa_id;
  vehiculoForm.placa = v.placa;
  vehiculoForm.marca_modelo = v.marca_modelo;
  vehiculoForm.capacidad_pasajeros = v.capacidad_pasajeros;
  vehiculoForm.soat_vencimiento = v.soat_vencimiento;
  vehiculoForm.rt_vencimiento = v.rt_vencimiento;
  isVehiculoDrawerOpen.value = true;
};

const submitVehiculoForm = () => {
  if (editingVehiculo.value) {
    vehiculoForm.put(route('flota.vehiculos.update', editingVehiculo.value.id), {
      onSuccess: () => {
        vehiculoForm.reset();
        isVehiculoDrawerOpen.value = false;
        editingVehiculo.value = null;
      },
    });
  } else {
    vehiculoForm.post(route('flota.vehiculos.store'), {
      onSuccess: () => {
        vehiculoForm.reset();
        isVehiculoDrawerOpen.value = false;
      },
    });
  }
};

const toggleVehiculoEstado = (v) => {
  const accion = v.activo ? 'desactivar' : 'reactivar';
  if (confirm(`¿Confirmas que deseas ${accion} el vehículo con placa ${v.placa}?`)) {
    router.delete(route('flota.vehiculos.destroy', v.id));
  }
};

const openCreateConductorDrawer = () => {
  editingConductor.value = null;
  conductorForm.reset();
  conductorForm.categoria_licencia = 'A-IIIc';
  isConductorDrawerOpen.value = true;
};

const openEditConductorDrawer = (c) => {
  editingConductor.value = c;
  conductorForm.trabajador_id = c.trabajador_id;
  conductorForm.numero_licencia = c.numero_licencia;
  conductorForm.categoria_licencia = c.categoria_licencia;
  conductorForm.brevete_interno_vencimiento = c.brevete_interno_vencimiento;
  isConductorDrawerOpen.value = true;
};

const submitConductorForm = () => {
  if (editingConductor.value) {
    conductorForm.put(route('flota.conductores.update', editingConductor.value.id), {
      onSuccess: () => {
        conductorForm.reset();
        isConductorDrawerOpen.value = false;
        editingConductor.value = null;
      },
    });
  } else {
    conductorForm.post(route('flota.conductores.store'), {
      onSuccess: () => {
        conductorForm.reset();
        isConductorDrawerOpen.value = false;
      },
    });
  }
};

const toggleConductorEstado = (c) => {
  const accion = c.activo ? 'desactivar' : 'reactivar';
  if (confirm(`¿Confirmas que deseas ${accion} al conductor ${c.trabajador?.nombres} ${c.trabajador?.apellidos}?`)) {
    router.delete(route('flota.conductores.destroy', c.id));
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
            <Bus class="w-6 h-6 text-purple-600 mr-2.5" /> Gestión de Flota y Choferes
          </h2>
          <p class="text-sm text-slate-500 mt-1">Control de buses, minivan, SOAT, revisiones técnicas y brevete interno de choferes.</p>
        </div>
        <div v-if="canWrite" class="flex space-x-2">
          <button 
            @click="openCreateVehiculoDrawer"
            class="bg-purple-600 hover:bg-purple-500 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-md hover:shadow-purple-500/20 flex items-center space-x-2 transition cursor-pointer"
          >
            <Plus class="w-4 h-4" />
            <span>Nuevo Vehículo</span>
          </button>
          <button 
            @click="openCreateConductorDrawer"
            class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-md hover:shadow-indigo-500/20 flex items-center space-x-2 transition cursor-pointer"
          >
            <Plus class="w-4 h-4" />
            <span>Nuevo Conductor</span>
          </button>
        </div>
      </div>

      <!-- Filters & Search Bar -->
      <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
        <!-- Sub-Tabs Switcher -->
        <div class="flex bg-slate-200/70 p-1 rounded-xl w-full sm:w-auto">
          <button 
            @click="activeTab = 'vehiculos'"
            :class="['px-4 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer', activeTab === 'vehiculos' ? 'bg-white text-purple-700 shadow-sm' : 'text-slate-600 hover:text-slate-900']"
          >
            Buses / Vehículos ({{ filteredVehiculos.length }})
          </button>
          <button 
            @click="activeTab = 'conductores'"
            :class="['px-4 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer', activeTab === 'conductores' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-600 hover:text-slate-900']"
          >
            Conductores Acreditados ({{ filteredConductores.length }})
          </button>
        </div>

        <!-- Search input -->
        <div class="relative w-full sm:w-72">
          <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
          <input 
            v-model="searchQuery" 
            type="text" 
            :placeholder="activeTab === 'vehiculos' ? 'Buscar Placa, Modelo o Empresa...' : 'Buscar Conductor o Licencia...'" 
            class="w-full bg-white border border-slate-300 rounded-xl pl-9 pr-4 py-2 text-xs text-slate-900 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-purple-500 outline-none shadow-sm"
          />
        </div>
      </div>

      <!-- TAB 1: VEHÍCULOS TABLE -->
      <div v-if="activeTab === 'vehiculos'" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase border-b border-slate-100">
              <tr>
                <th class="px-6 py-3.5">Placa</th>
                <th class="px-6 py-3.5">Marca / Modelo</th>
                <th class="px-6 py-3.5">Empresa Operadora</th>
                <th class="px-6 py-3.5">Capacidad</th>
                <th class="px-6 py-3.5">Vencimiento SOAT</th>
                <th class="px-6 py-3.5">Revisión Técnica</th>
                <th v-if="canWrite" class="px-6 py-3.5 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="v in filteredVehiculos" :key="v.id" :class="['hover:bg-slate-50/80 transition', !v.activo ? 'bg-red-50/30 opacity-75' : '']">
                <td class="px-6 py-4 font-mono font-extrabold text-slate-900 text-base">
                  <span class="bg-slate-100 px-2.5 py-1 rounded-md border border-slate-200 shadow-xs">{{ v.placa }}</span>
                </td>
                <td class="px-6 py-4 font-bold text-slate-800">{{ v.marca_modelo }}</td>
                <td class="px-6 py-4 text-slate-700 font-medium">{{ v.empresa?.razon_social }}</td>
                <td class="px-6 py-4 font-bold text-purple-700">
                  {{ v.capacidad_pasajeros }} pax
                </td>
                <td class="px-6 py-4 text-xs font-semibold text-slate-600">
                  {{ v.soat_vencimiento }}
                </td>
                <td class="px-6 py-4 text-xs font-semibold text-slate-600">
                  {{ v.rt_vencimiento }}
                </td>
                <td v-if="canWrite" class="px-6 py-4 text-right space-x-1 whitespace-nowrap">
                  <button 
                    @click="openEditVehiculoDrawer(v)"
                    title="Editar vehículo"
                    class="p-1.5 text-slate-400 hover:text-purple-600 hover:bg-purple-50/80 rounded-lg transition cursor-pointer"
                  >
                    <Edit3 class="w-3.5 h-3.5" />
                  </button>
                  <button 
                    @click="toggleVehiculoEstado(v)"
                    :title="v.activo ? 'Desactivar vehículo' : 'Reactivar vehículo'"
                    :class="[
                      'p-1.5 rounded-lg transition cursor-pointer',
                      v.activo 
                        ? 'text-slate-400 hover:text-red-600 hover:bg-red-50/80' 
                        : 'text-slate-400 hover:text-emerald-600 hover:bg-emerald-50/80'
                    ]"
                  >
                    <component :is="v.activo ? Trash2 : RotateCcw" class="w-3.5 h-3.5" />
                  </button>
                </td>
              </tr>
              <tr v-if="!filteredVehiculos || filteredVehiculos.length === 0">
                <td colspan="7" class="px-6 py-8 text-center text-slate-400 text-sm">
                  No se encontraron vehículos en la búsqueda.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TAB 2: CONDUCTORES TABLE -->
      <div v-if="activeTab === 'conductores'" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase border-b border-slate-100">
              <tr>
                <th class="px-6 py-3.5">Nombre del Conductor</th>
                <th class="px-6 py-3.5">N° Licencia MTC</th>
                <th class="px-6 py-3.5">Categoría</th>
                <th class="px-6 py-3.5">Brevete Interno</th>
                <th class="px-6 py-3.5">Estado</th>
                <th v-if="canWrite" class="px-6 py-3.5 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="c in filteredConductores" :key="c.id" :class="['hover:bg-slate-50/80 transition', !c.activo ? 'bg-red-50/30 opacity-75' : '']">
                <td class="px-6 py-4 font-bold text-slate-900">
                  {{ c.trabajador?.nombres }} {{ c.trabajador?.apellidos }}
                </td>
                <td class="px-6 py-4 font-mono font-bold text-indigo-700">{{ c.numero_licencia }}</td>
                <td class="px-6 py-4 font-bold text-slate-800">{{ c.categoria_licencia }}</td>
                <td class="px-6 py-4 text-xs font-semibold text-slate-600">{{ c.brevete_interno_vencimiento }}</td>
                <td class="px-6 py-4">
                  <span v-if="c.activo" class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Habilitado</span>
                  <span v-else class="px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">Suspendido</span>
                </td>
                <td v-if="canWrite" class="px-6 py-4 text-right space-x-1 whitespace-nowrap">
                  <button 
                    @click="openEditConductorDrawer(c)"
                    title="Editar conductor"
                    class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50/80 rounded-lg transition cursor-pointer"
                  >
                    <Edit3 class="w-3.5 h-3.5" />
                  </button>
                  <button 
                    @click="toggleConductorEstado(c)"
                    :title="c.activo ? 'Desactivar conductor' : 'Reactivar conductor'"
                    :class="[
                      'p-1.5 rounded-lg transition cursor-pointer',
                      c.activo 
                        ? 'text-slate-400 hover:text-red-600 hover:bg-red-50/80' 
                        : 'text-slate-400 hover:text-emerald-600 hover:bg-emerald-50/80'
                    ]"
                  >
                    <component :is="c.activo ? Trash2 : RotateCcw" class="w-3.5 h-3.5" />
                  </button>
                </td>
              </tr>
              <tr v-if="!filteredConductores || filteredConductores.length === 0">
                <td colspan="6" class="px-6 py-8 text-center text-slate-400 text-sm">
                  No se encontraron conductores en la búsqueda.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Teleported Drawer Vehiculo -->
      <Teleport to="body">
        <div v-if="isVehiculoDrawerOpen" class="fixed inset-0 z-[9999] overflow-hidden">
          <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="isVehiculoDrawerOpen = false"></div>

          <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
            <div class="w-screen max-w-lg bg-white shadow-2xl flex flex-col transform transition duration-300 border-l border-slate-200">
              
              <div class="p-6 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 rounded-xl bg-purple-600 flex items-center justify-center text-white">
                    <Bus class="w-5 h-5" />
                  </div>
                  <div>
                    <h3 class="font-extrabold text-lg text-slate-100">
                      {{ editingVehiculo ? 'Editar Vehículo' : 'Nuevo Vehículo' }}
                    </h3>
                    <span class="text-xs text-purple-300 block">Formulario de registro de flota</span>
                  </div>
                </div>
                <button @click="isVehiculoDrawerOpen = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 cursor-pointer"><X class="w-5 h-5" /></button>
              </div>

              <form @submit.prevent="submitVehiculoForm" class="flex-1 overflow-y-auto p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Placa</label>
                    <input v-model="vehiculoForm.placa" type="text" maxlength="10" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm font-semibold focus:ring-2 focus:ring-purple-500 outline-none font-mono" placeholder="F1A-892" />
                  </div>
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Empresa Titular</label>
                    <select v-model="vehiculoForm.empresa_id" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm font-bold focus:ring-2 focus:ring-purple-500 outline-none bg-white">
                      <option value="" disabled>Seleccione Empresa</option>
                      <option v-for="e in empresas" :key="e.id" :value="e.id">{{ e.razon_social }}</option>
                    </select>
                  </div>
                  <div class="col-span-2">
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Marca y Modelo</label>
                    <input v-model="vehiculoForm.marca_modelo" type="text" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm font-semibold focus:ring-2 focus:ring-purple-500 outline-none" placeholder="Volvo Bus B450R 6x2" />
                  </div>
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Capacidad Pasajeros (Default 45)</label>
                    <input v-model="vehiculoForm.capacidad_pasajeros" type="number" min="1" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm font-bold focus:ring-2 focus:ring-purple-500 outline-none" placeholder="45" />
                  </div>
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Vencimiento SOAT</label>
                    <input v-model="vehiculoForm.soat_vencimiento" type="date" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm font-semibold focus:ring-2 focus:ring-purple-500 outline-none" />
                  </div>
                  <div class="col-span-2">
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Vencimiento Revisión Técnica</label>
                    <input v-model="vehiculoForm.rt_vencimiento" type="date" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm font-semibold focus:ring-2 focus:ring-purple-500 outline-none" />
                  </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                  <button type="button" @click="isVehiculoDrawerOpen = false" class="px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancelar</button>
                  <button type="submit" :disabled="vehiculoForm.processing" class="px-5 py-2.5 text-sm bg-purple-600 text-white font-bold rounded-xl hover:bg-purple-500 shadow-md">
                    {{ editingVehiculo ? 'Guardar Cambios' : 'Registrar Vehículo' }}
                  </button>
                </div>
              </form>

            </div>
          </div>
        </div>
      </Teleport>

      <!-- Teleported Drawer Conductor -->
      <Teleport to="body">
        <div v-if="isConductorDrawerOpen" class="fixed inset-0 z-[9999] overflow-hidden">
          <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="isConductorDrawerOpen = false"></div>

          <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
            <div class="w-screen max-w-md bg-white shadow-2xl flex flex-col transform transition duration-300 border-l border-slate-200">
              
              <div class="p-6 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white">
                    <Users class="w-5 h-5" />
                  </div>
                  <div>
                    <h3 class="font-extrabold text-lg text-slate-100">
                      {{ editingConductor ? 'Editar Conductor' : 'Nuevo Conductor' }}
                    </h3>
                    <span class="text-xs text-indigo-300 block">Formulario de registro MTC</span>
                  </div>
                </div>
                <button @click="isConductorDrawerOpen = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 cursor-pointer"><X class="w-5 h-5" /></button>
              </div>

              <form @submit.prevent="submitConductorForm" class="flex-1 overflow-y-auto p-6 space-y-4">
                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Trabajador Acreditado</label>
                  <select v-model="conductorForm.trabajador_id" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                    <option value="" disabled>Seleccione Conductor</option>
                    <option v-for="t in trabajadores" :key="t.id" :value="t.id">{{ t.nombres }} {{ t.apellidos }} (DNI: {{ t.dni }})</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Número de Licencia MTC</label>
                  <input v-model="conductorForm.numero_licencia" type="text" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-indigo-500 outline-none font-mono" placeholder="Q-74567890" />
                </div>
                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Categoría Licencia</label>
                  <input v-model="conductorForm.categoria_licencia" type="text" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="A-IIIc" />
                </div>
                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Vencimiento Brevete Interno</label>
                  <input v-model="conductorForm.brevete_interno_vencimiento" type="date" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-indigo-500 outline-none" />
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                  <button type="button" @click="isConductorDrawerOpen = false" class="px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancelar</button>
                  <button type="submit" :disabled="conductorForm.processing" class="px-5 py-2.5 text-sm bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-500 shadow-md">
                    {{ editingConductor ? 'Guardar Cambios' : 'Registrar Conductor' }}
                  </button>
                </div>
              </form>

            </div>
          </div>
        </div>
      </Teleport>

    </div>
  </AppLayout>
</template>