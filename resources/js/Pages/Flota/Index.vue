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

const currentVehiculosPage = ref(1);
const perVehiculosPage = ref(15);

const currentConductoresPage = ref(1);
const perConductoresPage = ref(15);

watch([searchQuery, filterStatusVehiculos], () => {
  currentVehiculosPage.value = 1;
});

watch([searchQuery, filterStatusConductores], () => {
  currentConductoresPage.value = 1;
});

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

const paginatedVehiculos = computed(() => {
  const start = (currentVehiculosPage.value - 1) * perVehiculosPage.value;
  return filteredVehiculos.value.slice(start, start + perVehiculosPage.value);
});

const paginatedConductores = computed(() => {
  const start = (currentConductoresPage.value - 1) * perConductoresPage.value;
  return filteredConductores.value.slice(start, start + perConductoresPage.value);
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
  vehiculoForm.clearErrors();
  vehiculoForm.capacidad_pasajeros = 46;
  isVehiculoDrawerOpen.value = true;
};

const openVehiculoEdit = (v) => {
  editingVehiculo.value = v;
  vehiculoForm.clearErrors();
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
      preserveScroll: true,
      onSuccess: () => {
        isVehiculoDrawerOpen.value = false;
        vehiculoForm.reset();
        vehiculoForm.clearErrors();
      },
    });
  } else {
    vehiculoForm.post(route('flota.vehiculos.store'), {
      preserveScroll: true,
      onSuccess: () => {
        isVehiculoDrawerOpen.value = false;
        vehiculoForm.reset();
        vehiculoForm.clearErrors();
      },
    });
  }
};

const openConductorCreate = () => {
  editingConductor.value = null;
  conductorForm.reset();
  conductorForm.clearErrors();
  conductorForm.categoria_licencia = 'A-I';
  conductorForm.rol_conductor = 'CONDUCTOR';
  isConductorDrawerOpen.value = true;
};

