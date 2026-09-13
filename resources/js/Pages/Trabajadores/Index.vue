<script setup>
import { ref, computed, watch } from 'vue';
import TablePagination from '@/Components/TablePagination.vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { 
  Users, 
  UserPlus, 
  Search, 
  Edit3, 
  Trash2, 
  RotateCcw, 
  X, 
  Building2, 
  ShieldCheck, 
  Briefcase
} from 'lucide-vue-next';

const props = defineProps({
  trabajadores: Array,
  empresas: Array,
});

const page = usePage();
const canWrite = computed(() => {
  const perm = page.props.auth?.user?.permisos?.trabajadores;
  return perm === 'ESCRITURA' || page.props.auth?.user?.rol === 'ADMIN';
});

const searchQuery = ref('');
const filterEmpresa = ref('');
const filterStatus = ref('active');
const isDrawerOpen = ref(false);
const editingTrabajador = ref(null);
const currentPage = ref(1);
const perPage = ref(15);

watch([searchQuery, filterEmpresa, filterStatus], () => {
  currentPage.value = 1;
});

const filteredTrabajadores = computed(() => {
  return (props.trabajadores || []).filter(t => {
    const search = searchQuery.value.toLowerCase();
    const nombreCompleto = `${t.nombres || ''} ${t.apellido_paterno || ''} ${t.apellido_materno || ''} ${t.apellidos || ''}`.toLowerCase();
    const dni = t.dni ? String(t.dni).toLowerCase() : '';
    const area = t.area ? t.area.toLowerCase() : '';
    const empresaNombre = t.empresa ? t.empresa.razon_social.toLowerCase() : '';

    const matchesSearch = nombreCompleto.includes(search) || dni.includes(search) || area.includes(search) || empresaNombre.includes(search);
    const matchesEmpresa = !filterEmpresa.value || t.empresa_id == filterEmpresa.value;
    const matchesStatus = filterStatus.value === 'all' || 
                          (filterStatus.value === 'active' && (t.estado ?? 1) == 1) || 
                          (filterStatus.value === 'inactive' && (t.estado ?? 1) == 0);

    return matchesSearch && matchesEmpresa && matchesStatus;
  });
});

const paginatedTrabajadores = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  return filteredTrabajadores.value.slice(start, start + perPage.value);
});

const form = useForm({
  empresa_id: '',
  dni: '',
  nombres: '',
  apellido_paterno: '',
  apellido_materno: '',
  area: '',
  cargo: '',
  telefono_emergencia: '',
  grupo_sanguineo: 'O+',
  estado_acreditacion: 'APTO',
});

const handleUppercaseInput = (field, event) => {
  form[field] = (event.target.value || '').toUpperCase();
};

const openCreateDrawer = () => {
  editingTrabajador.value = null;
  form.reset();
  form.clearErrors();
  form.grupo_sanguineo = 'O+';
  form.estado_acreditacion = 'APTO';
  if (props.empresas && props.empresas.length > 0) {
    form.empresa_id = props.empresas[0].id;
  }
  isDrawerOpen.value = true;
};

const openEditDrawer = (t) => {
  editingTrabajador.value = t;
  form.clearErrors();
  form.empresa_id = t.empresa_id || '';
  form.dni = t.dni || '';
  form.nombres = t.nombres || '';
  form.apellido_paterno = t.apellido_paterno || '';
  form.apellido_materno = t.apellido_materno || '';
  form.area = t.area || '';
  form.cargo = t.cargo || '';
  form.telefono_emergencia = t.telefono_emergencia || '';
  form.grupo_sanguineo = t.grupo_sanguineo || 'O+';
  form.estado_acreditacion = t.estado_acreditacion || 'APTO';
  isDrawerOpen.value = true;
};

const submitForm = () => {
  form.nombres = (form.nombres || '').toUpperCase();
  form.apellido_paterno = (form.apellido_paterno || '').toUpperCase();
  form.apellido_materno = (form.apellido_materno || '').toUpperCase();
  form.area = (form.area || '').toUpperCase();

  if (editingTrabajador.value) {
    form.put(route('trabajadores.update', editingTrabajador.value.id), {
      preserveScroll: true,
      onSuccess: () => {
        isDrawerOpen.value = false;
        form.reset();
        form.clearErrors();
      },
    });
  } else {
    form.post(route('trabajadores.store'), {
      preserveScroll: true,
      onSuccess: () => {
        isDrawerOpen.value = false;
        form.reset();
        form.clearErrors();
      },
    });
  }
};

