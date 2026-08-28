<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Building2, Plus, Search, Edit3, Trash2, RotateCcw, X } from 'lucide-vue-next';

const props = defineProps({
  empresas: Array,
});

const activeTabFilter = ref('active'); // 'active' | 'inactive' | 'all'
const searchQuery = ref('');
const isDrawerOpen = ref(false);
const editingEmpresa = ref(null);

const filteredEmpresas = computed(() => {
  return (props.empresas || []).filter(e => {
    const matchesFilter = 
      activeTabFilter.value === 'all' ? true :
      activeTabFilter.value === 'active' ? (e.estado ?? 1) == 1 :
      (e.estado ?? 1) == 0;

    const term = searchQuery.value.toLowerCase();
    const matchesSearch = e.ruc.toLowerCase().includes(term) ||
                          e.razon_social.toLowerCase().includes(term);

    return matchesFilter && matchesSearch;
  });
});

const form = useForm({
  ruc: '',
  razon_social: '',
  es_contratista: true,
});

const openCreateDrawer = () => {
  editingEmpresa.value = null;
  form.reset();
  form.es_contratista = true;
  isDrawerOpen.value = true;
};

const openEditDrawer = (e) => {
  editingEmpresa.value = e;
  form.ruc = e.ruc;
  form.razon_social = e.razon_social;
  form.es_contratista = Boolean(e.es_contratista);
  isDrawerOpen.value = true;
};

const submitForm = () => {
  if (editingEmpresa.value) {
    form.put(route('empresas.update', editingEmpresa.value.id), {
      onSuccess: () => {
        form.reset();
        isDrawerOpen.value = false;
        editingEmpresa.value = null;
      },
    });
  } else {
    form.post(route('empresas.store'), {
      onSuccess: () => {
        form.reset();
        isDrawerOpen.value = false;
      },
    });
  }
};

const toggleEstado = (e) => {
  const accion = (e.estado ?? 1) == 1 ? 'desactivar' : 'reactivar';
  if (confirm(`¿Confirmas que deseas ${accion} la empresa ${e.razon_social}?`)) {
    router.delete(route('empresas.destroy', e.id));
  }
};
</script>

<template>
  <AppLayout>
    <div class="w-full space-y-6">
      
      <!-- Top Header & Actions -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
          <h2 class="text-xl font-extrabold text-slate-900 flex items-center">
            <Building2 class="w-6 h-6 text-blue-600 mr-2.5" /> Empresas y Contratistas
          </h2>
          <p class="text-sm text-slate-500 mt-1">Directorio de empresas titulares y contratistas para asignación de trabajadores.</p>
        </div>
        <button 
          @click="openCreateDrawer"
          class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-md hover:shadow-blue-500/20 flex items-center space-x-2 transition cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>Nueva Empresa</span>
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
            Activas ({{ empresas.filter(e => (e.estado ?? 1) == 1).length }})
          </button>
          <button 
            @click="activeTabFilter = 'inactive'"
            :class="['px-4 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer', activeTabFilter === 'inactive' ? 'bg-white text-red-700 shadow-sm' : 'text-slate-600 hover:text-slate-900']"
          >
            Inactivas ({{ empresas.filter(e => (e.estado ?? 1) == 0).length }})
          </button>
          <button 
            @click="activeTabFilter = 'all'"
            :class="['px-4 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer', activeTabFilter === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900']"
          >
            Todas ({{ empresas.length }})
          </button>
        </div>

        <!-- Search input -->
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

      <!-- Empresas Table -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase border-b border-slate-100">
              <tr>
                <th class="px-6 py-3.5">RUC</th>
                <th class="px-6 py-3.5">Razón Social</th>
                <th class="px-6 py-3.5">Tipo de Empresa</th>
                <th class="px-6 py-3.5">Trabajadores Asignados</th>
                <th class="px-6 py-3.5">Estado</th>
                <th class="px-6 py-3.5 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="e in filteredEmpresas" :key="e.id" :class="['hover:bg-slate-50/80 transition', (e.estado ?? 1) == 0 ? 'bg-red-50/30 opacity-75' : '']">
                <td class="px-6 py-4 font-mono font-bold text-slate-900">{{ e.ruc }}</td>
                <td class="px-6 py-4 font-semibold text-slate-800">{{ e.razon_social }}</td>
                <td class="px-6 py-4">
                  <span v-if="e.es_contratista" class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                    Contratista / Tercero
                  </span>
                  <span v-else class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-800 border border-blue-200">
                    Empresa Principal
                  </span>
                </td>
                <td class="px-6 py-4 text-slate-700 font-bold">
                  {{ e.trabajadores_count || 0 }} trabajadores
                </td>
                <td class="px-6 py-4">
                  <span v-if="(e.estado ?? 1) == 1" class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Activa</span>
                  <span v-else class="px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">Inactiva</span>
                </td>
                <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                  <button 
                    @click="openEditDrawer(e)"
                    title="Editar empresa"
                    class="p-2 text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 border border-blue-200/80 rounded-xl transition cursor-pointer"
                  >
                    <Edit3 class="w-4 h-4" />
                  </button>
                  <button 
                    @click="toggleEstado(e)"
                    :title="(e.estado ?? 1) == 1 ? 'Desactivar empresa' : 'Reactivar empresa'"
                    :class="[
                      'p-2 rounded-xl border transition cursor-pointer',
                      (e.estado ?? 1) == 1 
                        ? 'text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 border-red-200/80' 
                        : 'text-emerald-600 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border-emerald-200/80'
                    ]"
                  >
                    <component :is="(e.estado ?? 1) == 1 ? Trash2 : RotateCcw" class="w-4 h-4" />
                  </button>
                </td>
              </tr>
              <tr v-if="!filteredEmpresas || filteredEmpresas.length === 0">
                <td colspan="6" class="px-6 py-8 text-center text-slate-400 text-sm">
                  No se encontraron empresas en la búsqueda.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Slide-Over Drawer Form -->
      <div v-if="isDrawerOpen" class="fixed inset-0 z-50 overflow-hidden">
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
                  <span class="text-xs text-blue-300 block">Formulario de registro</span>
                </div>
              </div>
              <button @click="isDrawerOpen = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 cursor-pointer">
                <X class="w-5 h-5" />
              </button>
            </div>

            <form @submit.prevent="submitForm" class="flex-1 overflow-y-auto p-6 space-y-5">
              <div>
                <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">RUC (11 DÍGITOS)</label>
                <input v-model="form.ruc" type="text" maxlength="11" required class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none font-mono" placeholder="20123456789" />
              </div>
              <div>
                <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">RAZÓN SOCIAL</label>
                <input v-model="form.razon_social" type="text" required class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Servicios Mineros S.A.C." />
              </div>
              <div class="flex items-center space-x-2 pt-2">
                <input v-model="form.es_contratista" type="checkbox" id="es_c_drawer" class="w-4 h-4 rounded text-blue-600 border-slate-300 cursor-pointer" />
                <label for="es_c_drawer" class="text-sm text-slate-800 font-semibold cursor-pointer">Es Empresa Contratista / Tercero</label>
              </div>

              <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                <button type="button" @click="isDrawerOpen = false" class="px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancelar</button>
                <button type="submit" :disabled="form.processing" class="px-5 py-2.5 text-sm bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-500 shadow-md">
                  {{ editingEmpresa ? 'Guardar Cambios' : 'Registrar Empresa' }}
                </button>
              </div>
            </form>

          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>