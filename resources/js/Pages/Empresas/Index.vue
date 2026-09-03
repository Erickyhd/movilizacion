<script setup>
import { ref, computed, watch } from 'vue';
import TablePagination from '@/Components/TablePagination.vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { 
  Building2, 
  Plus, 
  Search, 
  Edit3, 
  Trash2, 
  RotateCcw, 
  X
} from 'lucide-vue-next';

const props = defineProps({
  empresas: Array,
});

const page = usePage();
const canWrite = computed(() => {
  const perm = page.props.auth?.user?.permisos?.empresas;
  return perm === 'ESCRITURA' || page.props.auth?.user?.rol === 'ADMIN';
});

const searchQuery = ref('');
const filterStatus = ref('active');
const isDrawerOpen = ref(false);
const editingEmpresa = ref(null);
const currentPage = ref(1);
const perPage = ref(15);

watch([searchQuery, filterStatus], () => {
  currentPage.value = 1;
});

const totalPages = computed(() => Math.ceil(filteredEmpresas.value.length / perPage.value) || 1);

const paginatedEmpresas = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  return filteredEmpresas.value.slice(start, start + perPage.value);
});

// Confirmation Modal State
const showConfirmModal = ref(false);
const empresaToToggle = ref(null);

const filteredEmpresas = computed(() => {
  return (props.empresas || []).filter(e => {
    const search = searchQuery.value.toLowerCase();
    const ruc = e.ruc ? e.ruc.toLowerCase() : '';
    const razon = e.razon_social ? e.razon_social.toLowerCase() : '';

    const matchesSearch = ruc.includes(search) || razon.includes(search);
    const matchesStatus = filterStatus.value === 'all' || 
                          (filterStatus.value === 'active' && (e.estado ?? 1) == 1) || 
                          (filterStatus.value === 'inactive' && (e.estado ?? 1) == 0);

    return matchesSearch && matchesStatus;
  });
});

const form = useForm({
  ruc: '',
  razon_social: '',
  observaciones: '',
});

const handleUppercaseInput = (field, event) => {
  form[field] = (event.target.value || '').toUpperCase();
};

const openCreateDrawer = () => {
  editingEmpresa.value = null;
  form.reset();
  form.clearErrors();
  isDrawerOpen.value = true;
};

const openEditDrawer = (e) => {
  editingEmpresa.value = e;
  form.clearErrors();
  form.ruc = e.ruc || '';
  form.razon_social = e.razon_social || '';
  form.observaciones = e.observaciones || '';
  isDrawerOpen.value = true;
};

const submitForm = () => {
  form.razon_social = (form.razon_social || '').toUpperCase();

  if (editingEmpresa.value) {
    form.put(route('empresas.update', editingEmpresa.value.id), {
      onSuccess: () => {
        isDrawerOpen.value = false;
        form.reset();
        form.clearErrors();
      },
    });
  } else {
    form.post(route('empresas.store'), {
      onSuccess: () => {
        isDrawerOpen.value = false;
        form.reset();
        form.clearErrors();
      },
    });
  }
};

const confirmToggleEstado = (e) => {
  empresaToToggle.value = e;
  showConfirmModal.value = true;
};

