<script setup>
import { ref, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { MapPin, Plus, Clock, Navigation, Search, Edit3, Trash2, RotateCcw, X, Building } from 'lucide-vue-next';

const props = defineProps({
  rutas: Array,
});

const page = usePage();
const canWrite = computed(() => {
  const perm = page.props.auth?.user?.permisos?.rutas;
  return perm === 'ESCRITURA' || page.props.auth?.user?.rol === 'ADMIN';
});

const activeTabFilter = ref('active'); // 'active' | 'inactive' | 'all'
const searchQuery = ref('');
const isDrawerOpen = ref(false);
const editingRuta = ref(null);

const filteredRutas = computed(() => {
  return (props.rutas || []).filter(r => {
    const matchesFilter = 
      activeTabFilter.value === 'all' ? true :
      activeTabFilter.value === 'active' ? Boolean(r.activa) :
      !r.activa;

    const term = searchQuery.value.toLowerCase();
    const matchesSearch = r.origen.toLowerCase().includes(term) || (r.destino || '').toLowerCase().includes(term);

    return matchesFilter && matchesSearch;
  });
});

const form = useForm({
  origen: '',
  destino: '',
  duracion_estimada_minutos: 120,
});

const openCreateDrawer = () => {
  editingRuta.value = null;
  form.reset();
  form.duracion_estimada_minutos = 120;
  isDrawerOpen.value = true;
};

const openEditDrawer = (r) => {
  editingRuta.value = r;
  form.origen = r.origen;
  form.destino = r.destino || r.origen;
  form.duracion_estimada_minutos = r.duracion_estimada_minutos || 120;
  isDrawerOpen.value = true;
};

const submitForm = () => {
  if (!form.destino) {
    form.destino = form.origen;
  }
  if (editingRuta.value) {
    form.put(route('rutas.update', editingRuta.value.id), {
      onSuccess: () => {
        form.reset();
        isDrawerOpen.value = false;
        editingRuta.value = null;
      },
    });
  } else {
    form.post(route('rutas.store'), {
      onSuccess: () => {
        form.reset();
        isDrawerOpen.value = false;
      },
    });
  }
};

const toggleEstado = (r) => {
  const accion = r.activa ? 'desactivar' : 'reactivar';
  if (confirm(`¿Confirmas que deseas ${accion} el punto ${r.origen}?`)) {
    router.delete(route('rutas.destroy', r.id));
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
            <MapPin class="w-6 h-6 text-blue-600 mr-2.5" /> Catálogo de Puntos y Localidades
          </h2>
          <p class="text-sm text-slate-500 mt-1">Configuración de terminales, bases operativas y puntos de embarque/desembarque.</p>
        </div>
        <button 
          v-if="canWrite"
          @click="openCreateDrawer"
          class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-md hover:shadow-blue-500/20 flex items-center space-x-2 transition cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>Nuevo Punto / Localidad</span>
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
            Activos ({{ rutas.filter(r => r.activa).length }})
          </button>
          <button 
            @click="activeTabFilter = 'inactive'"
            :class="['px-4 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer', activeTabFilter === 'inactive' ? 'bg-white text-red-700 shadow-sm' : 'text-slate-600 hover:text-slate-900']"
          >
            Inactivos ({{ rutas.filter(r => !r.activa).length }})
          </button>
          <button 
            @click="activeTabFilter = 'all'"
            :class="['px-4 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer', activeTabFilter === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900']"
          >
            Todos ({{ rutas.length }})
          </button>
        </div>

        <!-- Search input -->
        <div class="relative w-full sm:w-72">
          <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Buscar Punto o Localidad..." 
            class="w-full bg-white border border-slate-300 rounded-xl pl-9 pr-4 py-2 text-xs text-slate-900 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none shadow-sm"
          />
        </div>
      </div>

      <!-- Rutas Table -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase border-b border-slate-100">
              <tr>
                <th class="px-6 py-3.5">Punto / Localidad</th>
                <th class="px-6 py-3.5">Referencia / Conexión</th>
                <th class="px-6 py-3.5">Tiempo Ref. (Minutos)</th>
                <th class="px-6 py-3.5">Estado</th>
                <th v-if="canWrite" class="px-6 py-3.5 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="r in filteredRutas" :key="r.id" :class="['hover:bg-slate-50/80 transition', !r.activa ? 'bg-red-50/30 opacity-75' : '']">
                <td class="px-6 py-4 font-extrabold text-slate-900 flex items-center">
                  <Navigation class="w-4 h-4 text-blue-600 mr-2" /> {{ r.origen }}
                </td>
                <td class="px-6 py-4 font-semibold text-slate-700">
                  <span v-if="r.destino && r.destino !== r.origen" class="text-slate-800 font-bold">➔ {{ r.destino }}</span>
                  <span v-else class="text-slate-400 text-xs font-normal">Punto Terminal</span>
                </td>
                <td class="px-6 py-4 text-slate-700">
                  <span class="inline-flex items-center text-xs font-bold bg-slate-100 px-2.5 py-1 rounded-lg text-slate-700">
                    <Clock class="w-3.5 h-3.5 mr-1 text-slate-400" />
                    {{ r.duracion_estimada_minutos || 120 }} m
                  </span>
                </td>
                <td class="px-6 py-4">
                  <span v-if="r.activa" class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Activo</span>
                  <span v-else class="px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">Inactivo</span>
                </td>
                <td v-if="canWrite" class="px-6 py-4 text-right space-x-1 whitespace-nowrap">
                  <button 
                    @click="openEditDrawer(r)"
                    title="Editar punto"
                    class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50/80 rounded-lg transition cursor-pointer"
                  >
                    <Edit3 class="w-3.5 h-3.5" />
                  </button>
                  <button 
                    @click="toggleEstado(r)"
                    :title="r.activa ? 'Desactivar punto' : 'Reactivar punto'"
                    :class="[
                      'p-1.5 rounded-lg transition cursor-pointer',
                      r.activa 
                        ? 'text-slate-400 hover:text-red-600 hover:bg-red-50/80' 
                        : 'text-slate-400 hover:text-emerald-600 hover:bg-emerald-50/80'
                    ]"
                  >
                    <component :is="r.activa ? Trash2 : RotateCcw" class="w-3.5 h-3.5" />
                  </button>
                </td>
              </tr>
              <tr v-if="!filteredRutas || filteredRutas.length === 0">
                <td colspan="5" class="px-6 py-8 text-center text-slate-400 text-sm">
                  No se encontraron puntos o localidades en la búsqueda.
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
            <div class="w-screen max-w-md bg-white shadow-2xl flex flex-col transform transition duration-300 border-l border-slate-200">
              
              <div class="p-6 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white">
                    <MapPin class="w-5 h-5" />
                  </div>
                  <div>
                    <h3 class="font-extrabold text-lg text-slate-100">
                      {{ editingRuta ? 'Editar Punto / Localidad' : 'Nuevo Punto / Localidad' }}
                    </h3>
                    <span class="text-xs text-blue-300 block">Catálogo de orígenes y destinos</span>
                  </div>
                </div>
                <button @click="isDrawerOpen = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 cursor-pointer">
                  <X class="w-5 h-5" />
                </button>
              </div>

              <form @submit.prevent="submitForm" class="flex-1 overflow-y-auto p-6 space-y-5">
                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Nombre del Punto / Localidad</label>
                  <input v-model="form.origen" type="text" required class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Huancayo (Base Central) o Mina Las Bambas" />
                </div>
                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Punto de Destino Referencial (Opcional)</label>
                  <input v-model="form.destino" type="text" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Dejar igual o indicar conexión referencial" />
                </div>
                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Tiempo Estimado (Minutos)</label>
                  <input v-model="form.duracion_estimada_minutos" type="number" min="1" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="120" />
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                  <button type="button" @click="isDrawerOpen = false" class="px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancelar</button>
                  <button type="submit" :disabled="form.processing" class="px-5 py-2.5 text-sm bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-500 shadow-md">
                    {{ editingRuta ? 'Guardar Cambios' : 'Registrar Punto' }}
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