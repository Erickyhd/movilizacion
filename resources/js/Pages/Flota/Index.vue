<script setup>
import { ref, computed, watch } from 'vue';
import TablePagination from '@/Components/TablePagination.vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { 
  Bus, 
  Users, 
  Plus, 
  Search, 
  Edit3, 
  Trash2, 
  RotateCcw, 
  X, 
  ShieldCheck, 
  Calendar,
  AlertTriangle,
  Clock,
  ShieldAlert
} from 'lucide-vue-next';

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

const categoriasMtc = ['A-I', 'A-IIa', 'A-IIb', 'A-IIIa', 'A-IIIb', 'A-IIIc'];

const activeTab = ref('vehiculos'); // 'vehiculos' | 'conductores'
const searchQuery = ref('');
const filterStatusVehiculos = ref('active');
const filterStatusConductores = ref('active');

const isVehiculoDrawerOpen = ref(false);
const editingVehiculo = ref(null);

const isConductorDrawerOpen = ref(false);
const editingConductor = ref(null);

// Confirm Modal state
const showConfirmModal = ref(false);
const itemToToggle = ref(null);
const toggleType = ref('vehiculo');

// Date Expiration Helper
const getDaysRemaining = (dateStr) => {
  if (!dateStr) return null;
  const target = new Date(dateStr);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  target.setHours(0, 0, 0, 0);
  const diffTime = target - today;
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
};

const filteredVehiculos = computed(() => {
  return (props.vehiculos || []).filter(v => {
    const search = searchQuery.value.toLowerCase();
    const placa = (v.placa || '').toLowerCase();
    const marca = (v.marca_modelo || '').toLowerCase();
    const matchesSearch = placa.includes(search) || marca.includes(search);
    const matchesStatus = filterStatusVehiculos.value === 'all' || 
                          (filterStatusVehiculos.value === 'active' && (v.activo ?? true)) || 
                          (filterStatusVehiculos.value === 'inactive' && (!v.activo));
    return matchesSearch && matchesStatus;
  });
});

const filteredConductores = computed(() => {
  return (props.conductores || []).filter(c => {
    const search = searchQuery.value.toLowerCase();
    const nombre = `${c.nombres || c.trabajador?.nombres || ''} ${c.apellido_paterno || c.trabajador?.apellidos || ''} ${c.apellido_materno || ''}`.toLowerCase();
    const dni = (c.dni || c.trabajador?.dni || '').toLowerCase();
    const licencia = (c.numero_licencia || '').toLowerCase();
    const cat = (c.categoria_licencia || '').toLowerCase();
    const rol = (c.rol_conductor || '').toLowerCase();

    const matchesSearch = nombre.includes(search) || dni.includes(search) || licencia.includes(search) || cat.includes(search) || rol.includes(search);
    const matchesStatus = filterStatusConductores.value === 'all' || 
                          (filterStatusConductores.value === 'active' && (c.activo ?? true)) || 
                          (filterStatusConductores.value === 'inactive' && (!c.activo));
    return matchesSearch && matchesStatus;
  });
});

const vehiculoForm = useForm({
  empresa_id: '',
  placa: '',
  marca_modelo: '',
  capacidad_pasajeros: 46,
  soat_vencimiento: '',
  rt_vencimiento: '',
});

const conductorForm = useForm({
  dni: '',
  nombres: '',
  apellido_paterno: '',
  apellido_materno: '',
  fecha_nacimiento: '',
  numero_licencia: '',
  categoria_licencia: 'A-I',
  rol_conductor: 'CONDUCTOR',
  brevete_interno_vencimiento: '',
});

const handleUppercaseVehiculo = (field, event) => {
  vehiculoForm[field] = (event.target.value || '').toUpperCase();
};

const handleUppercaseConductor = (field, event) => {
  conductorForm[field] = (event.target.value || '').toUpperCase();
};

const openVehiculoCreate = () => {
  editingVehiculo.value = null;
  vehiculoForm.reset();
  vehiculoForm.capacidad_pasajeros = 46;
  isVehiculoDrawerOpen.value = true;
};

const openVehiculoEdit = (v) => {
  editingVehiculo.value = v;
  vehiculoForm.empresa_id = v.empresa_id || '';
  vehiculoForm.placa = v.placa || '';
  vehiculoForm.marca_modelo = v.marca_modelo || '';
  vehiculoForm.capacidad_pasajeros = v.capacidad_pasajeros || 46;
  vehiculoForm.soat_vencimiento = v.soat_vencimiento || '';
  vehiculoForm.rt_vencimiento = v.rt_vencimiento || '';
  isVehiculoDrawerOpen.value = true;
};

