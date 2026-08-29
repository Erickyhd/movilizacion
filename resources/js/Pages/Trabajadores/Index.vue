<script setup>
import { ref, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { UserCheck, UserPlus, Search, Edit3, Trash2, RotateCcw, X, PhoneCall, Briefcase } from 'lucide-vue-next';

const props = defineProps({
  trabajadores: Array,
  empresas: Array,
});

const page = usePage();
const canWrite = computed(() => {
  const perm = page.props.auth?.user?.permisos?.trabajadores;
  return perm === 'ESCRITURA' || page.props.auth?.user?.rol === 'ADMIN';
});

const activeTabFilter = ref('active'); // 'active' | 'inactive' | 'all'
const searchQuery = ref('');
const isDrawerOpen = ref(false);
const editingTrabajador = ref(null);

const filteredTrabajadores = computed(() => {
  return (props.trabajadores || []).filter(t => {
    const matchesFilter = 
      activeTabFilter.value === 'all' ? true :
      activeTabFilter.value === 'active' ? (t.estado ?? 1) == 1 :
      (t.estado ?? 1) == 0;

    const term = searchQuery.value.toLowerCase();
    const fullName = `${t.nombres} ${t.apellido_paterno || ''} ${t.apellido_materno || ''} ${t.apellidos || ''}`.toLowerCase();
    const dni = t.dni.toLowerCase();
    const empresa = (t.empresa?.razon_social || '').toLowerCase();
    const area = (t.area || '').toLowerCase();
    const cargo = (t.cargo || '').toLowerCase();

    const matchesSearch = fullName.includes(term) || dni.includes(term) || empresa.includes(term) || area.includes(term) || cargo.includes(term);

    return matchesFilter && matchesSearch;
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

const openCreateDrawer = () => {
  editingTrabajador.value = null;
  form.reset();
  form.grupo_sanguineo = 'O+';
  form.estado_acreditacion = 'APTO';
  isDrawerOpen.value = true;
};

const openEditDrawer = (t) => {
  editingTrabajador.value = t;
  form.empresa_id = t.empresa_id;
  form.dni = t.dni;
  form.nombres = t.nombres;
  form.apellido_paterno = t.apellido_paterno || (t.apellidos ? t.apellidos.split(' ')[0] : '');
  form.apellido_materno = t.apellido_materno || (t.apellidos && t.apellidos.split(' ').length > 1 ? t.apellidos.split(' ').slice(1).join(' ') : '');
  form.area = t.area || '';
  form.cargo = t.cargo || '';
  form.telefono_emergencia = t.telefono_emergencia || '';
  form.grupo_sanguineo = t.grupo_sanguineo || 'O+';
  form.estado_acreditacion = t.estado_acreditacion || 'APTO';
  isDrawerOpen.value = true;
};

const submitForm = () => {
  if (editingTrabajador.value) {
    form.put(route('trabajadores.update', editingTrabajador.value.id), {
      onSuccess: () => {
        form.reset();
        isDrawerOpen.value = false;
        editingTrabajador.value = null;
      },
    });
  } else {
    form.post(route('trabajadores.store'), {
      onSuccess: () => {
        form.reset();
        isDrawerOpen.value = false;
      },
    });
  }
};

const toggleEstado = (t) => {
  const accion = (t.estado ?? 1) == 1 ? 'desactivar' : 'reactivar';
  if (confirm(`¿Confirmas que deseas ${accion} al trabajador ${t.nombres} ${t.apellido_paterno || t.apellidos}?`)) {
    router.delete(route('trabajadores.destroy', t.id));
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
            <UserCheck class="w-6 h-6 text-blue-600 mr-2.5" /> Padrón de Trabajadores
          </h2>
          <p class="text-sm text-slate-500 mt-1">Registro de personal autorizado con separación de Apellidos Paterno/Materno y Área de Trabajo.</p>
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
      <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
        <!-- Filter Tabs -->
        <div class="flex bg-slate-200/70 p-1 rounded-xl w-full sm:w-auto">
          <button 
            @click="activeTabFilter = 'active'"
            :class="['px-4 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer', activeTabFilter === 'active' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900']"
          >
            Activos ({{ trabajadores.filter(t => (t.estado ?? 1) == 1).length }})
          </button>
          <button 
            @click="activeTabFilter = 'inactive'"
            :class="['px-4 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer', activeTabFilter === 'inactive' ? 'bg-white text-red-700 shadow-sm' : 'text-slate-600 hover:text-slate-900']"
          >
            Inactivos ({{ trabajadores.filter(t => (t.estado ?? 1) == 0).length }})
          </button>
          <button 
            @click="activeTabFilter = 'all'"
            :class="['px-4 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer', activeTabFilter === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900']"
          >
            Todos ({{ trabajadores.length }})
          </button>
        </div>

        <!-- Search input -->
        <div class="relative w-full sm:w-72">
          <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Buscar por DNI, Nombre, Apellidos o Área..." 
            class="w-full bg-white border border-slate-300 rounded-xl pl-9 pr-4 py-2 text-xs text-slate-900 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none shadow-sm"
          />
        </div>
      </div>

      <!-- Trabajadores Table -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase border-b border-slate-100">
              <tr>
                <th class="px-6 py-3.5">DNI</th>
                <th class="px-6 py-3.5">Nombres y Apellidos</th>
                <th class="px-6 py-3.5">Empresa</th>
                <th class="px-6 py-3.5">Área / Cargo</th>
                <th class="px-6 py-3.5">Contacto Emergencia</th>
                <th v-if="canWrite" class="px-6 py-3.5 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="t in filteredTrabajadores" :key="t.id" :class="['hover:bg-slate-50/80 transition', (t.estado ?? 1) == 0 ? 'bg-red-50/30 opacity-75' : '']">
                <td class="px-6 py-4 font-mono font-bold text-slate-900">{{ t.dni }}</td>
                <td class="px-6 py-4">
                  <span class="font-semibold text-slate-900 block">{{ t.nombres }}</span>
                  <span class="text-xs text-slate-500 block font-medium">
                    {{ t.apellido_paterno || '' }} {{ t.apellido_materno || '' }} {{ (!t.apellido_paterno && !t.apellido_materno) ? t.apellidos : '' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-slate-700 font-medium">{{ t.empresa?.razon_social }}</td>
                <td class="px-6 py-4">
                  <span v-if="t.area" class="font-bold text-blue-700 block text-xs bg-blue-50 px-2 py-0.5 rounded border border-blue-100 w-fit">{{ t.area }}</span>
                  <span class="text-slate-600 text-xs">{{ t.cargo || '-' }}</span>
                </td>
                <td class="px-6 py-4 text-xs font-semibold text-slate-600">
                  <span v-if="t.telefono_emergencia" class="inline-flex items-center text-slate-700">
                    <PhoneCall class="w-3.5 h-3.5 mr-1 text-blue-500" /> {{ t.telefono_emergencia }}
                  </span>
                  <span v-else class="text-slate-400">-</span>
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
                    @click="toggleEstado(t)"
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
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">DNI</label>
                    <input v-model="form.dni" type="text" maxlength="15" required class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none font-mono" placeholder="71234567" />
                  </div>
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Empresa</label>
                    <select v-model="form.empresa_id" required class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-900 font-semibold focus:ring-2 focus:ring-blue-500 outline-none">
                      <option value="" disabled>Seleccione Empresa</option>
                      <option v-for="e in empresas" :key="e.id" :value="e.id">{{ e.razon_social }}</option>
                    </select>
                  </div>

                  <div class="col-span-2">
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Nombres</label>
                    <input v-model="form.nombres" type="text" required class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Juan Carlos" />
                  </div>

                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Apellido Paterno</label>
                    <input v-model="form.apellido_paterno" type="text" required class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Mendoza" />
                  </div>
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Apellido Materno</label>
                    <input v-model="form.apellido_materno" type="text" required class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Quispe" />
                  </div>

                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Área de Trabajo</label>
                    <input v-model="form.area" type="text" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Operaciones / Mina" />
                  </div>
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Cargo / Puesto</label>
                    <input v-model="form.cargo" type="text" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Supervisor de Campo" />
                  </div>

                  <div class="col-span-2">
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Teléfono de Emergencia</label>
                    <input v-model="form.telefono_emergencia" type="text" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="987654321" />
                  </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                  <button type="button" @click="isDrawerOpen = false" class="px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancelar</button>
                  <button type="submit" :disabled="form.processing" class="px-5 py-2.5 text-sm bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-500 shadow-md">
                    {{ editingTrabajador ? 'Guardar Cambios' : 'Registrar Trabajador' }}
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