const openConductorEdit = (c) => {
  editingConductor.value = c;
  conductorForm.clearErrors();
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
      preserveScroll: true,
      onSuccess: () => {
        isConductorDrawerOpen.value = false;
        conductorForm.reset();
        conductorForm.clearErrors();
      },
    });
  } else {
    conductorForm.post(route('flota.conductores.store'), {
      preserveScroll: true,
      onSuccess: () => {
        isConductorDrawerOpen.value = false;
        conductorForm.reset();
        conductorForm.clearErrors();
      },
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

const executeToggleItem = () => {
  if (!itemToToggle.value) return;

  if (toggleType.value === 'vehiculo') {
    router.delete(route('flota.vehiculos.destroy', itemToToggle.value.id), {
      preserveScroll: true,
      onSuccess: () => {
        showConfirmModal.value = false;
        itemToToggle.value = null;
      }
    });
  } else {
    router.delete(route('flota.conductores.destroy', itemToToggle.value.id), {
      preserveScroll: true,
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
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 theme-card p-6">
        <div>
          <h2 class="text-xl font-extrabold theme-text-title flex items-center">
            <Bus class="w-6 h-6 text-purple-600 mr-2.5" /> Flota de Vehículos y Conductores
          </h2>
          <p class="text-sm theme-text-muted mt-1">Gestión de unidades de transporte, control de SOAT, revisiones técnicas y licencias de conducir.</p>
        </div>

        <div v-if="canWrite" class="flex items-center space-x-3">
          <button 
            v-if="activeTab === 'vehiculos'"
            @click="openVehiculoCreate"
            class="theme-btn-primary text-sm px-4 py-2.5 flex items-center space-x-2"
          >
            <Plus class="w-4 h-4" />
            <span>Nuevo Vehículo</span>
          </button>
          <button 
            v-if="activeTab === 'conductores'"
            @click="openConductorCreate"
            class="theme-btn-primary text-sm px-4 py-2.5 flex items-center space-x-2"
          >
            <Plus class="w-4 h-4" />
            <span>Nuevo Conductor</span>
          </button>
        </div>
      </div>

      <!-- Navigation Tabs & Sub-filters -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 theme-card-subtle p-2.5">
        
        <!-- Main Tabs Switcher -->
        <div class="flex items-center space-x-1.5 bg-[var(--theme-pill-bg)] p-1.5 rounded-xl self-start md:self-auto border border-[var(--theme-border)]">
          <button 
            @click="activeTab = 'vehiculos'"
            :class="[
              'px-4 py-2 rounded-lg text-xs font-bold transition flex items-center space-x-2 cursor-pointer',
              activeTab === 'vehiculos' ? 'theme-tab-active shadow-sm' : 'theme-tab-inactive'
            ]"
          >
            <Bus class="w-4 h-4" />
            <span>Buses / Unidades ({{ (vehiculos || []).length }})</span>
          </button>

          <button 
            @click="activeTab = 'conductores'"
            :class="[
              'px-4 py-2 rounded-lg text-xs font-bold transition flex items-center space-x-2 cursor-pointer',
              activeTab === 'conductores' ? 'theme-tab-active shadow-sm' : 'theme-tab-inactive'
            ]"
          >
            <Users class="w-4 h-4" />
            <span>Conductores MTC ({{ (conductores || []).length }})</span>
          </button>
        </div>

        <!-- Filter Tabs & Search Bar -->
        <div class="flex flex-wrap items-center gap-3">
          
          <!-- Status Filter Tabs for Vehiculos -->
          <div v-if="activeTab === 'vehiculos'" class="flex bg-[var(--theme-pill-bg)] p-1 rounded-xl">
            <button 
              @click="filterStatusVehiculos = 'active'"
              :class="['px-3 py-1 text-xs font-bold rounded-lg transition cursor-pointer', filterStatusVehiculos === 'active' ? 'theme-tab-active shadow-sm' : 'theme-tab-inactive']"
            >
              Activos ({{ (vehiculos || []).filter(v => (v.activo ?? true)).length }})
            </button>
            <button 
              @click="filterStatusVehiculos = 'inactive'"
              :class="['px-3 py-1 text-xs font-bold rounded-lg transition cursor-pointer', filterStatusVehiculos === 'inactive' ? 'theme-tab-active shadow-sm' : 'theme-tab-inactive']"
            >
              Inactivos ({{ (vehiculos || []).filter(v => !(v.activo ?? true)).length }})
            </button>
            <button 
              @click="filterStatusVehiculos = 'all'"
              :class="['px-3 py-1 text-xs font-bold rounded-lg transition cursor-pointer', filterStatusVehiculos === 'all' ? 'theme-tab-active shadow-sm' : 'theme-tab-inactive']"
            >
              Todos ({{ (vehiculos || []).length }})
            </button>
          </div>

          <!-- Status Filter Tabs for Conductores -->
          <div v-if="activeTab === 'conductores'" class="flex bg-[var(--theme-pill-bg)] p-1 rounded-xl">
            <button 
              @click="filterStatusConductores = 'active'"
              :class="['px-3 py-1 text-xs font-bold rounded-lg transition cursor-pointer', filterStatusConductores === 'active' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900']"
            >
              Activos ({{ (conductores || []).filter(c => (c.activo ?? true)).length }})
            </button>
            <button 
              @click="filterStatusConductores = 'inactive'"
              :class="['px-3 py-1 text-xs font-bold rounded-lg transition cursor-pointer', filterStatusConductores === 'inactive' ? 'bg-white text-red-700 shadow-sm' : 'text-slate-600 hover:text-slate-900']"
            >
              Inactivos ({{ (conductores || []).filter(c => !(c.activo ?? true)).length }})
            </button>
            <button 
              @click="filterStatusConductores = 'all'"
              :class="['px-3 py-1 text-xs font-bold rounded-lg transition cursor-pointer', filterStatusConductores === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900']"
            >
              Todos ({{ (conductores || []).length }})
            </button>
          </div>

          <!-- Search Input -->
          <div class="relative w-full sm:w-64">
            <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input 
              v-model="searchQuery" 
              type="text" 
              :placeholder="activeTab === 'vehiculos' ? 'Buscar por placa, modelo...' : 'Buscar conductor, DNI, licencia...'" 
              class="w-full bg-white border border-slate-200 text-xs rounded-xl pl-9 pr-4 py-2 text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-purple-500 outline-none"
            />
          </div>
        </div>
      </div>

      <!-- Tab 1: Vehículos Table -->
      <div v-if="activeTab === 'vehiculos'" class="theme-card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="theme-card-subtle text-xs font-bold theme-text-muted uppercase border-b border-[var(--theme-border)]">
              <tr>
                <th class="px-6 py-3.5">Placa</th>
                <th class="px-6 py-3.5">Marca / Modelo</th>
                <th class="px-6 py-3.5">Capacidad</th>
                <th class="px-6 py-3.5">SOAT / Rev. Técnica</th>
                <th v-if="canWrite" class="px-6 py-3.5 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="v in paginatedVehiculos" :key="v.id" :class="['hover:bg-[var(--palette-50)]/5 transition', !v.activo ? 'bg-red-50/30 opacity-75' : '']">
                <td class="px-6 py-4 font-mono font-extrabold text-purple-700 text-base">
                  {{ v.placa }}
                </td>
                <td class="px-6 py-4 font-extrabold theme-text-title uppercase">
                  {{ v.marca_modelo }}
                </td>
                <td class="px-6 py-4 font-bold theme-text-body">
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
                        SOAT Vencido
                      </span>
                      <span v-else-if="getDaysRemaining(v.soat_vencimiento) !== null && getDaysRemaining(v.soat_vencimiento) <= 30" class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-amber-100 text-amber-900 border border-amber-300">
                        SOAT Vence en {{ getDaysRemaining(v.soat_vencimiento) }} días
                      </span>

                      <!-- RT Alert -->
                      <span v-if="getDaysRemaining(v.rt_vencimiento) !== null && getDaysRemaining(v.rt_vencimiento) <= 0" class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-red-600 text-white animate-pulse">
                        Rev. Técnica Vencida
                      </span>
                      <span v-else-if="getDaysRemaining(v.rt_vencimiento) !== null && getDaysRemaining(v.rt_vencimiento) <= 30" class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-amber-100 text-amber-900 border border-amber-300">
                        RT Vence en {{ getDaysRemaining(v.rt_vencimiento) }} días
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
      <div v-if="activeTab === 'conductores'" class="theme-card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="theme-card-subtle text-xs font-bold theme-text-muted uppercase border-b border-[var(--theme-border)]">
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
              <tr v-for="c in paginatedConductores" :key="c.id" :class="['hover:bg-[var(--palette-50)]/5 transition', !c.activo ? 'bg-red-50/30 opacity-75' : '']">
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
                        Brevete Vencido
                      </span>
                      <span v-else-if="getDaysRemaining(c.brevete_interno_vencimiento) !== null && getDaysRemaining(c.brevete_interno_vencimiento) <= 30" class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-amber-100 text-amber-900 border border-amber-300">
                        Brevete Vence en {{ getDaysRemaining(c.brevete_interno_vencimiento) }} días
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
            <div class="w-screen max-w-md theme-card shadow-2xl flex flex-col transform transition duration-300 border-l border-[var(--theme-border)]">
              
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
                  <span v-if="vehiculoForm.errors.placa" class="text-xs text-red-600 font-bold mt-1 block">{{ vehiculoForm.errors.placa }}</span>
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
                  <span v-if="vehiculoForm.errors.marca_modelo" class="text-xs text-red-600 font-bold mt-1 block">{{ vehiculoForm.errors.marca_modelo }}</span>
                </div>

                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Capacidad Pasajeros *</label>
                  <input v-model="vehiculoForm.capacidad_pasajeros" type="number" min="1" max="100" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-bold focus:ring-2 focus:ring-purple-500 outline-none" placeholder="46" />
                  <span v-if="vehiculoForm.errors.capacidad_pasajeros" class="text-xs text-red-600 font-bold mt-1 block">{{ vehiculoForm.errors.capacidad_pasajeros }}</span>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-100">
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">SOAT Vencimiento <span class="text-slate-400 font-normal">(Opcional)</span></label>
                    <input v-model="vehiculoForm.soat_vencimiento" type="date" class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-purple-500 outline-none" />
                    <span v-if="vehiculoForm.errors.soat_vencimiento" class="text-xs text-red-600 font-bold mt-1 block">{{ vehiculoForm.errors.soat_vencimiento }}</span>
                  </div>
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Rev. Técnica Vencimiento <span class="text-slate-400 font-normal">(Opcional)</span></label>
                    <input v-model="vehiculoForm.rt_vencimiento" type="date" class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-purple-500 outline-none" />
                    <span v-if="vehiculoForm.errors.rt_vencimiento" class="text-xs text-red-600 font-bold mt-1 block">{{ vehiculoForm.errors.rt_vencimiento }}</span>
                  </div>
                </div>

                <div class="pt-4 border-t border-[var(--theme-border)] flex justify-end space-x-3">
                  <button type="button" @click="isVehiculoDrawerOpen = false" class="cursor-pointer px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancelar</button>
                  <button type="submit" :disabled="vehiculoForm.processing" class="theme-btn-primary text-sm px-5 py-2.5 disabled:opacity-50">
                    <span v-if="vehiculoForm.processing">Guardando...</span>
                    <span v-else>{{ editingVehiculo ? 'Guardar Cambios' : 'Registrar Vehículo' }}</span>
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
            <div class="w-screen max-w-md theme-card shadow-2xl flex flex-col transform transition duration-300 border-l border-[var(--theme-border)]">
              
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
                  <span v-if="conductorForm.errors.dni" class="text-xs text-red-600 font-bold mt-1 block">{{ conductorForm.errors.dni }}</span>
                </div>

                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Nombres *</label>
                  <input 
                    v-model="conductorForm.nombres" 
                    @input="e => handleUppercaseConductor('nombres', e)"
                    type="text" 
                    required 
                    class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm font-semibold focus:ring-2 focus:ring-indigo-500 outline-none uppercase" 
                    placeholder="CARLOS ALBERTO" 
                  />
                  <span v-if="conductorForm.errors.nombres" class="text-xs text-red-600 font-bold mt-1 block">{{ conductorForm.errors.nombres }}</span>
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
                      placeholder="GARCIA" 
                    />
                    <span v-if="conductorForm.errors.apellido_paterno" class="text-xs text-red-600 font-bold mt-1 block">{{ conductorForm.errors.apellido_paterno }}</span>
                  </div>
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Apellido Materno *</label>
                    <input 
                      v-model="conductorForm.apellido_materno" 
                      @input="e => handleUppercaseConductor('apellido_materno', e)"
                      type="text" 
                      required 
                      class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm font-semibold focus:ring-2 focus:ring-indigo-500 outline-none uppercase" 
                      placeholder="QUISPE" 
                    />
                    <span v-if="conductorForm.errors.apellido_materno" class="text-xs text-red-600 font-bold mt-1 block">{{ conductorForm.errors.apellido_materno }}</span>
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Fecha Nacimiento</label>
                  <input v-model="conductorForm.fecha_nacimiento" type="date" class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-semibold focus:ring-2 focus:ring-indigo-500 outline-none" />
                  <span v-if="conductorForm.errors.fecha_nacimiento" class="text-xs text-red-600 font-bold mt-1 block">{{ conductorForm.errors.fecha_nacimiento }}</span>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-100">
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Nº Licencia MTC *</label>
                    <input 
                      v-model="conductorForm.numero_licencia" 
                      @input="e => handleUppercaseConductor('numero_licencia', e)"
                      type="text" 
                      required 
                      class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm font-semibold focus:ring-2 focus:ring-indigo-500 outline-none font-mono uppercase" 
                      placeholder="Q-74567890" 
                    />
                    <span v-if="conductorForm.errors.numero_licencia" class="text-xs text-red-600 font-bold mt-1 block">{{ conductorForm.errors.numero_licencia }}</span>
                  </div>
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Categoría MTC *</label>
                    <select v-model="conductorForm.categoria_licencia" required class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-indigo-500 outline-none">
                      <option v-for="cat in categoriasMtc" :key="cat" :value="cat">{{ cat }}</option>
                    </select>
                    <span v-if="conductorForm.errors.categoria_licencia" class="text-xs text-red-600 font-bold mt-1 block">{{ conductorForm.errors.categoria_licencia }}</span>
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-100">
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Rol Operativo *</label>
                    <select v-model="conductorForm.rol_conductor" required class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-indigo-500 outline-none">
                      <option value="CONDUCTOR">Conductor</option>
                      <option value="COPILOTO">Copiloto</option>
                      <option value="AMBOS">Ambos</option>
                    </select>
                    <span v-if="conductorForm.errors.rol_conductor" class="text-xs text-red-600 font-bold mt-1 block">{{ conductorForm.errors.rol_conductor }}</span>
                  </div>
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Brevete Int. Vence</label>
                    <input v-model="conductorForm.brevete_interno_vencimiento" type="date" class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-indigo-500 outline-none" />
                    <span v-if="conductorForm.errors.brevete_interno_vencimiento" class="text-xs text-red-600 font-bold mt-1 block">{{ conductorForm.errors.brevete_interno_vencimiento }}</span>
                  </div>
                </div>

                <div class="pt-4 border-t border-[var(--theme-border)] flex justify-end space-x-3">
                  <button type="button" @click="isConductorDrawerOpen = false" class="cursor-pointer px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancelar</button>
                  <button type="submit" :disabled="conductorForm.processing" class="theme-btn-primary text-sm px-5 py-2.5 disabled:opacity-50">
                    <span v-if="conductorForm.processing">Guardando...</span>
                    <span v-else>{{ editingConductor ? 'Guardar Cambios' : 'Registrar Conductor' }}</span>
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
        :title="toggleType === 'vehiculo' ? (itemToToggle?.activo ? 'Desactivar Vehículo' : 'Reactivar Vehículo') : (itemToToggle?.activo ? 'Desactivar Conductor' : 'Reactivar Conductor')"
        :message="itemToToggle ? '¿Desea ' + (itemToToggle.activo ? 'desactivar' : 'reactivar') + ' ' + (toggleType === 'vehiculo' ? 'la unidad ' + itemToToggle.placa : 'al conductor ' + (itemToToggle.nombres || '') + ' ' + (itemToToggle.apellido_paterno || '')) + '?' : ''"
        :confirmText="itemToToggle?.activo ? 'Sí, Desactivar' : 'Sí, Reactivar'"
        :variant="itemToToggle?.activo ? 'danger' : 'success'"
        @close="showConfirmModal = false"
        @confirm="executeToggleItem"
      />

    </div>
  </AppLayout>
</template>