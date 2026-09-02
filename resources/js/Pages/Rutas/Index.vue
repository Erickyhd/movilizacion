<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { 
  MapPin, 
  Plus, 
  Search, 
  Edit3, 
  Trash2, 
  RotateCcw, 
  Clock, 
  X, 
  Navigation,
  Compass
} from 'lucide-vue-next';

const props = defineProps({
  rutas: Array,
});

const page = usePage();
const canWrite = computed(() => {
  const perm = page.props.auth?.user?.permisos?.rutas;
  return perm === 'ESCRITURA' || page.props.auth?.user?.rol === 'ADMIN';
});

const departamentosPeru = [
  'AMAZONAS', 'ANCASH', 'APURÍMAC', 'AREQUIPA', 'AYACUCHO', 
  'CAJAMARCA', 'CALLAO', 'CUSCO', 'HUANCAVELICA', 'HUÁNUCO', 
  'ICA', 'JUNÍN', 'LA LIBERTAD', 'LAMBAYEQUE', 'LIMA', 
  'LORETO', 'MADRE DE DIOS', 'MOQUEGUA', 'PASCO', 'PIURA', 
  'PUNO', 'SAN MARTÍN', 'TACNA', 'TUMBES', 'UCAYALI'
];

const searchQuery = ref('');
const filterStatus = ref('active');
const isDrawerOpen = ref(false);
const editingRuta = ref(null);
const currentPage = ref(1);
const perPage = ref(15);

watch([searchQuery, filterStatus], () => {
  currentPage.value = 1;
});

const totalPages = computed(() => Math.ceil(filteredRutas.value.length / perPage.value) || 1);

const paginatedRutas = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  return filteredRutas.value.slice(start, start + perPage.value);
});

// Confirm Modal state
const showConfirmModal = ref(false);
const rutaToToggle = ref(null);

const filteredRutas = computed(() => {
  return (props.rutas || []).filter(r => {
    const search = searchQuery.value.toLowerCase();
    const punto = (r.origen || '').toLowerCase();
    const depto = (r.departamento || '').toLowerCase();

    const matchesSearch = punto.includes(search) || depto.includes(search);
    const matchesStatus = filterStatus.value === 'all' || (filterStatus.value === 'active' && r.activa) || (filterStatus.value === 'inactive' && !r.activa);

    return matchesSearch && matchesStatus;
  });
});

const form = useForm({
  origen: '',
  departamento: 'JUNÍN',
  duracion_estimada_minutos: '',
  distancia_km: '',
  observaciones: '',
});

const handleUppercaseInput = (field, event) => {
  form[field] = (event.target.value || '').toUpperCase();
};

const openCreateDrawer = () => {
  editingRuta.value = null;
  form.reset();
  form.clearErrors();
  form.departamento = 'JUNÍN';
  isDrawerOpen.value = true;
};

const openEditDrawer = (r) => {
  editingRuta.value = r;
  form.origen = r.origen || '';
  form.departamento = r.departamento || 'JUNÍN';
  form.duracion_estimada_minutos = r.duracion_estimada_minutos || '';
  form.distancia_km = r.distancia_km || '';
  form.observaciones = r.observaciones || '';
  isDrawerOpen.value = true;
};

const submitForm = () => {
  form.origen = (form.origen || '').toUpperCase();

  if (editingRuta.value) {
    form.put(route('rutas.update', editingRuta.value.id), {
      onSuccess: () => {
        isDrawerOpen.value = false;
        form.reset();
        form.clearErrors();
      },
    });
  } else {
    form.post(route('rutas.store'), {
      onSuccess: () => {
        isDrawerOpen.value = false;
        form.reset();
        form.clearErrors();
      },
    });
  }
};

const confirmToggleEstado = (r) => {
  rutaToToggle.value = r;
  showConfirmModal.value = true;
};

