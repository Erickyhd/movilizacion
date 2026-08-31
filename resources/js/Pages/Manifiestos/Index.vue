<script setup>
import { ref, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { useToast } from '@/Composables/useToast';
import { 
  Printer,
  FileText, 
  Plus, 
  Search, 
  Eye, 
  Trash2, 
  Bus, 
  Users, 
  MapPin, 
  Calendar, 
  CheckCircle2, 
  Clock, 
  X, 
  Upload, 
  FileSpreadsheet, 
  AlertTriangle,
  ArrowRight,
  UserPlus,
  UserX,
  Lock
} from 'lucide-vue-next';

const props = defineProps({
  manifiestos: Array,
  rutas: Array,
  vehiculos: Array,
  conductores: Array,
  trabajadores: Array,
  pasajeros_asignados_hoy: Array,
});

const page = usePage();
const canWrite = computed(() => {
  const perm = page.props.auth?.user?.permisos?.manifiestos;
  return perm === 'ESCRITURA' || page.props.auth?.user?.rol === 'ADMIN';
});

const activeTab = ref('manual'); // 'manual' | 'pdf'
const searchQuery = ref('');
const filterStatus = ref('all');

const isDrawerOpen = ref(false);
const isDetailModalOpen = ref(false);
const selectedManifiesto = ref(null);

// Add Passenger Sub-modal state
const showAddPassengerModal = ref(false);
const selectedWorkersToAdd = ref([]);

// Confirm Modal state

const getLocalDateTime = () => {
  const d = new Date();
  const pad = n => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

const showConfirmModal = ref(false);
const manifiestoToCancel = ref(null);

// PDF Upload state
const pdfFile = ref(null);
const isPdfParsing = ref(false);
const parsedPdfRows = ref([]);
const pdfParseError = ref('');

const activeManifiestosCount = computed(() => {
  return (props.manifiestos || []).filter(m => m.estado !== 'CANCELADO').length;
});


const conductoresPrincipales = computed(() => {
  return (props.conductores || []).filter(c => {
    const rol = c.rol_conductor || 'CONDUCTOR';
    return rol === 'CONDUCTOR' || rol === 'AMBOS';
  });
});

const copilotosDisponibles = computed(() => {
  return (props.conductores || []).filter(c => {
    const rol = c.rol_conductor || 'CONDUCTOR';
    return rol === 'COPILOTO' || rol === 'AMBOS';
  });
});

const onRutaSelect = () => {
  if (!form.ruta_id) return;
  const selected = (props.rutas || []).find(r => r.id === form.ruta_id);
  if (selected) {
    form.origen = selected.origen || '';
    form.destino = selected.destino || selected.origen || '';
  }
};



const apiPuntosList = ref([]);

const fetchPuntosFromApi = async () => {
  try {
    const res = await fetch(route('api.puntos'), {
      headers: { 'Accept': 'application/json' }
    });
    const data = await res.json();
    if (data.success) {
      apiPuntosList.value = data.puntos || [];
    }
  } catch (err) {
    console.error('Error fetching puntos API:', err);
  }
};

const puntosDisponibles = computed(() => {
  const points = new Set();
  (props.rutas || []).forEach(r => {
    if (r.origen) points.add(r.origen.trim().toUpperCase());
    if (r.destino) points.add(r.destino.trim().toUpperCase());
  });
  (apiPuntosList.value || []).forEach(p => points.add(p.trim().toUpperCase()));
  return Array.from(points).sort();
});


const canceladosCount = computed(() => {
  return (props.manifiestos || []).filter(m => m.estado === 'CANCELADO').length;
});

const filteredManifiestos = computed(() => {
  return (props.manifiestos || []).filter(m => {
    const search = searchQuery.value.toLowerCase();
    const cod = (m.codigo_manifiesto || '').toLowerCase();
    const veh = (m.vehiculo?.placa || '').toLowerCase();
    const cond = (m.conductor?.nombres || m.conductor?.trabajador?.nombres || '').toLowerCase();
    const orig = (m.ruta?.origen || '').toLowerCase();
    const dest = (m.ruta?.destino || '').toLowerCase();

    const matchesSearch = cod.includes(search) || veh.includes(search) || cond.includes(search) || orig.includes(search) || dest.includes(search);
    
    // 'all' shows active manifestos (REGISTRADO and CONFIRMADO), excluding CANCELADO
    const matchesStatus = filterStatus.value === 'all' 
      ? m.estado !== 'CANCELADO' 
      : m.estado === filterStatus.value;

    return matchesSearch && matchesStatus;
  });
});

const form = useForm({
  origen: '',
  destino: '',
  ruta_id: '',
  vehiculo_id: '',
  conductor_id: '',
  copiloto_id: '',
  tipo_movilizacion: 'INGRESO',
  fecha_salida_programada: getLocalDateTime(),
  pasajeros: [],
  pasajeros_excel: [],
});

const isWorkerAssignedToday = (trabajadorId) => {
  return (props.pasajeros_asignados_hoy || []).includes(trabajadorId);
};

const toggleWorkerSelection = (id) => {
  if (isWorkerAssignedToday(id)) return;
  const index = form.pasajeros.indexOf(id);
  if (index > -1) {
    form.pasajeros.splice(index, 1);
  } else {
    form.pasajeros.push(id);
  }
};

const selectAllAvailableWorkers = () => {
  const availableIds = (props.trabajadores || [])
    .filter(t => !isWorkerAssignedToday(t.id))
    .map(t => t.id);
  form.pasajeros = availableIds;
};

const openCreateDrawer = () => {
  fetchPuntosFromApi();
  form.reset();
  form.tipo_movilizacion = 'INGRESO';
  form.fecha_salida_programada = getLocalDateTime();
  if (puntosDisponibles.value && puntosDisponibles.value.length > 0) {
    form.origen = puntosDisponibles.value[0];
    form.destino = puntosDisponibles.value[1] || puntosDisponibles.value[0];
  } else if (props.rutas && props.rutas.length > 0) {
    form.origen = props.rutas[0].origen || '';
    form.destino = props.rutas[0].destino || props.rutas[0].origen || '';
  }
  if (props.vehiculos && props.vehiculos.length > 0) form.vehiculo_id = props.vehiculos[0].id;
  if (conductoresPrincipales.value && conductoresPrincipales.value.length > 0) {
    form.conductor_id = conductoresPrincipales.value[0].id;
  }
  
  parsedPdfRows.value = [];
  pdfFile.value = null;
  pdfParseError.value = '';
  isDrawerOpen.value = true;
};

const openDetailModal = (m) => {
  selectedManifiesto.value = m;
  isDetailModalOpen.value = true;
};


const toast = useToast();

const submitForm = () => {
  if (activeTab.value === 'manual' && form.pasajeros.length === 0) {
    toast.warning('Debe seleccionar al menos un trabajador o pasajero disponible para generar el manifiesto.');
    return;
  }

  if (activeTab.value === 'pdf' && parsedPdfRows.value.length === 0) {
    toast.warning('Debe procesar un archivo PDF o Excel válido con al menos un pasajero.');
    return;
  }

  if (activeTab.value === 'pdf') {
    form.pasajeros_excel = parsedPdfRows.value;
  }

  form.post(route('manifiestos.store'), {
    onSuccess: () => {
      isDrawerOpen.value = false;
      parsedPdfRows.value = [];
    }
  });
};

const confirmCancelManifiesto = (m) => {
  manifiestoToCancel.value = m;
  showConfirmModal.value = true;
};

const executeCancelManifiesto = () => {
  if (manifiestoToCancel.value) {
    router.delete(route('manifiestos.destroy', manifiestoToCancel.value.id), {
      onSuccess: () => {
        showConfirmModal.value = false;
        manifiestoToCancel.value = null;
        if (isDetailModalOpen.value) isDetailModalOpen.value = false;
      }
    });
  }
};

const updateEstado = (m, nuevoEstado) => {
  router.put(route('manifiestos.updateEstado', m.id), { estado: nuevoEstado }, {
    onSuccess: () => {
      if (selectedManifiesto.value && selectedManifiesto.value.id === m.id) {
        selectedManifiesto.value.estado = nuevoEstado;
      }
    }
  });
};

// Add / Remove passenger dynamically on existing manifesto
const openAddPassengerModal = () => {
  if (!selectedManifiesto.value || selectedManifiesto.value.estado !== 'REGISTRADO') return;
  selectedWorkersToAdd.value = [];
  showAddPassengerModal.value = true;
};

const toggleAddWorkerSelection = (id) => {
  if (isWorkerAssignedToday(id)) return;
  const idx = selectedWorkersToAdd.value.indexOf(id);
  if (idx > -1) {
    selectedWorkersToAdd.value.splice(idx, 1);
  } else {
    selectedWorkersToAdd.value.push(id);
  }
};

const submitAddPassengers = () => {
  if (!selectedManifiesto.value || selectedManifiesto.value.estado !== 'REGISTRADO' || selectedWorkersToAdd.value.length === 0) return;

  router.post(route('manifiestos.addPasajeros', selectedManifiesto.value.id), {
    trabajador_ids: selectedWorkersToAdd.value
  }, {
    onSuccess: () => {
      showAddPassengerModal.value = false;
      selectedWorkersToAdd.value = [];
      const updated = props.manifiestos.find(m => m.id === selectedManifiesto.value.id);
      if (updated) selectedManifiesto.value = updated;
    }
  });
};



const printPreimpresoSheet = (manifiestoId) => {
  window.open(route('manifiestos.pdfPreimpreso', manifiestoId), '_blank');
};

const printOfficialSheet = (manifiestoId) => {
  window.open(route('manifiestos.imprimirOficial', manifiestoId), '_blank');
};

const removePasajero = (detalleId) => {
  if (!selectedManifiesto.value || selectedManifiesto.value.estado !== 'REGISTRADO') return;

  router.delete(route('manifiestos.removePasajero', [selectedManifiesto.value.id, detalleId]), {
    onSuccess: () => {
      const updated = props.manifiestos.find(m => m.id === selectedManifiesto.value.id);
      if (updated) selectedManifiesto.value = updated;
    }
  });
};

// PDF File Handler
const handlePdfUpload = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  pdfFile.value = file;
  isPdfParsing.value = true;
  pdfParseError.value = '';

  const formData = new FormData();
  formData.append('pdf_file', file);

  try {
    const response = await fetch(route('manifiestos.parsePdf'), {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json'
      }
    });

    const data = await response.json();
    if (data.success) {
      parsedPdfRows.value = data.rows || [];
    } else {
      pdfParseError.value = data.error || 'Error al procesar el archivo PDF.';
    }
  } catch (err) {
    pdfParseError.value = 'Ocurrió un error inesperado al leer el PDF.';
  } finally {
    isPdfParsing.value = false;
  }
};