const submitVehiculoForm = () => {
  vehiculoForm.placa = (vehiculoForm.placa || '').toUpperCase();
  vehiculoForm.marca_modelo = (vehiculoForm.marca_modelo || '').toUpperCase();

  if (editingVehiculo.value) {
    vehiculoForm.put(route('flota.vehiculos.update', editingVehiculo.value.id), {
      onSuccess: () => isVehiculoDrawerOpen.value = false,
    });
  } else {
    vehiculoForm.post(route('flota.vehiculos.store'), {
      onSuccess: () => isVehiculoDrawerOpen.value = false,
    });
  }
};

const openConductorCreate = () => {
  editingConductor.value = null;
  conductorForm.reset();
  conductorForm.categoria_licencia = 'A-I';
  conductorForm.rol_conductor = 'CONDUCTOR';
  isConductorDrawerOpen.value = true;
};

const openConductorEdit = (c) => {
  editingConductor.value = c;
  conductorForm.dni = c.dni || c.trabajador?.dni || '';
  conductorForm.nombres = c.nombres || c.trabajador?.nombres || '';
  conductorForm.apellido_paterno = c.apellido_paterno || c.trabajador?.apellido_paterno || '';
  conductorForm.apellido_materno = c.apellido_materno || c.trabajador?.apellido_materno || '';
  conductorForm.fecha_nacimiento = c.fecha_nacimiento || '';
  conductorForm.numero_licencia = c.numero_licencia || '';
  conductorForm.categoria_licencia = c.categoria_licencia || 'A-I';
  conductorForm.rol_conductor = c.rol_conductor || 'CONDUCTOR';
  conductorForm.brevete_interno_vencimiento = c.brevete_interno_vencimiento || '';
  isConductorDrawerOpen.value = true;
};

const submitConductorForm = () => {
  conductorForm.nombres = (conductorForm.nombres || '').toUpperCase();
  conductorForm.apellido_paterno = (conductorForm.apellido_paterno || '').toUpperCase();
  conductorForm.apellido_materno = (conductorForm.apellido_materno || '').toUpperCase();
  conductorForm.numero_licencia = (conductorForm.numero_licencia || '').toUpperCase();

  if (editingConductor.value) {
    conductorForm.put(route('flota.conductores.update', editingConductor.value.id), {
      onSuccess: () => isConductorDrawerOpen.value = false,
    });
  } else {
    conductorForm.post(route('flota.conductores.store'), {
      onSuccess: () => isConductorDrawerOpen.value = false,
    });
  }
};

const confirmToggleVehiculo = (v) => {
  itemToToggle.value = v;
  toggleType.value = 'vehiculo';
  showConfirmModal.value = true;
};

const confirmToggleConductor = (c) => {
  itemToToggle.value = c;
  toggleType.value = 'conductor';
  showConfirmModal.value = true;
};

const executeToggle = () => {
  if (toggleType.value === 'vehiculo' && itemToToggle.value) {
    router.delete(route('flota.vehiculos.destroy', itemToToggle.value.id), {
      onSuccess: () => {
        showConfirmModal.value = false;
        itemToToggle.value = null;
      }
    });
  } else if (toggleType.value === 'conductor' && itemToToggle.value) {
    router.delete(route('flota.conductores.destroy', itemToToggle.value.id), {
      onSuccess: () => {
        showConfirmModal.value = false;
        itemToToggle.value = null;
      }
    });
  }
};
</script>