const executeToggleEstado = () => {
  if (rutaToToggle.value) {
    router.delete(route('rutas.destroy', rutaToToggle.value.id), {
      onSuccess: () => {
        showConfirmModal.value = false;
        rutaToToggle.value = null;
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
            <MapPin class="w-6 h-6 text-blue-600 mr-2.5" /> Catálogo de Puntos y Localidades de Traslado
          </h2>
          <p class="text-sm text-slate-500 mt-1">Directorio de orígenes, destinos y campamentos por departamento.</p>
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
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50 p-2.5 rounded-2xl border border-slate-200/80">
          <div class="flex bg-slate-200/70 p-1 rounded-xl w-full sm:w-auto">
            <button 
              @click="filterStatus = 'active'"
              :class="['px-4 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer', filterStatus === 'active' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900']"
            >
              Activas ({{ (rutas || []).filter(r => r.activa).length }})
            </button>
            <button 
              @click="filterStatus = 'inactive'"
              :class="['px-4 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer', filterStatus === 'inactive' ? 'bg-white text-red-700 shadow-sm' : 'text-slate-600 hover:text-slate-900']"
            >
              Inactivas ({{ (rutas || []).filter(r => !r.activa).length }})
            </button>
            <button 
              @click="filterStatus = 'all'"
              :class="['px-4 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer', filterStatus === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900']"
            >
              Todas ({{ (rutas || []).length }})
            </button>
          </div>

          <div class="relative w-full sm:w-72">
            <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
            <input 
              v-model="searchQuery" 
              type="text" 
              placeholder="Buscar Punto o Departamento..." 
              class="w-full bg-white border border-slate-300 rounded-xl pl-9 pr-4 py-2 text-xs text-slate-900 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none shadow-sm"
            />
          </div>
        </div>

        <!-- Clean Rutas & Puntos Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase border-b border-slate-100">
              <tr>
                <th class="px-6 py-3.5">Punto / Localidad</th>
                <th class="px-6 py-3.5">Departamento / Región</th>
                <th class="px-6 py-3.5">Distancia (Km)</th>
                <th class="px-6 py-3.5">Tiempo Estimado</th>
                <th class="px-6 py-3.5">Estado</th>
                <th v-if="canWrite" class="px-6 py-3.5 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="r in paginatedRutas" :key="r.id" :class="['hover:bg-slate-50/80 transition', !r.activa ? 'bg-red-50/30 opacity-75' : '']">
                <td class="px-6 py-4 font-extrabold text-slate-900 flex items-center">
                  <Navigation class="w-4 h-4 text-blue-600 mr-2 flex-shrink-0" /> {{ r.origen }}
                </td>
                <td class="px-6 py-4 font-semibold text-slate-700">
                  <span class="inline-flex items-center text-xs bg-slate-100 px-2.5 py-1 rounded-lg text-slate-800 font-bold">
                    <Compass class="w-3.5 h-3.5 mr-1 text-slate-500" />
                    {{ r.departamento || 'JUNÍN' }}
                  </span>
                </td>
                <td class="px-6 py-4 font-bold text-slate-800 font-mono">
                  <span v-if="r.distancia_km" class="text-xs bg-blue-50 text-blue-800 px-2 py-0.5 rounded border border-blue-200">
                    {{ r.distancia_km }} km
                  </span>
                  <span v-else class="text-slate-400 text-xs font-normal">-</span>
                </td>
                <td class="px-6 py-4 text-slate-700">
                  <span v-if="r.duracion_estimada_minutos" class="inline-flex items-center text-xs font-bold bg-slate-100 px-2.5 py-1 rounded-lg text-slate-700">
                    <Clock class="w-3.5 h-3.5 mr-1 text-slate-400" />
                    {{ r.duracion_estimada_minutos }} m
                  </span>
                  <span v-else class="text-slate-400 text-xs font-normal">-</span>
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
                    @click="confirmToggleEstado(r)"
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
                <td colspan="6" class="px-6 py-8 text-center text-slate-400 text-sm">
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

              <form @submit.prevent="submitForm" class="flex-1 overflow-y-auto p-6 space-y-4">
                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Departamento / Región *</label>
                  <select v-model="form.departamento" required class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                    <option v-for="d in departamentosPeru" :key="d" :value="d">{{ d }}</option>
                  </select>
                </div>

                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Nombre del Punto / Localidad *</label>
                  <input 
                    v-model="form.origen" 
                    @input="e => handleUppercaseInput('origen', e)"
                    type="text" 
                    required 
                    class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none uppercase" 
                    placeholder="HUANCAYO, MINA LAS BAMBAS, CAMPAMENTO CARMEN" 
                  />
                </div>

                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Distancia (Km)</label>
                    <input v-model="form.distancia_km" type="number" min="0" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="240" />
                  </div>
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Tiempo (Minutos)</label>
                    <input v-model="form.duracion_estimada_minutos" type="number" min="1" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="120" />
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Observaciones / Notas <span class="text-slate-400 font-normal">(Opcional)</span></label>
                  <textarea v-model="form.observaciones" rows="3" class="w-full bg-white border border-slate-300 rounded-xl p-3 text-xs text-slate-900 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none shadow-inner" placeholder="Requisito de pase de ingreso o nota del punto..."></textarea>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                  <button type="button" @click="isDrawerOpen = false" class="cursor-pointer px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancelar</button>
                  <button type="submit" :disabled="form.processing" class="cursor-pointer px-5 py-2.5 text-sm bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-500 shadow-md">
                    {{ editingRuta ? 'Guardar Cambios' : 'Registrar Punto' }}
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
        :title="rutaToToggle && rutaToToggle.activa ? 'Inhabilitar Punto' : 'Reactivar Punto'"
        :message="rutaToToggle ? 'Desea ' + (rutaToToggle.activa ? 'desactivar' : 'reactivar') + ' el punto ' + rutaToToggle.origen + '?' : ''"
        :confirmText="rutaToToggle && rutaToToggle.activa ? 'Sí, Inhabilitar' : 'Sí, Reactivar'"
        :variant="rutaToToggle && rutaToToggle.activa ? 'danger' : 'success'"
        @close="showConfirmModal = false"
        @confirm="executeToggleEstado"
      />

    </div>
  </AppLayout>
</template>