const exportToCsv = () => {
  if (!parsedPdfRows.value || parsedPdfRows.value.length === 0) return;

  const headers = ['DNI', 'Empresa', 'Apellido Paterno', 'Apellido Materno', 'Nombres', 'Embarque', 'Campamento', 'Area'];
  const rows = parsedPdfRows.value.map(r => [
    r.dni,
    `"${r.empresa || ''}"`,
    `"${r.apellido_paterno || ''}"`,
    `"${r.apellido_materno || ''}"`,
    `"${r.nombres || ''}"`,
    `"${r.embarque || ''}"`,
    `"${r.campamento || ''}"`,
    `"${r.area || ''}"`
  ]);

  const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement('a');
  link.setAttribute('href', encodedUri);
  link.setAttribute('download', `Extractor_Pasajeros_${new Date().toISOString().slice(0,10)}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};
</script>

<template>
  <AppLayout>
    <div class="w-full space-y-6">
      
      <!-- Top Banner & Main Actions -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
          <h2 class="text-xl font-extrabold text-slate-900 flex items-center">
            <FileText class="w-6 h-6 text-blue-600 mr-2.5" /> Manifiestos de Movilización de Personal
          </h2>
          <p class="text-sm text-slate-500 mt-1">Control de guías de despacho de transporte, asignación de pasajeros y validación de embarque.</p>
        </div>
        <button 
          v-if="canWrite"
          @click="openCreateDrawer"
          class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-md hover:shadow-blue-500/20 flex items-center space-x-2 transition cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>Generar Nuevo Manifiesto</span>
        </button>
      </div>

      <!-- Filters & Search Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center space-x-2 overflow-x-auto pb-1 sm:pb-0">
          <button 
            @click="filterStatus = 'all'" 
            :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer whitespace-nowrap', filterStatus === 'all' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50']"
          >
            Todos los Activos ({{ activeManifiestosCount }})
          </button>
          <button 
            @click="filterStatus = 'REGISTRADO'" 
            :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer whitespace-nowrap', filterStatus === 'REGISTRADO' ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50']"
          >
            Registrados (Abiertos)
          </button>
          <button 
            @click="filterStatus = 'CONFIRMADO'" 
            :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer whitespace-nowrap', filterStatus === 'CONFIRMADO' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50']"
          >
            Confirmados (Cerrados)
          </button>
          <button 
            @click="filterStatus = 'CANCELADO'" 
            :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer whitespace-nowrap', filterStatus === 'CANCELADO' ? 'bg-red-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50']"
          >
            Cancelados (Inactivos) ({{ canceladosCount }})
          </button>
        </div>

        <div class="relative w-full sm:w-72">
          <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Buscar por Código, Placa, Chofer..." 
            class="w-full bg-white border border-slate-300 rounded-xl pl-9 pr-4 py-2 text-xs text-slate-900 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none shadow-sm"
          />
        </div>
      </div>

      <!-- Manifiestos Table -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase border-b border-slate-100">
              <tr>
                <th class="px-6 py-3.5">Código Manifiesto</th>
                <th class="px-6 py-3.5">Ruta (Origen ➔ Destino)</th>
                <th class="px-6 py-3.5">Bus / Placa</th>
                <th class="px-6 py-3.5">Conductor</th>
                <th class="px-6 py-3.5">Pasajeros</th>
                <th class="px-6 py-3.5">Estado</th>
                <th class="px-6 py-3.5 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="m in filteredManifiestos" :key="m.id" :class="['hover:bg-slate-50/80 transition', m.estado === 'CANCELADO' ? 'bg-red-50/30 opacity-75' : '']">
                <td class="px-6 py-4 font-mono font-extrabold text-blue-700 text-base">
                  {{ m.codigo_manifiesto }}
                </td>
                <td class="px-6 py-4 font-extrabold text-slate-900">
                  <div class="flex items-center space-x-1.5 text-xs">
                    <span class="bg-slate-100 text-slate-800 px-2 py-0.5 rounded font-bold uppercase">{{ m.ruta?.origen }}</span>
                    <ArrowRight class="w-3.5 h-3.5 text-slate-400" />
                    <span class="bg-blue-50 text-blue-800 px-2 py-0.5 rounded font-bold uppercase">{{ m.ruta?.destino }}</span>
                  </div>
                </td>
                <td class="px-6 py-4 font-extrabold text-slate-800 font-mono">
                  <span class="inline-flex items-center text-xs bg-purple-50 text-purple-800 px-2.5 py-1 rounded-lg border border-purple-200">
                    <Bus class="w-3.5 h-3.5 mr-1 text-purple-600" />
                    {{ m.vehiculo?.placa }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <span class="block text-xs uppercase font-extrabold text-slate-900">{{ m.conductor?.nombres || m.conductor?.trabajador?.nombres }} {{ m.conductor?.apellido_paterno || m.conductor?.trabajador?.apellidos }}</span>
                  <span class="text-[11px] text-slate-400 font-mono">Lic: {{ m.conductor?.numero_licencia }}</span>
                </td>
                <td class="px-6 py-4 font-bold text-slate-900">
                  <span class="inline-flex items-center text-xs bg-slate-100 px-2.5 py-1 rounded-lg text-slate-800">
                    <Users class="w-3.5 h-3.5 mr-1 text-slate-500" />
                    {{ m.detalles ? m.detalles.length : 0 }} pax
                  </span>
                </td>
                <td class="px-6 py-4">
                  <span v-if="m.estado === 'CONFIRMADO'" class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">CONFIRMADO</span>
                  <span v-else-if="m.estado === 'REGISTRADO'" class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">REGISTRADO</span>
                  <span v-else-if="m.estado === 'CANCELADO'" class="px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">CANCELADO</span>
                  <span v-else class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-800 border border-slate-200">{{ m.estado }}</span>
                </td>
                <td class="px-6 py-4 text-right space-x-1 whitespace-nowrap">
                  <button 
                    @click="openDetailModal(m)"
                    title="Ver detalle del manifiesto"
                    class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50/80 rounded-lg transition cursor-pointer"
                  >
                    <Eye class="w-4 h-4" />
                  </button>
                  <button 
                    v-if="canWrite && m.estado !== 'CANCELADO'"
                    @click="confirmCancelManifiesto(m)"
                    title="Cancelar manifiesto"
                    class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50/80 rounded-lg transition cursor-pointer"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </td>
              </tr>
              <tr v-if="!filteredManifiestos || filteredManifiestos.length === 0">
                <td colspan="7" class="px-6 py-8 text-center text-slate-400 text-sm">
                  No se encontraron manifiestos registrados.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Teleported Drawer Form (New Manifesto) -->
      <Teleport to="body">
        <div v-if="isDrawerOpen" class="fixed inset-0 z-[9999] overflow-hidden">
          <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="isDrawerOpen = false"></div>

          <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
            <div class="w-screen max-w-2xl bg-white shadow-2xl flex flex-col transform transition duration-300 border-l border-slate-200">
              
              <div class="p-6 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white">
                    <FileText class="w-5 h-5" />
                  </div>
                  <div>
                    <h3 class="font-extrabold text-lg text-slate-100">Generar Manifiesto de Traslado</h3>
                    <span class="text-xs text-blue-300 block">Programación de viaje y asignación de personal</span>
                  </div>
                </div>
                <button @click="isDrawerOpen = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 cursor-pointer"><X class="w-5 h-5" /></button>
              </div>

              <!-- Main Tabs inside Drawer -->
              <div class="flex border-b border-slate-200 bg-slate-50 px-6 pt-3 space-x-4">
                <button 
                  @click="activeTab = 'manual'"
                  :class="['pb-2.5 text-xs font-extrabold transition border-b-2 cursor-pointer flex items-center space-x-2', activeTab === 'manual' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-900']"
                >
                  <Users class="w-4 h-4" />
                  <span>1. Selección Manual (Padrón)</span>
                </button>
                <button 
                  @click="activeTab = 'pdf'"
                  :class="['pb-2.5 text-xs font-extrabold transition border-b-2 cursor-pointer flex items-center space-x-2', activeTab === 'pdf' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-900']"
                >
                  <FileSpreadsheet class="w-4 h-4" />
                  <span>2. Carga Masiva (Excel / PDF)</span>
                </button>
              </div>

              <form @submit.prevent="submitForm" class="flex-1 overflow-y-auto p-6 space-y-4">
                
                <!-- General Logistics Header -->
                <div class="grid grid-cols-2 gap-3 bg-slate-50 p-4 rounded-xl border border-slate-200">
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Punto Origen *</label>
                    <select v-model="form.origen" required class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold uppercase focus:ring-2 focus:ring-blue-500 outline-none">
                      <option v-for="p in puntosDisponibles" :key="p" :value="p">{{ p }}</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Punto Destino *</label>
                    <select v-model="form.destino" required class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold uppercase focus:ring-2 focus:ring-blue-500 outline-none">
                      <option v-for="p in puntosDisponibles" :key="p" :value="p">{{ p }}</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Bus / Unidad *</label>
                    <select v-model="form.vehiculo_id" required class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                      <option v-for="v in vehiculos" :key="v.id" :value="v.id">{{ v.placa }} - {{ v.marca_modelo }} ({{ v.capacidad_pasajeros }} pax)</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Conductor Principal *</label>
                    <select v-model="form.conductor_id" required class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                      <option value="" disabled>Seleccione Conductor Principal</option>
                      <option v-for="c in conductoresPrincipales" :key="c.id" :value="c.id">
                        {{ c.nombres || c.trabajador?.nombres }} {{ c.apellido_paterno || c.trabajador?.apellidos }} (Lic: {{ c.numero_licencia }})
                      </option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Copiloto <span class="text-slate-400 font-normal">(Opcional)</span></label>
                    <select v-model="form.copiloto_id" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                      <option value="">Sin Copiloto</option>
                      <option v-for="c in copilotosDisponibles" :key="c.id" :value="c.id">
                        {{ c.nombres || c.trabajador?.nombres }} {{ c.apellido_paterno || c.trabajador?.apellidos }} (Lic: {{ c.numero_licencia }})
                      </option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Tipo Movilización</label>
                    <select v-model="form.tipo_movilizacion" required class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                      <option value="INGRESO">INGRESO DE PERSONAL</option>
                      <option value="SALIDA">SALIDA / RETORNO</option>
                      <option value="INTERNO">TRASLADO INTERNO</option>
                    </select>
                  </div>
                </div>

                <!-- Tab Manual: Padrón selection with daily duplicate blocking -->
                <div v-if="activeTab === 'manual'" class="space-y-3">
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold text-slate-700 uppercase tracking-wider">
                      Seleccionar Pasajeros Acreditados ({{ form.pasajeros.length }} Seleccionados)
                    </span>
                    <button type="button" @click="selectAllAvailableWorkers" class="text-xs font-bold text-blue-600 hover:text-blue-700 cursor-pointer">
                      Seleccionar Todos Disponibles
                    </button>
                  </div>

                  <div class="max-h-60 overflow-y-auto border border-slate-200 rounded-xl divide-y divide-slate-100">
                    <div 
                      v-for="t in trabajadores" 
                      :key="t.id"
                      @click="toggleWorkerSelection(t.id)"
                      :class="[
                        'p-3 flex items-center justify-between text-xs transition cursor-pointer',
                        isWorkerAssignedToday(t.id) ? 'bg-slate-100 opacity-60 cursor-not-allowed' :
                        form.pasajeros.includes(t.id) ? 'bg-blue-50/80 font-bold text-blue-900' : 'hover:bg-slate-50'
                      ]"
                    >
                      <div>
                        <span class="block font-extrabold uppercase text-slate-900">{{ t.nombres }} {{ t.apellidos }}</span>
                        <span class="text-slate-400 font-mono text-[11px]">DNI: {{ t.dni }} | Empresa: {{ t.empresa?.razon_social || 'CONTRATISTA' }}</span>
                      </div>
                      <span v-if="isWorkerAssignedToday(t.id)" class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                        ⚠ Registrado Hoy
                      </span>
                      <span v-else-if="form.pasajeros.includes(t.id)" class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-600 text-white">
                        Seleccionado
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Tab PDF: File Parser -->
                <div v-if="activeTab === 'pdf'" class="space-y-4">
                  <div class="p-4 border-2 border-dashed border-slate-300 rounded-xl text-center bg-slate-50/50">
                    <Upload class="w-8 h-8 text-blue-500 mx-auto mb-2" />
                    <span class="block text-xs font-bold text-slate-700">Subir Archivo PDF o Excel</span>
                    <span class="block text-[11px] text-slate-400 mb-3">El sistema extraerá automáticamente la lista masiva de pasajeros.</span>
                    <input type="file" accept=".pdf" @change="handlePdfUpload" class="text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer" />
                  </div>

                  <div v-if="isPdfParsing" class="text-center py-4 text-xs font-bold text-blue-600 animate-pulse flex items-center justify-center space-x-2">
                    <Clock class="w-4 h-4 animate-spin" />
                    <span>Procesando y extrayendo tabla de pasajeros...</span>
                  </div>

                  <div v-if="pdfParseError" class="p-3 bg-red-50 text-red-700 rounded-xl text-xs font-bold border border-red-200">
                    {{ pdfParseError }}
                  </div>

                  <div v-if="parsedPdfRows.length > 0" class="space-y-2">
                    <div class="flex items-center justify-between">
                      <span class="text-xs font-extrabold text-emerald-700">
                        ✓ {{ parsedPdfRows.length }} Pasajeros extraídos exitosamente
                      </span>
                      <button type="button" @click="exportToCsv" class="text-xs font-bold bg-emerald-100 text-emerald-800 px-3 py-1 rounded-lg hover:bg-emerald-200 transition cursor-pointer flex items-center space-x-1">
                        <FileSpreadsheet class="w-3.5 h-3.5 mr-1" />
                        <span>Exportar a Excel (CSV)</span>
                      </button>
                    </div>

                    <div class="max-h-48 overflow-y-auto border border-slate-200 rounded-xl text-[11px]">
                      <table class="w-full text-left divide-y divide-slate-100">
                        <thead class="bg-slate-100 font-bold text-slate-600">
                          <tr>
                            <th class="p-2">DNI</th>
                            <th class="p-2">Pasajero</th>
                            <th class="p-2">Empresa</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                          <tr v-for="(r, idx) in parsedPdfRows" :key="idx">
                            <td class="p-2 font-mono font-bold text-slate-800">{{ r.dni }}</td>
                            <td class="p-2 font-extrabold text-slate-900 uppercase">{{ r.nombres }} {{ r.apellido_paterno }}</td>
                            <td class="p-2 text-slate-600 uppercase">{{ r.empresa }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                  <button type="button" @click="isDrawerOpen = false" class="cursor-pointer px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancelar</button>
                  <button type="submit" :disabled="form.processing || (activeTab === 'manual' && form.pasajeros.length === 0) || (activeTab === 'pdf' && parsedPdfRows.length === 0)" class="cursor-pointer px-5 py-2.5 text-sm bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-500 shadow-md">
                    Guardar Manifiesto
                  </button>
                </div>

              </form>

            </div>
          </div>
        </div>
      </Teleport>

      <!-- Expanded Detail Modal (Ficha Técnica y Gestión Dinámica de Pasajeros) -->
      <Teleport to="body">
        <div v-if="isDetailModalOpen && selectedManifiesto" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" @click="isDetailModalOpen = false"></div>

          <div class="relative bg-white rounded-2xl max-w-5xl w-full p-6 shadow-2xl border border-slate-200 z-10 max-h-[90vh] flex flex-col">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
              <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center text-white shadow-md">
                  <FileText class="w-6 h-6" />
                </div>
                <div>
                  <h3 class="text-lg font-extrabold text-slate-900 flex items-center">
                    Manifiesto {{ selectedManifiesto.codigo_manifiesto }}
                    <span :class="[
                      'ml-3 px-2.5 py-0.5 rounded-full text-xs font-bold border',
                      selectedManifiesto.estado === 'CONFIRMADO' ? 'bg-emerald-100 text-emerald-800 border-emerald-200' :
                      selectedManifiesto.estado === 'REGISTRADO' ? 'bg-blue-100 text-blue-800 border-blue-200' : 'bg-red-100 text-red-800 border-red-200'
                    ]">{{ selectedManifiesto.estado }}</span>
                  </h3>
                  <span class="text-xs text-slate-400 font-medium">Programación: {{ selectedManifiesto.fecha_salida_programada }} | Tipo: {{ selectedManifiesto.tipo_movilizacion }}</span>
                </div>
              </div>

              <div class="flex items-center space-x-2">
                <button 
                  @click="printPreimpresoSheet(selectedManifiesto.id)"
                  class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold px-3.5 py-1.5 rounded-xl shadow-sm flex items-center space-x-1.5 transition cursor-pointer"
                  title="Imprimir Manifiesto Ajustado en 1 Hoja A4"
                >
                  <Printer class="w-4 h-4 text-white" />
                  <span>🖨 Imprimir PDF Manifiesto (1 Hoja A4)</span>
                </button>

                <!-- + Agregar Pasajeros button ONLY visible in REGISTRADO state -->
                <button 
                  v-if="canWrite && selectedManifiesto.estado === 'REGISTRADO'"
                  @click="openAddPassengerModal"
                  class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold px-3 py-1.5 rounded-xl shadow-sm flex items-center space-x-1.5 transition cursor-pointer"
                >
                  <UserPlus class="w-4 h-4" />
                  <span>+ Agregar Pasajeros</span>
                </button>

                <div v-else-if="selectedManifiesto.estado === 'CONFIRMADO'" class="flex items-center text-xs font-bold text-emerald-800 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-200">
                  <Lock class="w-3.5 h-3.5 mr-1.5 text-emerald-600" />
                  <span>Lista Cerrada (CONFIRMADO)</span>
                </div>

                <button @click="isDetailModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 cursor-pointer"><X class="w-5 h-5" /></button>
              </div>
            </div>

            <!-- Technical Sheet Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs">
              <div>
                <span class="block text-slate-400 font-bold uppercase text-[10px]">Unidad / Vehículo</span>
                <span class="font-extrabold text-purple-700 font-mono text-sm block">{{ selectedManifiesto.vehiculo?.placa }}</span>
                <span class="text-slate-600 font-semibold block">{{ selectedManifiesto.vehiculo?.marca_modelo }} ({{ selectedManifiesto.vehiculo?.capacidad_pasajeros }} pax)</span>
              </div>
              <div>
                <span class="block text-slate-400 font-bold uppercase text-[10px]">Conductor Principal</span>
                <span class="font-extrabold text-slate-900 uppercase block">{{ selectedManifiesto.conductor?.nombres || selectedManifiesto.conductor?.trabajador?.nombres }} {{ selectedManifiesto.conductor?.apellido_paterno || selectedManifiesto.conductor?.trabajador?.apellidos }}</span>
                <span class="text-slate-500 font-mono block">Licencia: {{ selectedManifiesto.conductor?.numero_licencia }}</span>
              </div>
              <div>
                <span class="block text-slate-400 font-bold uppercase text-[10px]">Ruta Oficial</span>
                <span class="font-extrabold text-blue-700 uppercase block">{{ selectedManifiesto.ruta?.origen }} ➔ {{ selectedManifiesto.ruta?.destino }}</span>
                <span class="text-slate-500 font-semibold block">Total Pasajeros: {{ selectedManifiesto.detalles ? selectedManifiesto.detalles.length : 0 }}</span>
              </div>
            </div>

            <!-- Passengers List Table with Edit/Delete Actions only in REGISTRADO state -->
            <div class="flex-1 overflow-y-auto border border-slate-200 rounded-xl">
              <table class="w-full text-left text-xs divide-y divide-slate-100">
                <thead class="bg-slate-100 font-bold text-slate-600 uppercase sticky top-0">
                  <tr>
                    <th class="p-3">Asiento</th>
                    <th class="p-3">DNI</th>
                    <th class="p-3">Pasajero / Nombres</th>
                    <th class="p-3">Empresa</th>
                    <th class="p-3">Área de Trabajo</th>
                    <th class="p-3">Embarque ➔ Destino</th>
                    <th v-if="canWrite && selectedManifiesto.estado === 'REGISTRADO'" class="p-3 text-right">Quitar</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="d in selectedManifiesto.detalles" :key="d.id" class="hover:bg-slate-50">
                    <td class="p-3 font-bold text-blue-600 font-mono">#{{ d.numero_asiento }}</td>
                    <td class="p-3 font-mono font-bold text-slate-900">{{ d.trabajador?.dni || '-' }}</td>
                    <td class="p-3 font-extrabold text-slate-900 uppercase">{{ d.trabajador?.nombres }} {{ d.trabajador?.apellidos }}</td>
                    <td class="p-3 font-semibold text-slate-700 uppercase">{{ d.trabajador?.empresa?.razon_social || '-' }}</td>
                    <td class="p-3 text-slate-600 uppercase">{{ d.area || d.trabajador?.area || '-' }}</td>
                    <td class="p-3 text-slate-500 font-medium">{{ d.embarque || selectedManifiesto.ruta?.origen }} ➔ {{ d.campamento || selectedManifiesto.ruta?.destino }}</td>
                    <td v-if="canWrite && selectedManifiesto.estado === 'REGISTRADO'" class="p-3 text-right">
                      <button 
                        @click="removePasajero(d.id)"
                        title="Quitar pasajero del manifiesto"
                        class="p-1 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition cursor-pointer"
                      >
                        <UserX class="w-4 h-4" />
                      </button>
                    </td>
                  </tr>
                  <tr v-if="!selectedManifiesto.detalles || selectedManifiesto.detalles.length === 0">
                    <td colspan="7" class="p-6 text-center text-slate-400 text-xs">
                      No hay pasajeros registrados en este manifiesto.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Footer Action Buttons & Simplified Statuses -->
            <div class="flex justify-between items-center pt-4 mt-4 border-t border-slate-100">
              <div class="flex space-x-2">
                <button 
                  v-if="selectedManifiesto.estado === 'REGISTRADO'" 
                  @click="updateEstado(selectedManifiesto, 'CONFIRMADO')" 
                  class="cursor-pointer px-3.5 py-2 text-xs font-bold bg-emerald-600 text-white rounded-xl hover:bg-emerald-500 shadow-sm transition"
                >
                  ✓ Confirmar Manifiesto
                </button>

                <button 
                  v-if="selectedManifiesto.estado === 'CONFIRMADO'" 
                  @click="updateEstado(selectedManifiesto, 'REGISTRADO')" 
                  class="cursor-pointer px-3.5 py-2 text-xs font-bold bg-slate-200 text-slate-800 rounded-xl hover:bg-slate-300 transition"
                >
                  Reabrir a REGISTRADO
                </button>
              </div>

              <button @click="isDetailModalOpen = false" class="cursor-pointer px-4 py-2 text-xs font-bold bg-slate-900 text-white rounded-xl hover:bg-slate-800">Cerrar</button>
            </div>

          </div>
        </div>
      </Teleport>

      <!-- Sub-Modal: Add Passengers to Existing Manifesto -->
      <Teleport to="body">
        <div v-if="showAddPassengerModal && selectedManifiesto" class="fixed inset-0 z-[10001] flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="showAddPassengerModal = false"></div>

          <div class="relative bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 z-10 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <h4 class="font-extrabold text-slate-900 text-base">Agregar Pasajeros a {{ selectedManifiesto.codigo_manifiesto }}</h4>
              <button @click="showAddPassengerModal = false" class="cursor-pointer text-slate-400 hover:text-slate-600 p-1"><X class="w-5 h-5" /></button>
            </div>

            <div class="max-h-60 overflow-y-auto border border-slate-200 rounded-xl divide-y divide-slate-100">
              <div 
                v-for="t in trabajadores" 
                :key="t.id"
                @click="toggleAddWorkerSelection(t.id)"
                :class="[
                  'p-3 flex items-center justify-between text-xs transition cursor-pointer',
                  isWorkerAssignedToday(t.id) ? 'bg-slate-100 opacity-60 cursor-not-allowed' :
                  selectedWorkersToAdd.includes(t.id) ? 'bg-blue-50/80 font-bold text-blue-900' : 'hover:bg-slate-50'
                ]"
              >
                <div>
                  <span class="block font-extrabold uppercase text-slate-900">{{ t.nombres }} {{ t.apellidos }}</span>
                  <span class="text-slate-400 font-mono text-[11px]">DNI: {{ t.dni }} | {{ t.empresa?.razon_social || 'CONTRATISTA' }}</span>
                </div>
                <span v-if="isWorkerAssignedToday(t.id)" class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                  ⚠ Registrado Hoy
                </span>
                <span v-else-if="selectedWorkersToAdd.includes(t.id)" class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-600 text-white">
                  Seleccionado
                </span>
              </div>
            </div>

            <div class="flex justify-end space-x-3 pt-2 border-t border-slate-100">
              <button type="button" @click="showAddPassengerModal = false" class="cursor-pointer px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancelar</button>
              <button type="button" @click="submitAddPassengers" :disabled="selectedWorkersToAdd.length === 0" class="cursor-pointer px-4 py-2 text-xs font-bold bg-blue-600 text-white rounded-xl hover:bg-blue-500 shadow-md disabled:opacity-50">
                Agregar {{ selectedWorkersToAdd.length }} Pasajeros
              </button>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- Reusable Confirmation Modal -->
      <ConfirmModal 
        :show="showConfirmModal"
        title="¿Cancelar Manifiesto?"
        :message="manifiestoToCancel ? '¿Estás seguro de cancelar el manifiesto de traslado ' + manifiestoToCancel.codigo_manifiesto + '?' : ''"
        confirmText="Sí, Cancelar Manifiesto"
        variant="danger"
        @close="showConfirmModal = false"
        @confirm="executeCancelManifiesto"
      />

    </div>
  </AppLayout>
</template>