const executeToggleEstado = () => {
  if (empresaToToggle.value) {
    router.delete(route('empresas.destroy', empresaToToggle.value.id), {
      onSuccess: () => {
        showConfirmModal.value = false;
        empresaToToggle.value = null;
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
            <Building2 class="w-6 h-6 text-blue-600 mr-2.5" /> Catálogo de Empresas
          </h2>
          <p class="text-sm text-slate-500 mt-1">Directorio oficial de empresas asociadas y contratistas para movilización de personal.</p>
        </div>
        <button 
          v-if="canWrite"
          @click="openCreateDrawer"
          class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-md hover:shadow-blue-500/20 flex items-center space-x-2 transition cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>Nueva Empresa</span>
        </button>
      </div>

      <!-- Filters & Search Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50 p-2.5 rounded-2xl border border-slate-200/80">
          <div class="flex bg-slate-200/70 p-1 rounded-xl w-full sm:w-auto">
            <button 
              @click="filterStatus = 'active'"
              :class="['px-4 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer', filterStatus === 'active' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900']"
            >
              Activas ({{ (empresas || []).filter(e => (e.estado ?? 1) == 1).length }})
            </button>
            <button 
              @click="filterStatus = 'inactive'"
              :class="['px-4 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer', filterStatus === 'inactive' ? 'bg-white text-red-700 shadow-sm' : 'text-slate-600 hover:text-slate-900']"
            >
              Inactivas ({{ (empresas || []).filter(e => (e.estado ?? 1) == 0).length }})
            </button>
            <button 
              @click="filterStatus = 'all'"
              :class="['px-4 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer', filterStatus === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900']"
            >
              Todas ({{ (empresas || []).length }})
            </button>
          </div>

          <div class="relative w-full sm:w-72">
            <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
            <input 
              v-model="searchQuery" 
              type="text" 
              placeholder="Buscar por RUC o Razón Social..." 
              class="w-full bg-white border border-slate-300 rounded-xl pl-9 pr-4 py-2 text-xs text-slate-900 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none shadow-sm"
            />
          </div>
        </div>

        <!-- Clean Empresas Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase border-b border-slate-100">
              <tr>
                <th class="px-6 py-3.5">RUC</th>
                <th class="px-6 py-3.5">Razón Social</th>
                <th class="px-6 py-3.5">Estado</th>
                <th v-if="canWrite" class="px-6 py-3.5 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="e in filteredEmpresas" :key="e.id" :class="['hover:bg-slate-50/80 transition', (e.estado ?? 1) == 0 ? 'bg-red-50/30 opacity-75' : '']">
                <td class="px-6 py-4 font-mono font-extrabold text-slate-900">
                  {{ e.ruc || '-' }}
                </td>
                <td class="px-6 py-4 font-extrabold text-slate-900 uppercase">
                  {{ e.razon_social }}
                </td>
                <td class="px-6 py-4">
                  <span v-if="(e.estado ?? 1) == 1" class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Activo</span>
                  <span v-else class="px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">Inactivo</span>
                </td>
                <td v-if="canWrite" class="px-6 py-4 text-right space-x-1 whitespace-nowrap">
                  <button 
                    @click="openEditDrawer(e)"
                    title="Editar empresa"
                    class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50/80 rounded-lg transition cursor-pointer"
                  >
                    <Edit3 class="w-3.5 h-3.5" />
                  </button>
                  <button 
                    @click="confirmToggleEstado(e)"
                    :title="(e.estado ?? 1) == 1 ? 'Desactivar empresa' : 'Reactivar empresa'"
                    :class="[
                      'p-1.5 rounded-lg transition cursor-pointer',
                      (e.estado ?? 1) == 1 
                        ? 'text-slate-400 hover:text-red-600 hover:bg-red-50/80' 
                        : 'text-slate-400 hover:text-emerald-600 hover:bg-emerald-50/80'
                    ]"
                  >
                    <component :is="(e.estado ?? 1) == 1 ? Trash2 : RotateCcw" class="w-3.5 h-3.5" />
                  </button>
                </td>
              </tr>
              <tr v-if="!filteredEmpresas || filteredEmpresas.length === 0">
                <td colspan="4" class="px-6 py-8 text-center text-slate-400 text-sm">
                  No se encontraron empresas en la búsqueda.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <TablePagination 
          :totalItems="filteredEmpresas.length" 
          v-model:currentPage="currentPage" 
          v-model:perPage="perPage" 
        />
      </div>

      <!-- Teleported Slide-Over Drawer Form -->
      <Teleport to="body">
        <div v-if="isDrawerOpen" class="fixed inset-0 z-[9999] overflow-hidden">
          <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="isDrawerOpen = false"></div>

          <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
            <div class="w-screen max-w-md bg-white shadow-2xl flex flex-col transform transition duration-300 border-l border-slate-200">
              
              <div class="p-6 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white">
                    <Building2 class="w-5 h-5" />
                  </div>
                  <div>
                    <h3 class="font-extrabold text-lg text-slate-100">
                      {{ editingEmpresa ? 'Editar Empresa' : 'Nueva Empresa' }}
                    </h3>
                    <span class="text-xs text-blue-300 block">Registro de datos de la empresa</span>
                  </div>
                </div>
                <button @click="isDrawerOpen = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 cursor-pointer">
                  <X class="w-5 h-5" />
                </button>
              </div>

              <form @submit.prevent="submitForm" class="flex-1 overflow-y-auto p-6 space-y-4">
                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">RUC <span class="text-slate-400 font-normal">(Opcional)</span></label>
                  <input v-model="form.ruc" type="text" maxlength="11" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none font-mono" placeholder="20123456789" />
                  <span v-if="form.errors.ruc" class="text-xs text-red-600 font-bold mt-1 block">{{ form.errors.ruc }}</span>
                </div>

                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Razón Social *</label>
                  <input 
                    v-model="form.razon_social" 
                    @input="e => handleUppercaseInput('razon_social', e)"
                    type="text" 
                    required 
                    class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none uppercase" 
                    placeholder="CONSORCIO MINERO S.A.C." 
                  />
                  <span v-if="form.errors.razon_social" class="text-xs text-red-600 font-bold mt-1 block">{{ form.errors.razon_social }}</span>
                </div>

                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Observaciones / Notas <span class="text-slate-400 font-normal">(Opcional)</span></label>
                  <textarea v-model="form.observaciones" rows="3" class="w-full bg-white border border-slate-300 rounded-xl p-3 text-xs text-slate-900 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none shadow-inner" placeholder="Notas internas sobre la empresa..."></textarea>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                  <button type="button" @click="isDrawerOpen = false" class="cursor-pointer px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancelar</button>
                  <button type="submit" :disabled="form.processing" class="cursor-pointer px-5 py-2.5 text-sm bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-500 shadow-md">
                    {{ editingEmpresa ? 'Guardar Cambios' : 'Registrar Empresa' }}
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
        :title="empresaToToggle && (empresaToToggle.estado ?? 1) == 1 ? 'Inhabilitar Empresa' : 'Reactivar Empresa'"
        :message="empresaToToggle ? 'Desea ' + ((empresaToToggle.estado ?? 1) == 1 ? 'desactivar' : 'reactivar') + ' a la empresa ' + empresaToToggle.razon_social + '?' : ''"
        :confirmText="empresaToToggle && (empresaToToggle.estado ?? 1) == 1 ? 'Sí, Inhabilitar' : 'Sí, Reactivar'"
        :variant="empresaToToggle && (empresaToToggle.estado ?? 1) == 1 ? 'danger' : 'success'"
        @close="showConfirmModal = false"
        @confirm="executeToggleEstado"
      />

    </div>
  </AppLayout>
</template>