const showConfirmModal = ref(false);
const trabajadorToToggle = ref(null);

const confirmToggleEstado = (t) => {
  trabajadorToToggle.value = t;
  showConfirmModal.value = true;
};

const executeToggleEstado = () => {
  if (trabajadorToToggle.value) {
    router.delete(route('trabajadores.destroy', trabajadorToToggle.value.id), {
      preserveScroll: true,
      onSuccess: () => {
        showConfirmModal.value = false;
        trabajadorToToggle.value = null;
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
            <Users class="w-6 h-6 text-blue-600 mr-2.5" /> Registro e Historial de Personal
          </h2>
          <p class="text-sm theme-text-muted mt-1">Administración de padrón de trabajadores auditados y asignación de empresas contratistas.</p>
        </div>
        <button 
          v-if="canWrite"
          @click="openCreateDrawer"
          class="theme-btn-primary text-sm px-4 py-2.5 flex items-center space-x-2"
        >
          <UserPlus class="w-4 h-4" />
          <span>Nuevo Trabajador</span>
        </button>
      </div>

      <!-- Filters & Search Bar -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 theme-card-subtle p-2.5">
        <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto">
          <!-- Status Filter Tabs -->
          <div class="flex bg-[var(--theme-pill-bg)] p-1 rounded-xl">
            <button 
              @click="filterStatus = 'active'"
              :class="['px-3.5 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer', filterStatus === 'active' ? 'theme-tab-active shadow-sm' : 'theme-tab-inactive']"
            >
              Activos ({{ (trabajadores || []).filter(t => (t.estado ?? 1) == 1).length }})
            </button>
            <button 
              @click="filterStatus = 'inactive'"
              :class="['px-3.5 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer', filterStatus === 'inactive' ? 'theme-tab-active shadow-sm' : 'theme-tab-inactive']"
            >
              Inactivos ({{ (trabajadores || []).filter(t => (t.estado ?? 1) == 0).length }})
            </button>
            <button 
              @click="filterStatus = 'all'"
              :class="['px-3.5 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer', filterStatus === 'all' ? 'theme-tab-active shadow-sm' : 'theme-tab-inactive']"
            >
              Todos ({{ (trabajadores || []).length }})
            </button>
          </div>

          <!-- Empresa Filter -->
          <div class="w-full sm:w-64">
            <select v-model="filterEmpresa" class="w-full bg-white border border-slate-200 text-xs font-semibold rounded-xl px-3 py-2 text-slate-700 focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="">Todas las Empresas</option>
              <option v-for="e in empresas" :key="e.id" :value="e.id">{{ e.razon_social }}</option>
            </select>
          </div>
        </div>

        <!-- Search Input -->
        <div class="relative w-full lg:w-80">
          <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Buscar por DNI, Nombres o Área..." 
            class="w-full theme-input pl-9 pr-4 py-2 text-xs font-medium placeholder:text-[var(--theme-text-muted)] shadow-sm"
          />
        </div>
      </div>

      <!-- Workers Table -->
      <div class="theme-card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-sm">
            <thead>
              <tr class="border-b border-[var(--theme-border)] theme-card-subtle text-xs font-extrabold theme-text-muted uppercase tracking-wider">
                <th class="px-6 py-4">Personal / DNI</th>
                <th class="px-6 py-4">Empresa Asignada</th>
                <th class="px-6 py-4">Área / Cargo</th>
                <!-- <th class="px-6 py-4">Contacto Emergencia</th> -->
                <th class="px-6 py-4 text-center">Acreditación</th>
                <th v-if="canWrite" class="px-6 py-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="t in paginatedTrabajadores" :key="t.id" class="hover:bg-[var(--palette-50)]/5 transition" :class="{'opacity-60 bg-slate-50/40': (t.estado ?? 1) == 0}">
                <td class="px-6 py-4">
                  <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs">
                      {{ (t.nombres || 'U').charAt(0) }}{{ (t.apellido_paterno || '').charAt(0) }}
                    </div>
                    <div>
                      <div class="font-bold theme-text-title leading-tight">
                        {{ t.nombres }} {{ t.apellido_paterno }} {{ t.apellido_materno }}
                      </div>
                      <div class="text-xs theme-text-muted font-mono mt-0.5">DNI: {{ t.dni }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center text-xs font-semibold theme-text-body">
                    <Building2 class="w-3.5 h-3.5 text-slate-400 mr-1.5 shrink-0" />
                    <span class="truncate max-w-[200px]">{{ t.empresa ? t.empresa.razon_social : 'No Asignada' }}</span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="font-semibold text-xs theme-text-body">{{ t.area }}</div>
                  <div class="text-[11px] text-slate-500 flex items-center mt-0.5">
                    <!-- <Briefcase class="w-3 h-3 text-slate-400 mr-1 shrink-0" />
                    {{ t.cargo || 'Operario' }} -->
                  </div>
                </td>
                <!-- <td class="px-6 py-4 text-xs font-mono text-slate-600">
                  {{ t.telefono_emergencia || 'No registrado' }}
                </td> -->
                <td class="px-6 py-4 text-center">
                  <span 
                    :class="[
                      'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border',
                      t.estado_acreditacion === 'APTO' ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-amber-100 text-amber-800 border-amber-200'
                    ]"
                  >
                    <ShieldCheck class="w-3.5 h-3.5 mr-1" />
                    {{ t.estado_acreditacion || 'APTO' }}
                  </span>
                </td>
                <td v-if="canWrite" class="px-6 py-4 text-right space-x-1 whitespace-nowrap">
                  <button 
                    @click="openEditDrawer(t)"
                    title="Editar trabajador"
                    class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50/80 rounded-lg transition cursor-pointer"
                  >
                    <Edit3 class="w-3.5 h-3.5" />
                  </button>
                  <button 
                    @click="confirmToggleEstado(t)"
                    :title="(t.estado ?? 1) == 1 ? 'Desactivar trabajador' : 'Reactivar trabajador'"
                    :class="[
                      'p-1.5 rounded-lg transition cursor-pointer',
                      (t.estado ?? 1) == 1 
                        ? 'text-slate-400 hover:text-red-600 hover:bg-red-50/80' 
                        : 'text-slate-400 hover:text-emerald-600 hover:bg-emerald-50/80'
                    ]"
                  >
                    <component :is="(t.estado ?? 1) == 1 ? Trash2 : RotateCcw" class="w-3.5 h-3.5" />
                  </button>
                </td>
              </tr>
              <tr v-if="!filteredTrabajadores || filteredTrabajadores.length === 0">
                <td colspan="6" class="px-6 py-8 text-center text-slate-400 text-sm">
                  No se encontraron trabajadores en la búsqueda.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <TablePagination 
          :totalItems="filteredTrabajadores.length" 
          v-model:currentPage="currentPage" 
          v-model:perPage="perPage" 
        />
      </div>

      <!-- Teleported Slide-Over Drawer Form -->
      <Teleport to="body">
        <div v-if="isDrawerOpen" class="fixed inset-0 z-[9999] overflow-hidden">
          <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="isDrawerOpen = false"></div>

          <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
            <div class="w-screen max-w-lg bg-white shadow-2xl flex flex-col transform transition duration-300 border-l border-slate-200">
              
              <div class="p-6 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white">
                    <UserPlus v-if="!editingTrabajador" class="w-5 h-5" />
                    <Edit3 v-else class="w-5 h-5" />
                  </div>
                  <div>
                    <h3 class="font-extrabold text-lg text-slate-100">
                      {{ editingTrabajador ? 'Editar Trabajador' : 'Nuevo Trabajador' }}
                    </h3>
                    <span class="text-xs text-blue-300 block">Formulario de registro de personal</span>
                  </div>
                </div>
                <button @click="isDrawerOpen = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 cursor-pointer">
                  <X class="w-5 h-5" />
                </button>
              </div>

              <form @submit.prevent="submitForm" class="flex-1 overflow-y-auto p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">DNI *</label>
                    <input v-model="form.dni" type="text" maxlength="8" required class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none font-mono" placeholder="71234567" />
                    <span v-if="form.errors.dni" class="text-xs text-red-600 font-bold mt-1 block">{{ form.errors.dni }}</span>
                  </div>
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Empresa *</label>
                    <select v-model="form.empresa_id" required class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-900 font-semibold focus:ring-2 focus:ring-blue-500 outline-none">
                      <option value="" disabled>Seleccione Empresa</option>
                      <option v-for="e in empresas" :key="e.id" :value="e.id">{{ e.razon_social }}</option>
                    </select>
                    <span v-if="form.errors.empresa_id" class="text-xs text-red-600 font-bold mt-1 block">{{ form.errors.empresa_id }}</span>
                  </div>

                  <div class="col-span-2">
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Nombres *</label>
                    <input 
                      v-model="form.nombres" 
                      @input="e => handleUppercaseInput('nombres', e)"
                      type="text" 
                      required 
                      class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none uppercase" 
                      placeholder="JUAN CARLOS" 
                    />
                    <span v-if="form.errors.nombres" class="text-xs text-red-600 font-bold mt-1 block">{{ form.errors.nombres }}</span>
                  </div>

                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Apellido Paterno *</label>
                    <input 
                      v-model="form.apellido_paterno" 
                      @input="e => handleUppercaseInput('apellido_paterno', e)"
                      type="text" 
                      required 
                      class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none uppercase" 
                      placeholder="MENDOZA" 
                    />
                    <span v-if="form.errors.apellido_paterno" class="text-xs text-red-600 font-bold mt-1 block">{{ form.errors.apellido_paterno }}</span>
                  </div>
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Apellido Materno *</label>
                    <input 
                      v-model="form.apellido_materno" 
                      @input="e => handleUppercaseInput('apellido_materno', e)"
                      type="text" 
                      required 
                      class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none uppercase" 
                      placeholder="RAMOS" 
                    />
                    <span v-if="form.errors.apellido_materno" class="text-xs text-red-600 font-bold mt-1 block">{{ form.errors.apellido_materno }}</span>
                  </div>

                  <div class="col-span-2">
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Área de Trabajo *</label>
                    <input 
                      v-model="form.area" 
                      @input="e => handleUppercaseInput('area', e)"
                      type="text" 
                      required 
                      class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none uppercase" 
                      placeholder="OPERACIONES / MINA" 
                    />
                    <span v-if="form.errors.area" class="text-xs text-red-600 font-bold mt-1 block">{{ form.errors.area }}</span>
                  </div>

                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Cargo / Puesto</label>
                    <input v-model="form.cargo" type="text" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Supervisor de Campo" />
                    <span v-if="form.errors.cargo" class="text-xs text-red-600 font-bold mt-1 block">{{ form.errors.cargo }}</span>
                  </div>
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Teléfono Emergencia</label>
                    <input v-model="form.telefono_emergencia" type="text" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="987654321" />
                    <span v-if="form.errors.telefono_emergencia" class="text-xs text-red-600 font-bold mt-1 block">{{ form.errors.telefono_emergencia }}</span>
                  </div>
                </div>

                <div class="pt-4 border-t border-[var(--theme-border)] flex justify-end space-x-3">
                  <button type="button" @click="isDrawerOpen = false" class="cursor-pointer px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancelar</button>
                  <button type="submit" :disabled="form.processing" class="theme-btn-primary text-sm px-5 py-2.5 disabled:opacity-50">
                    <span v-if="form.processing">Guardando...</span>
                    <span v-else>{{ editingTrabajador ? 'Guardar Cambios' : 'Registrar Trabajador' }}</span>
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
        :title="trabajadorToToggle && (trabajadorToToggle.estado ?? 1) == 1 ? 'Inhabilitar Trabajador' : 'Reactivar Trabajador'"
        :message="trabajadorToToggle ? '¿Desea ' + ((trabajadorToToggle.estado ?? 1) == 1 ? 'desactivar' : 'reactivar') + ' al trabajador ' + trabajadorToToggle.nombres + ' ' + (trabajadorToToggle.apellidos || '') + '?' : ''"
        :confirmText="trabajadorToToggle && (trabajadorToToggle.estado ?? 1) == 1 ? 'Sí, Inhabilitar' : 'Sí, Reactivar'"
        :variant="trabajadorToToggle && (trabajadorToToggle.estado ?? 1) == 1 ? 'danger' : 'success'"
        @close="showConfirmModal = false"
        @confirm="executeToggleEstado"
      />
    </div>
  </AppLayout>
</template>