<template>
  <AppLayout>
    <div class="w-full space-y-6">
      
      <!-- Top Banner & Main Actions -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
          <h2 class="text-xl font-extrabold text-slate-900 flex items-center">
            <Bus class="w-6 h-6 text-purple-600 mr-2.5" /> Flota de Buses y Tripulación de Choferes
          </h2>
          <p class="text-sm text-slate-500 mt-1">Gestión de unidades de movilidad Magori y registro de conductores y copilotos MTC.</p>
        </div>
        <div v-if="canWrite" class="flex space-x-2">
          <button 
            @click="openVehiculoCreate"
            class="bg-purple-600 hover:bg-purple-500 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-md hover:shadow-purple-500/20 flex items-center space-x-2 transition cursor-pointer"
          >
            <Plus class="w-4 h-4" />
            <span>Nuevo Vehículo</span>
          </button>
          <button 
            @click="openConductorCreate"
            class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-md hover:shadow-indigo-500/20 flex items-center space-x-2 transition cursor-pointer"
          >
            <Plus class="w-4 h-4" />
            <span>Nuevo Conductor / Copiloto</span>
          </button>
        </div>
      </div>

      <!-- Tab Switcher & Search Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex bg-slate-200/70 p-1 rounded-xl w-fit border border-slate-200">
          <button 
            @click="activeTab = 'vehiculos'"
            :class="['px-4 py-2 rounded-lg text-xs font-extrabold transition cursor-pointer flex items-center space-x-2', activeTab === 'vehiculos' ? 'bg-white text-purple-700 shadow-xs' : 'text-slate-600 hover:text-slate-900']"
          >
            <Bus class="w-4 h-4" />
            <span>Vehículos / Flota ({{ vehiculos.length }})</span>
          </button>
          <button 
            @click="activeTab = 'conductores'"
            :class="['px-4 py-2 rounded-lg text-xs font-extrabold transition cursor-pointer flex items-center space-x-2', activeTab === 'conductores' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-600 hover:text-slate-900']"
          >
            <Users class="w-4 h-4" />
            <span>Conductores y Copilotos ({{ conductores.length }})</span>
          </button>
        </div>

        <div class="relative w-full sm:w-72">
          <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
          <input 
            v-model="searchQuery" 
            type="text" 
            :placeholder="activeTab === 'vehiculos' ? 'Buscar Placa o Modelo...' : 'Buscar DNI, Conductor o Licencia...'" 
            class="w-full bg-white border border-slate-300 rounded-xl pl-9 pr-4 py-2 text-xs text-slate-900 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-purple-500 outline-none shadow-sm"
          />
        </div>
      </div>

      <!-- Tab 1: Vehículos Table -->
      <div v-if="activeTab === 'vehiculos'" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase border-b border-slate-100">
              <tr>
                <th class="px-6 py-3.5">Placa</th>
                <th class="px-6 py-3.5">Marca / Modelo</th>
                <th class="px-6 py-3.5">Capacidad Pasajeros</th>
                <th class="px-6 py-3.5">Estado / Alertas Vencimiento</th>
                <th v-if="canWrite" class="px-6 py-3.5 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="v in paginatedVehiculos" :key="v.id" :class="['hover:bg-slate-50/80 transition', !v.activo ? 'bg-red-50/30 opacity-75' : '']">
                <td class="px-6 py-4 font-mono font-extrabold text-purple-700 text-base">
                  {{ v.placa }}
                </td>
                <td class="px-6 py-4 font-extrabold text-slate-900 uppercase">
                  {{ v.marca_modelo }}
                </td>
                <td class="px-6 py-4 font-bold text-slate-900">
                  <span class="inline-flex items-center text-xs bg-purple-50 text-purple-800 px-2.5 py-1 rounded-lg border border-purple-200">
                    <Users class="w-3.5 h-3.5 mr-1 text-purple-600" />
                    {{ v.capacidad_pasajeros }} asientos
                  </span>
                </td>
                <td class="px-6 py-4">
                  <!-- Expiration Alert Warnings -->
                  <div class="flex flex-wrap gap-1.5 items-center">
                    <span v-if="!v.activo" class="px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">Inactivo</span>
                    <template v-else>
                      <!-- SOAT Alert -->
                      <span v-if="getDaysRemaining(v.soat_vencimiento) !== null && getDaysRemaining(v.soat_vencimiento) <= 0" class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-red-600 text-white animate-pulse">
                        🚨 SOAT Vencido
                      </span>
                      <span v-else-if="getDaysRemaining(v.soat_vencimiento) !== null && getDaysRemaining(v.soat_vencimiento) <= 30" class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-amber-100 text-amber-900 border border-amber-300">
                        ⚠ SOAT Vence en {{ getDaysRemaining(v.soat_vencimiento) }} días
                      </span>

                      <!-- RT Alert -->
                      <span v-if="getDaysRemaining(v.rt_vencimiento) !== null && getDaysRemaining(v.rt_vencimiento) <= 0" class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-red-600 text-white animate-pulse">
                        🚨 Rev. Técnica Vencida
                      </span>
                      <span v-else-if="getDaysRemaining(v.rt_vencimiento) !== null && getDaysRemaining(v.rt_vencimiento) <= 30" class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-amber-100 text-amber-900 border border-amber-300">
                        ⚠ RT Vence en {{ getDaysRemaining(v.rt_vencimiento) }} días
                      </span>

                      <span v-if="(getDaysRemaining(v.soat_vencimiento) === null || getDaysRemaining(v.soat_vencimiento) > 30) && (getDaysRemaining(v.rt_vencimiento) === null || getDaysRemaining(v.rt_vencimiento) > 30)" class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        Operativo
                      </span>
                    </template>
                  </div>
                </td>
                <td v-if="canWrite" class="px-6 py-4 text-right space-x-1 whitespace-nowrap">
                  <button 
                    @click="openVehiculoEdit(v)"
                    title="Editar vehículo"
                    class="p-1.5 text-slate-400 hover:text-purple-600 hover:bg-purple-50/80 rounded-lg transition cursor-pointer"
                  >
                    <Edit3 class="w-3.5 h-3.5" />
                  </button>
                  <button 
                    @click="confirmToggleVehiculo(v)"
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
                <td colspan="5" class="px-6 py-8 text-center text-slate-400 text-sm">
                  No se encontraron vehículos registrados.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <TablePagination 
          :totalItems="filteredVehiculos.length" 
          v-model:currentPage="currentVehiculosPage" 
          v-model:perPage="perVehiculosPage" 
        />
      </div>

      <!-- Tab 2: Conductores Table -->
      <div v-if="activeTab === 'conductores'" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase border-b border-slate-100">
              <tr>
                <th class="px-6 py-3.5">DNI</th>
                <th class="px-6 py-3.5">Conductor / Copiloto</th>
                <th class="px-6 py-3.5">Licencia MTC</th>
                <th class="px-6 py-3.5">Categoría MTC</th>
                <th class="px-6 py-3.5">Función / Rol</th>
                <th class="px-6 py-3.5">Estado / Alerta Brevete</th>
                <th v-if="canWrite" class="px-6 py-3.5 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="c in paginatedConductores" :key="c.id" :class="['hover:bg-slate-50/80 transition', !c.activo ? 'bg-red-50/30 opacity-75' : '']">
                <td class="px-6 py-4 font-mono font-extrabold text-slate-900">
                  {{ c.dni || c.trabajador?.dni || '-' }}
                </td>
                <td class="px-6 py-4">
                  <span class="block text-sm uppercase font-extrabold text-slate-900">
                    {{ c.nombres || c.trabajador?.nombres }} {{ c.apellido_paterno || c.trabajador?.apellido_paterno }} {{ c.apellido_materno || c.trabajador?.apellido_materno }}
                  </span>
                  <span v-if="c.fecha_nacimiento" class="text-[11px] text-slate-400 font-medium block">F. Nac: {{ c.fecha_nacimiento }}</span>
                </td>
                <td class="px-6 py-4 font-mono font-extrabold text-indigo-700">
                  {{ c.numero_licencia }}
                </td>
                <td class="px-6 py-4 font-extrabold">
                  <span class="bg-indigo-50 text-indigo-800 border border-indigo-200 px-2.5 py-1 rounded-lg text-xs">
                    {{ c.categoria_licencia }}
                  </span>
                </td>
                <td class="px-6 py-4 font-extrabold">
                  <span :class="['px-2 py-0.5 rounded text-xs border', c.rol_conductor === 'COPILOTO' ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-blue-50 text-blue-700 border-blue-200']">
                    {{ c.rol_conductor || 'CONDUCTOR' }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="flex flex-wrap gap-1.5 items-center">
                    <span v-if="!c.activo" class="px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">Inactivo</span>
                    <template v-else>
                      <!-- Brevete Expiration Alert -->
                      <span v-if="getDaysRemaining(c.brevete_interno_vencimiento) !== null && getDaysRemaining(c.brevete_interno_vencimiento) <= 0" class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-red-600 text-white animate-pulse">
                        🚨 Brevete Vencido
                      </span>
                      <span v-else-if="getDaysRemaining(c.brevete_interno_vencimiento) !== null && getDaysRemaining(c.brevete_interno_vencimiento) <= 30" class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-amber-100 text-amber-900 border border-amber-300">
                        ⚠ Brevete Vence en {{ getDaysRemaining(c.brevete_interno_vencimiento) }} días
                      </span>
                      <span v-else class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        Habilitado
                      </span>
                    </template>
                  </div>
                </td>
                <td v-if="canWrite" class="px-6 py-4 text-right space-x-1 whitespace-nowrap">
                  <button 
                    @click="openConductorEdit(c)"
                    title="Editar conductor"
                    class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50/80 rounded-lg transition cursor-pointer"
                  >
                    <Edit3 class="w-3.5 h-3.5" />
                  </button>
                  <button 
                    @click="confirmToggleConductor(c)"
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
                <td colspan="7" class="px-6 py-8 text-center text-slate-400 text-sm">
                  No se encontraron conductores registrados.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <TablePagination 
          :totalItems="filteredConductores.length" 
          v-model:currentPage="currentConductoresPage" 
          v-model:perPage="perConductoresPage" 
        />
      </div>

      <!-- Teleported Drawer Vehículo -->
      <Teleport to="body">
        <div v-if="isVehiculoDrawerOpen" class="fixed inset-0 z-[9999] overflow-hidden">
          <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="isVehiculoDrawerOpen = false"></div>

          <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
            <div class="w-screen max-w-md bg-white shadow-2xl flex flex-col transform transition duration-300 border-l border-slate-200">
              
              <div class="p-6 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 rounded-xl bg-purple-600 flex items-center justify-center text-white">
                    <Bus class="w-5 h-5" />
                  </div>
                  <div>
                    <h3 class="font-extrabold text-lg text-slate-100">
                      {{ editingVehiculo ? 'Editar Vehículo' : 'Nuevo Vehículo' }}
                    </h3>
                    <span class="text-xs text-purple-300 block">Registro de bus de movilidad</span>
                  </div>
                </div>
                <button @click="isVehiculoDrawerOpen = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 cursor-pointer"><X class="w-5 h-5" /></button>
              </div>

              <form @submit.prevent="submitVehiculoForm" class="flex-1 overflow-y-auto p-6 space-y-4">
                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Placa *</label>
                  <input 
                    v-model="vehiculoForm.placa" 
                    @input="e => handleUppercaseVehiculo('placa', e)"
                    type="text" 
                    maxlength="10" 
                    required 
                    class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-purple-500 outline-none font-mono uppercase" 
                    placeholder="F1A-892" 
                  />
                </div>

                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Marca y Modelo *</label>
                  <input 
                    v-model="vehiculoForm.marca_modelo" 
                    @input="e => handleUppercaseVehiculo('marca_modelo', e)"
                    type="text" 
                    required 
                    class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-purple-500 outline-none uppercase" 
                    placeholder="VOLVO BUS B450R 6X2" 
                  />
                </div>

                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Capacidad Pasajeros *</label>
                  <input v-model="vehiculoForm.capacidad_pasajeros" type="number" min="1" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-bold focus:ring-2 focus:ring-purple-500 outline-none" placeholder="46" />
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-100">
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">SOAT Vencimiento <span class="text-slate-400 font-normal">(Opcional)</span></label>
                    <input v-model="vehiculoForm.soat_vencimiento" type="date" class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-purple-500 outline-none" />
                  </div>
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Rev. Técnica Vencimiento <span class="text-slate-400 font-normal">(Opcional)</span></label>
                    <input v-model="vehiculoForm.rt_vencimiento" type="date" class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-purple-500 outline-none" />
                  </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                  <button type="button" @click="isVehiculoDrawerOpen = false" class="cursor-pointer px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancelar</button>
                  <button type="submit" :disabled="vehiculoForm.processing" class="cursor-pointer px-5 py-2.5 text-sm bg-purple-600 text-white font-bold rounded-xl hover:bg-purple-500 shadow-md">
                    {{ editingVehiculo ? 'Guardar Cambios' : 'Registrar Vehículo' }}
                  </button>
                </div>
              </form>

            </div>
          </div>
        </div>
      </Teleport>

      <!-- Teleported Drawer Conductor / Copiloto -->
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
                      {{ editingConductor ? 'Editar Conductor / Copiloto' : 'Nuevo Conductor / Copiloto' }}
                    </h3>
                    <span class="text-xs text-indigo-300 block">Formulario de registro MTC</span>
                  </div>
                </div>
                <button @click="isConductorDrawerOpen = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 cursor-pointer"><X class="w-5 h-5" /></button>
              </div>

              <form @submit.prevent="submitConductorForm" class="flex-1 overflow-y-auto p-6 space-y-4">
                
                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">DNI *</label>
                  <input v-model="conductorForm.dni" type="text" maxlength="8" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm font-semibold focus:ring-2 focus:ring-indigo-500 outline-none font-mono" placeholder="74567890" />
                </div>

                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Nombres *</label>
                  <input 
                    v-model="conductorForm.nombres" 
                    @input="e => handleUppercaseConductor('nombres', e)"
                    type="text" 
                    required 
                    class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm font-semibold focus:ring-2 focus:ring-indigo-500 outline-none uppercase" 
                    placeholder="JUAN CARLOS" 
                  />
                </div>

                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Apellido Paterno *</label>
                    <input 
                      v-model="conductorForm.apellido_paterno" 
                      @input="e => handleUppercaseConductor('apellido_paterno', e)"
                      type="text" 
                      required 
                      class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm font-semibold focus:ring-2 focus:ring-indigo-500 outline-none uppercase" 
                      placeholder="MENDOZA" 
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Apellido Materno *</label>
                    <input 
                      v-model="conductorForm.apellido_materno" 
                      @input="e => handleUppercaseConductor('apellido_materno', e)"
                      type="text" 
                      required 
                      class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm font-semibold focus:ring-2 focus:ring-indigo-500 outline-none uppercase" 
                      placeholder="RIOS" 
                    />
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Función / Rol *</label>
                  <select v-model="conductorForm.rol_conductor" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                    <option value="CONDUCTOR">CONDUCTOR PRINCIPAL</option>
                    <option value="COPILOTO">COPILOTO DE RUTA</option>
                  </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Licencia MTC *</label>
                    <input 
                      v-model="conductorForm.numero_licencia" 
                      @input="e => handleUppercaseConductor('numero_licencia', e)"
                      type="text" 
                      required 
                      class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm font-semibold focus:ring-2 focus:ring-indigo-500 outline-none font-mono uppercase" 
                      placeholder="Q-74567890" 
                    />
                  </div>

                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Categoría MTC *</label>
                    <select v-model="conductorForm.categoria_licencia" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                      <option v-for="cat in categoriasMtc" :key="cat" :value="cat">{{ cat }}</option>
                    </select>
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-100">
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Fecha Nacimiento <span class="text-slate-400 font-normal">(Opcional)</span></label>
                    <input v-model="conductorForm.fecha_nacimiento" type="date" class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-indigo-500 outline-none" />
                  </div>

                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Vencimiento Brevete <span class="text-slate-400 font-normal">(Opcional)</span></label>
                    <input v-model="conductorForm.brevete_interno_vencimiento" type="date" class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-indigo-500 outline-none" />
                  </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                  <button type="button" @click="isConductorDrawerOpen = false" class="cursor-pointer px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancelar</button>
                  <button type="submit" :disabled="conductorForm.processing" class="cursor-pointer px-5 py-2.5 text-sm bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-500 shadow-md">
                    {{ editingConductor ? 'Guardar Cambios' : 'Registrar Conductor' }}
                  </button>
                </div>
              </form>

            </div>
          </div>
        </div>
      </Teleport>

      <!-- Reusable Confirmation Modal -->
      <ConfirmModal 
        :show="showConfirmModal"
        :title="toggleType === 'vehiculo' ? (itemToToggle && itemToToggle.activo ? 'Inhabilitar Vehículo' : 'Reactivar Vehículo') : (itemToToggle && itemToToggle.activo ? 'Inhabilitar Conductor' : 'Reactivar Conductor')"
        :message="itemToToggle ? 'Desea ' + (itemToToggle.activo ? 'desactivar' : 'reactivar') + ' ' + (toggleType === 'vehiculo' ? 'el vehículo con placa ' + itemToToggle.placa : 'al conductor ' + (itemToToggle.nombres || itemToToggle.trabajador?.nombres || '') + ' ' + (itemToToggle.apellido_paterno || itemToToggle.trabajador?.apellidos || '')) + '?' : ''"
        :confirmText="itemToToggle && itemToToggle.activo ? 'Sí, Inhabilitar' : 'Sí, Reactivar'"
        :variant="itemToToggle && itemToToggle.activo ? 'danger' : 'success'"
        @close="showConfirmModal = false"
        @confirm="executeToggle"
      />

    </div>
  </AppLayout>
</template>
