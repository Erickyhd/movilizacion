<script setup>
import { ref, computed } from 'vue';
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
const isDrawerOpen = ref(false);
const editingTrabajador = ref(null);

const filteredTrabajadores = computed(() => {
  return (props.trabajadores || []).filter(t => {
    const search = searchQuery.value.toLowerCase();
    const nombreCompleto = `${t.nombres} ${t.apellido_paterno || ''} ${t.apellido_materno || ''} ${t.apellidos || ''}`.toLowerCase();
    const dni = t.dni ? t.dni.toLowerCase() : '';
    const area = t.area ? t.area.toLowerCase() : '';
    const empresaNombre = t.empresa ? t.empresa.razon_social.toLowerCase() : '';

    const matchesSearch = nombreCompleto.includes(search) || dni.includes(search) || area.includes(search) || empresaNombre.includes(search);
    const matchesEmpresa = !filterEmpresa.value || t.empresa_id == filterEmpresa.value;

    return matchesSearch && matchesEmpresa;
  });
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
  form.grupo_sanguineo = 'O+';
  form.estado_acreditacion = 'APTO';
  if (props.empresas && props.empresas.length > 0) {
    form.empresa_id = props.empresas[0].id;
  }
  isDrawerOpen.value = true;
};

const openEditDrawer = (t) => {
  editingTrabajador.value = t;
  form.empresa_id = t.empresa_id;
  form.dni = t.dni;
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
      onSuccess: () => isDrawerOpen.value = false,
    });
  } else {
    form.post(route('trabajadores.store'), {
      onSuccess: () => isDrawerOpen.value = false,
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
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
          <h2 class="text-xl font-extrabold text-slate-900 flex items-center">
            <Users class="w-6 h-6 text-blue-600 mr-2.5" /> Registro e Historial de Personal
          </h2>
          <p class="text-sm text-slate-500 mt-1">Administración de padrón de trabajadores auditados y asignación de empresas contratistas.</p>
        </div>
        <button 
          v-if="canWrite"
          @click="openCreateDrawer"
          class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-md hover:shadow-blue-500/20 flex items-center space-x-2 transition cursor-pointer"
        >
          <UserPlus class="w-4 h-4" />
          <span>Nuevo Trabajador</span>
        </button>
      </div>

      <!-- Filters & Search Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center space-x-2">
          <select 
            v-model="filterEmpresa" 
            class="bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-700 font-semibold focus:ring-2 focus:ring-blue-500 outline-none shadow-sm"
          >
            <option value="">Todas las Empresas ({{ trabajadores.length }})</option>
            <option v-for="e in empresas" :key="e.id" :value="e.id">{{ e.razon_social }}</option>
          </select>
        </div>

        <div class="relative w-full sm:w-72">
          <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Buscar por DNI, Nombres, Apellidos o Área..." 
            class="w-full bg-white border border-slate-300 rounded-xl pl-9 pr-4 py-2 text-xs text-slate-900 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none shadow-sm"
          />
        </div>
      </div>

      <!-- Clean Trabajadores Table -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase border-b border-slate-100">
              <tr>
                <th class="px-6 py-3.5">DNI</th>
                <th class="px-6 py-3.5">Apellidos y Nombres</th>
                <th class="px-6 py-3.5">Empresa</th>
                <th class="px-6 py-3.5">Área de Trabajo</th>
                <th class="px-6 py-3.5">Estado Acreditación</th>
                <th v-if="canWrite" class="px-6 py-3.5 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="t in filteredTrabajadores" :key="t.id" :class="['hover:bg-slate-50/80 transition', (t.estado ?? 1) == 0 ? 'bg-red-50/30 opacity-75' : '']">
                <td class="px-6 py-4 font-mono font-extrabold text-slate-900">
                  {{ t.dni }}
                </td>
                <td class="px-6 py-4">
                  <span class="font-extrabold text-slate-900 block text-sm uppercase">
                    {{ t.apellido_paterno }} {{ t.apellido_materno }}, {{ t.nombres }}
                  </span>
                  <span v-if="t.cargo" class="text-[11px] text-slate-400 font-medium block">Puesto: {{ t.cargo }}</span>
                </td>
                <td class="px-6 py-4 text-slate-700 font-semibold">
                  <span class="inline-flex items-center text-xs">
                    <Building2 class="w-3.5 h-3.5 mr-1.5 text-slate-400" />
                    {{ t.empresa?.razon_social || 'Servicios Generales Magori' }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <span class="inline-flex items-center text-xs font-extrabold bg-blue-50 text-blue-800 px-2.5 py-1 rounded-lg border border-blue-200">
                    <Briefcase class="w-3.5 h-3.5 mr-1 text-blue-600" />
                    {{ t.area || 'OPERACIONES' }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <span 
                    :class="[
                      'px-2.5 py-1 rounded-full text-xs font-bold border inline-flex items-center',
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
                  </div>
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Empresa *</label>
                    <select v-model="form.empresa_id" required class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-900 font-semibold focus:ring-2 focus:ring-blue-500 outline-none">
                      <option value="" disabled>Seleccione Empresa</option>
                      <option v-for="e in empresas" :key="e.id" :value="e.id">{{ e.razon_social }}</option>
                    </select>
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
                  </div>

                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Cargo / Puesto</label>
                    <input v-model="form.cargo" type="text" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Supervisor de Campo" />
                  </div>
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Teléfono Emergencia</label>
                    <input v-model="form.telefono_emergencia" type="text" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="987654321" />
                  </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                  <button type="button" @click="isDrawerOpen = false" class="cursor-pointer px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancelar</button>
                  <button type="submit" :disabled="form.processing" class="cursor-pointer px-5 py-2.5 text-sm bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-500 shadow-md">
                    {{ editingTrabajador ? 'Guardar Cambios' : 'Registrar Trabajador' }}
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
        :message="trabajadorToToggle ? 'Desea ' + ((trabajadorToToggle.estado ?? 1) == 1 ? 'desactivar' : 'reactivar') + ' al trabajador ' + trabajadorToToggle.nombres + ' ' + (trabajadorToToggle.apellidos || '') + '?' : ''"
        :confirmText="trabajadorToToggle && (trabajadorToToggle.estado ?? 1) == 1 ? 'Sí, Inhabilitar' : 'Sí, Reactivar'"
        :variant="trabajadorToToggle && (trabajadorToToggle.estado ?? 1) == 1 ? 'danger' : 'success'"
        @close="showConfirmModal = false"
        @confirm="executeToggleEstado"
      />
    </div>
  </AppLayout>
</template>