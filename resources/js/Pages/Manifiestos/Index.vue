<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
  FileText, 
  Plus, 
  Users, 
  ArrowRight, 
  CheckCircle2, 
  Clock, 
  Search, 
  Trash2, 
  X, 
  FileSpreadsheet, 
  Upload, 
  UserCheck, 
  ShieldCheck,
  Building2,
  AlertTriangle,
  Info,
  FileType,
  Download,
  FileCheck
} from 'lucide-vue-next';

const props = defineProps({
  manifiestos: Array,
  rutas: Array,
  vehiculos: Array,
  conductores: Array,
  trabajadores: Array,
  empresas: Array,
});

const page = usePage();
const canWrite = computed(() => {
  const perm = page.props.auth?.user?.permisos?.manifiestos;
  return perm === 'ESCRITURA' || page.props.auth?.user?.rol === 'ADMIN';
});

const searchQuery = ref('');
const showModal = ref(false);
const selectedManifiesto = ref(null);

const origenSeleccionado = ref('');
const destinoSeleccionado = ref('');
const activePassengerTab = ref('padron'); // 'padron' | 'excel' | 'pdf'

// Excel & PDF Raw Data
const rawExcelText = ref('');
const excelParsedRows = ref([]);
const excelError = ref('');
const isPdfParsing = ref(false);
const pdfMessage = ref('');
const pdfFileName = ref('');

// Helper for current ISO Datetime local string
const getCurrentLocalISO = () => {
  const now = new Date();
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
  return now.toISOString().slice(0, 16);
};

// List of all points available from catalog
const puntosDisponibles = computed(() => {
  const rawList = [];
  (props.rutas || []).forEach(r => {
    if (r.origen) rawList.push(r.origen);
    if (r.destino && r.destino !== r.origen) rawList.push(r.destino);
  });
  return [...new Set(rawList)];
});

const filteredManifiestos = computed(() => {
  return (props.manifiestos || []).filter(m => {
    const term = searchQuery.value.toLowerCase();
    const codigo = m.codigo_manifiesto.toLowerCase();
    const origen = (m.ruta?.origen || '').toLowerCase();
    const destino = (m.ruta?.destino || '').toLowerCase();
    const placa = (m.vehiculo?.placa || '').toLowerCase();
    const conductor = `${m.conductor?.trabajador?.nombres || ''} ${m.conductor?.trabajador?.apellidos || ''}`.toLowerCase();
    const tipo = (m.tipo_movilizacion || '').toLowerCase();

    return codigo.includes(term) || origen.includes(term) || destino.includes(term) || placa.includes(term) || conductor.includes(term) || tipo.includes(term);
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
  fecha_salida_programada: '',
  pasajeros: [],
  pasajeros_excel: [],
});

watch([origenSeleccionado, destinoSeleccionado], ([origen, destino]) => {
  form.origen = origen;
  form.destino = destino;
  if (origen && destino) {
    const rutaExistente = (props.rutas || []).find(r => r.origen === origen && r.destino === destino);
    if (rutaExistente) {
      form.ruta_id = rutaExistente.id;
    } else {
      form.ruta_id = '';
    }
  } else {
    form.ruta_id = '';
  }
});

const openModal = () => {
  form.reset();
  form.tipo_movilizacion = 'INGRESO';
  form.fecha_salida_programada = getCurrentLocalISO();
  
  if (props.vehiculos && props.vehiculos.length > 0) {
    form.vehiculo_id = props.vehiculos[0].id;
  }
  if (props.conductores && props.conductores.length > 0) {
    form.conductor_id = props.conductores[0].id;
  }

  origenSeleccionado.value = '';
  destinoSeleccionado.value = '';
  rawExcelText.value = '';
  excelParsedRows.value = [];
  excelError.value = '';
  pdfMessage.value = '';
  pdfFileName.value = '';
  activePassengerTab.value = 'padron';
  showModal.value = true;
};

// Parser for pasted Excel / CSV data with database mapping & audit validation check
const parseExcelInput = () => {
  excelError.value = '';
  excelParsedRows.value = [];
  if (!rawExcelText.value.trim()) return;

  const lines = rawExcelText.value.trim().split('\n');
  const rows = [];

  lines.forEach((line, index) => {
    const cols = line.split(line.includes('\t') ? '\t' : ',').map(c => c.trim());
    if (cols.length === 0 || !cols[0]) return;

    if (index === 0 && (cols[0].toUpperCase().includes('EMPRESA') || cols[0].toUpperCase().includes('DNI') || cols[0].toUpperCase().includes('FECHA'))) {
      return; // skip header line
    }

    let emp = '', fechaMov = '', tipoMov = '', dni = '', pat = '', mat = '', nom = '', emb = '', camp = '', ar = '';

    if (cols.length >= 7 && (isNaN(cols[0]) || cols[0].length > 15)) {
      emp = cols[0] || 'Contratista General';
      fechaMov = cols[1] || '';
      tipoMov = cols[2] || 'INGRESO';
      dni = cols[3] || '';
      pat = cols[4] || '';
      mat = cols[5] || '';
      nom = cols[6] || 'PASAJERO';
      emb = cols[7] || origenSeleccionado.value || 'HUANCAYO';
      camp = cols[8] || destinoSeleccionado.value || 'CARMEN';
      ar = cols[9] || 'OPERACIONES';
    } else {
      dni = cols[0] || '';
      pat = cols[1] || '';
      mat = cols[2] || '';
      nom = cols[3] || 'PASAJERO';
      emp = cols[4] || 'Contratista General';
      emb = cols[5] || origenSeleccionado.value || 'HUANCAYO';
      camp = cols[6] || destinoSeleccionado.value || 'CARMEN';
      ar = cols[7] || 'OPERACIONES';
    }

    if (!dni) return;

    if (!origenSeleccionado.value && emb) origenSeleccionado.value = emb;
    if (!destinoSeleccionado.value && camp) destinoSeleccionado.value = camp;

    const existingTrab = (props.trabajadores || []).find(t => t.dni === dni);
    const existingEmp = (props.empresas || []).find(e => e.razon_social.toLowerCase() === emp.toLowerCase());

    rows.push({
      dni: dni,
      apellido_paterno: pat,
      apellido_materno: mat,
      nombres: nom,
      empresa: emp,
      embarque: emb,
      campamento: camp,
      area: ar,
      is_registered_trab: Boolean(existingTrab),
      is_registered_emp: Boolean(existingEmp),
    });
  });

  excelParsedRows.value = rows;
  form.pasajeros_excel = rows;
};

const handleFileUpload = (e) => {
  const file = e.target.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = (evt) => {
    rawExcelText.value = evt.target.result;
    parseExcelInput();
  };
  reader.readAsText(file);
};

// Handle Direct PDF Upload & Extract Table
const handlePdfUpload = async (e) => {
  const file = e.target.files[0];
  if (!file) return;

  pdfFileName.value = file.name;
  isPdfParsing.value = true;
  pdfMessage.value = 'Procesando documento PDF y extrayendo tabla de pasajeros...';
  excelError.value = '';

  const formData = new FormData();
  formData.append('pdf_file', file);

  try {
    const res = await fetch(route('manifiestos.parsePdf'), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
      body: formData,
    });

    const data = await res.json();
    if (data.success && data.rows && data.rows.length > 0) {
      const rows = data.rows.map(r => {
        const existingTrab = (props.trabajadores || []).find(t => t.dni === r.dni);
        const existingEmp = (props.empresas || []).find(emp => emp.razon_social.toLowerCase() === (r.empresa || '').toLowerCase());
        return {
          ...r,
          is_registered_trab: Boolean(existingTrab),
          is_registered_emp: Boolean(existingEmp),
        };
      });

      excelParsedRows.value = rows;
      form.pasajeros_excel = rows;
      pdfMessage.value = `✓ Documento ${file.name} procesado exitosamente: ${rows.length} pasajeros identificados.`;
    } else {
      excelError.value = data.error || 'No se pudieron extraer registros con formato de DNI en este archivo PDF. Verifique que contenga una tabla de personal.';
    }
  } catch (err) {
    excelError.value = 'Error de conexión o servidor al procesar el archivo PDF.';
  } finally {
    isPdfParsing.value = false;
  }
};

// Export Parsed Table to CSV Download
const downloadAsCsv = () => {
  if (excelParsedRows.value.length === 0) return;
  let csv = 'Empresa,Fecha,Tipo,DNI,ApellidoPaterno,ApellidoMaterno,Nombres,Embarque,Campamento,Area\n';
  excelParsedRows.value.forEach(r => {
    csv += `"${r.empresa || ''}","${new Date().toLocaleDateString('es-PE')}","INGRESO","${r.dni || ''}","${r.apellido_paterno || ''}","${r.apellido_materno || ''}","${r.nombres || ''}","${r.embarque || ''}","${r.campamento || ''}","${r.area || ''}"\n`;
  });

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.setAttribute('href', url);
  link.setAttribute('download', `Pasajeros_Manifiesto_${Date.now()}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

const submit = () => {
  if (activePassengerTab.value === 'excel') {
    parseExcelInput();
  }
  form.post(route('manifiestos.store'), {
    onSuccess: () => {
      form.reset();
      origenSeleccionado.value = '';
      destinoSeleccionado.value = '';
      showModal.value = false;
    },
  });
};

const cambiarEstado = (m, nuevoEstado) => {
  router.put(route('manifiestos.estado', m.id), { estado: nuevoEstado });
};

const cancelarManifiesto = (m) => {
  if (confirm(`¿Confirmas que deseas cancelar el manifiesto ${m.codigo_manifiesto}?`)) {
    router.delete(route('manifiestos.destroy', m.id));
  }
};

const getStatusBadge = (estado) => {
  switch (estado) {
    case 'BORRADOR': return 'bg-slate-100 text-slate-700 border-slate-200';
    case 'CONFIRMADO': return 'bg-blue-100 text-blue-800 border-blue-200';
    case 'EN_GARITA': return 'bg-amber-100 text-amber-800 border-amber-200';
    case 'EN_RUTA': return 'bg-purple-100 text-purple-800 border-purple-200';
    case 'FINALIZADO': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
    case 'CANCELADO': return 'bg-red-100 text-red-800 border-red-200';
    default: return 'bg-slate-100 text-slate-700';
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
            <FileText class="w-6 h-6 text-teal-600 mr-2.5" /> Manifiestos de Traslado de Personal
          </h2>
          <p class="text-sm text-slate-500 mt-1">Generación de guías de despacho, extracción de PDF a Excel y auditoría masiva de pasajeros.</p>
        </div>
        <button 
          v-if="canWrite"
          @click="openModal"
          class="bg-teal-600 hover:bg-teal-500 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-md hover:shadow-teal-500/20 flex items-center space-x-2 transition cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>Generar Manifiesto</span>
        </button>
      </div>

      <!-- Search Bar -->
      <div class="flex justify-between items-center">
        <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">
          Total Manifiestos: {{ filteredManifiestos.length }}
        </div>
        <div class="relative w-full sm:w-72">
          <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Buscar Código, Ruta, Bus o Conductor..." 
            class="w-full bg-white border border-slate-300 rounded-xl pl-9 pr-4 py-2 text-xs text-slate-900 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-teal-500 outline-none shadow-sm"
          />
        </div>
      </div>

      <!-- Manifiestos Table -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase border-b border-slate-100">
              <tr>
                <th class="px-6 py-3.5">Código / Tipo</th>
                <th class="px-6 py-3.5">Tramo (Origen ➔ Destino)</th>
                <th class="px-6 py-3.5">Vehículo / Tripulantes</th>
                <th class="px-6 py-3.5">Pasajeros</th>
                <th class="px-6 py-3.5">Fecha Programada</th>
                <th class="px-6 py-3.5">Estado</th>
                <th class="px-6 py-3.5 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="m in filteredManifiestos" :key="m.id" :class="['hover:bg-slate-50/80 transition', m.estado === 'CANCELADO' ? 'bg-red-50/30 opacity-75' : '']">
                <td class="px-6 py-4">
                  <span class="font-mono font-extrabold text-teal-700 block text-base">{{ m.codigo_manifiesto }}</span>
                  <span :class="['text-[11px] font-bold px-2 py-0.5 rounded border inline-block mt-0.5', m.tipo_movilizacion === 'INGRESO' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200']">
                    {{ m.tipo_movilizacion || 'INGRESO' }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <span class="font-bold text-slate-800 block">{{ m.ruta?.origen }} ➔ {{ m.ruta?.destino }}</span>
                </td>
                <td class="px-6 py-4">
                  <span class="font-bold text-slate-900 block font-mono text-sm">{{ m.vehiculo?.placa }} ({{ m.vehiculo?.marca_modelo }})</span>
                  <span class="text-xs text-slate-600 block">Cond: {{ m.conductor?.trabajador?.nombres }} {{ m.conductor?.trabajador?.apellidos }}</span>
                  <span v-if="m.copiloto" class="text-xs text-indigo-600 font-semibold block">Copiloto: {{ m.copiloto?.trabajador?.nombres }} {{ m.copiloto?.trabajador?.apellidos }}</span>
                </td>
                <td class="px-6 py-4 font-extrabold text-blue-600">
                  {{ m.detalles?.length || 0 }} asignados
                </td>
                <td class="px-6 py-4 text-xs font-semibold text-slate-600">
                  {{ new Date(m.fecha_salida_programada).toLocaleString('es-PE') }}
                </td>
                <td class="px-6 py-4">
                  <span :class="['px-2.5 py-1 rounded-full text-xs font-bold border', getStatusBadge(m.estado)]">
                    {{ m.estado }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right space-x-1.5 whitespace-nowrap">
                  <button 
                    @click="selectedManifiesto = m"
                    class="px-3 py-1.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition cursor-pointer"
                  >
                    Ver Lista
                  </button>
                  <select 
                    v-if="canWrite"
                    :value="m.estado" 
                    @change="e => cambiarEstado(m, e.target.value)"
                    class="text-xs font-bold bg-white border border-slate-300 rounded-xl px-2.5 py-1.5 focus:ring-2 focus:ring-teal-500 outline-none cursor-pointer"
                  >
                    <option value="BORRADOR">BORRADOR</option>
                    <option value="CONFIRMADO">CONFIRMADO</option>
                    <option value="EN_GARITA">EN GARITA</option>
                    <option value="EN_RUTA">EN RUTA</option>
                    <option value="FINALIZADO">FINALIZADO</option>
                    <option value="CANCELADO">CANCELADO</option>
                  </select>
                  <button 
                    v-if="canWrite && m.estado !== 'CANCELADO'"
                    @click="cancelarManifiesto(m)"
                    title="Cancelar manifiesto"
                    class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50/80 rounded-lg transition cursor-pointer inline-flex items-center"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </td>
              </tr>
              <tr v-if="!filteredManifiestos || filteredManifiestos.length === 0">
                <td colspan="7" class="px-6 py-8 text-center text-slate-400 text-sm">
                  No se encontraron manifiestos en la búsqueda.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Teleported Modal Nuevo Manifiesto -->
      <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 z-[9999] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
          <div class="bg-white rounded-2xl max-w-3xl w-full p-6 shadow-2xl space-y-4 border border-slate-200 max-h-[94vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <div>
                <h3 class="font-extrabold text-slate-900 text-lg">Generar Manifiesto de Traslado</h3>
                <p class="text-xs text-slate-500">Conversión PDF/Excel, validación auditada e importación masiva de pasajeros</p>
              </div>
              <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer"><X class="w-5 h-5" /></button>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
              
              <!-- 2 Separate Selects for Origin and Destination from Puntos catalog -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Punto Origen (Embarque)</label>
                  <select v-model="origenSeleccionado" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-teal-500 outline-none bg-white">
                    <option value="" disabled>Seleccione Origen</option>
                    <option v-for="pto in puntosDisponibles" :key="pto" :value="pto">{{ pto }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Punto Destino (Campamento)</label>
                  <select v-model="destinoSeleccionado" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-teal-500 outline-none bg-white">
                    <option value="" disabled>Seleccione Destino</option>
                    <option v-for="pto in puntosDisponibles" :key="pto" :value="pto">{{ pto }}</option>
                  </select>
                </div>
              </div>

              <!-- Vehículo, Conductor Principal, Copiloto y Tipo -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Vehículo / Bus</label>
                  <select v-model="form.vehiculo_id" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-teal-500 outline-none bg-white">
                    <option value="" disabled>Seleccione Bus</option>
                    <option v-for="v in vehiculos" :key="v.id" :value="v.id">{{ v.placa }} - {{ v.marca_modelo }} (Cap: {{ v.capacidad_pasajeros }})</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Tipo de Movilización</label>
                  <select v-model="form.tipo_movilizacion" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-bold focus:ring-2 focus:ring-teal-500 outline-none bg-white">
                    <option value="INGRESO">INGRESO (Personal a Mina)</option>
                    <option value="SALIDA">SALIDA (Personal a Ciudad)</option>
                    <option value="INTERNO">INTERNO (Traslado Local)</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Conductor Principal</label>
                  <select v-model="form.conductor_id" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-teal-500 outline-none bg-white">
                    <option value="" disabled>Seleccione Conductor</option>
                    <option v-for="c in conductores" :key="c.id" :value="c.id">{{ c.trabajador?.nombres }} {{ c.trabajador?.apellidos }} ({{ c.categoria_licencia }})</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Copiloto de Ruta (Opcional)</label>
                  <select v-model="form.copiloto_id" class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-teal-500 outline-none bg-white">
                    <option value="">Sin Copiloto Asignado</option>
                    <option v-for="c in conductores" :key="c.id" :value="c.id">{{ c.trabajador?.nombres }} {{ c.trabajador?.apellidos }}</option>
                  </select>
                </div>
              </div>

              <div>
                <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">Fecha y Hora Programada de Salida</label>
                <input v-model="form.fecha_salida_programada" type="datetime-local" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-teal-500 outline-none" />
              </div>

              <!-- Carga de Pasajeros: Tab Switcher (Padron, Excel, PDF) -->
              <div class="border-t border-slate-100 pt-3 space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-extrabold text-slate-800 uppercase tracking-wider">Asignación de Pasajeros</span>
                  <div class="flex bg-slate-100 p-1 rounded-xl text-xs space-x-1 border border-slate-200">
                    <button 
                      type="button"
                      @click="activePassengerTab = 'padron'" 
                      :class="['px-3 py-1.5 font-bold rounded-lg transition cursor-pointer', activePassengerTab === 'padron' ? 'bg-white text-teal-700 shadow-sm' : 'text-slate-600 hover:text-slate-900']"
                    >
                      Padrón Sistema
                    </button>
                    <button 
                      type="button"
                      @click="activePassengerTab = 'excel'" 
                      :class="['px-3 py-1.5 font-bold rounded-lg transition cursor-pointer', activePassengerTab === 'excel' ? 'bg-teal-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900']"
                    >
                      📊 Cargar Excel / CSV
                    </button>
                    <button 
                      type="button"
                      @click="activePassengerTab = 'pdf'" 
                      :class="['px-3 py-1.5 font-bold rounded-lg transition cursor-pointer', activePassengerTab === 'pdf' ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900']"
                    >
                      📄 Extraer PDF
                    </button>
                  </div>
                </div>

                <!-- Tab 1: Padrón del Sistema -->
                <div v-if="activePassengerTab === 'padron'" class="max-h-44 overflow-y-auto border border-slate-200 rounded-xl p-2 space-y-1 bg-slate-50">
                  <label v-for="t in trabajadores" :key="t.id" class="flex items-center space-x-2 text-xs p-1.5 hover:bg-white rounded-lg cursor-pointer transition border border-transparent hover:border-slate-200">
                    <input type="checkbox" :value="t.id" v-model="form.pasajeros" class="rounded text-teal-600 border-slate-300 cursor-pointer" />
                    <span class="font-bold text-slate-800">{{ t.nombres }} {{ t.apellidos }}</span>
                    <span class="text-slate-500 text-[11px]">({{ t.empresa?.razon_social }})</span>
                    <span class="text-slate-400 font-mono ml-auto">DNI: {{ t.dni }}</span>
                  </label>
                </div>

                <!-- Tab 2: Cargar Excel / CSV -->
                <div v-if="activePassengerTab === 'excel'" class="space-y-3 bg-slate-50 p-4 rounded-xl border border-slate-200">
                  <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-slate-700">Pega los datos copiados de Excel o carga un archivo .CSV / .TXT</span>
                    <label class="bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 px-3 py-1.5 rounded-lg font-bold cursor-pointer inline-flex items-center space-x-1 shadow-xs">
                      <Upload class="w-3.5 h-3.5 text-teal-600" />
                      <span>Subir CSV</span>
                      <input type="file" accept=".csv,.txt,.tsv" @change="handleFileUpload" class="hidden" />
                    </label>
                  </div>

                  <textarea 
                    v-model="rawExcelText" 
                    @input="parseExcelInput"
                    rows="4" 
                    placeholder="Empresa | Fecha Mov. | Tipo | DNI | Ap. Paterno | Ap. Materno | Nombres | Embarque | Campamento | Área"
                    class="w-full text-xs font-mono bg-white border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:ring-2 focus:ring-teal-500 outline-none shadow-inner"
                  ></textarea>
                </div>

                <!-- Tab 3: Cargar Documento PDF / Extraer Tabla -->
                <div v-if="activePassengerTab === 'pdf'" class="space-y-3 bg-purple-50/60 p-4 rounded-xl border border-purple-200">
                  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                      <span class="font-extrabold text-slate-900 text-sm block flex items-center">
                        <FileType class="w-4 h-4 text-purple-600 mr-1.5" /> Extraer Tabla desde Documento PDF
                      </span>
                      <span class="text-xs text-slate-600 block mt-0.5">Selecciona el PDF proporcionado por la minera para convertir y auditar automáticamente a los pasajeros</span>
                    </div>
                    <label class="bg-purple-600 hover:bg-purple-500 text-white text-xs px-4 py-2.5 rounded-xl font-bold cursor-pointer inline-flex items-center space-x-2 shadow-md transition hover:shadow-purple-500/20 whitespace-nowrap">
                      <Upload class="w-4 h-4" />
                      <span>📁 Seleccionar y Extraer PDF</span>
                      <input type="file" accept=".pdf" @change="handlePdfUpload" class="hidden" />
                    </label>
                  </div>

                  <div v-if="isPdfParsing" class="text-xs text-purple-800 font-extrabold animate-pulse flex items-center space-x-2 bg-purple-100/80 p-2.5 rounded-xl border border-purple-200">
                    <Clock class="w-4 h-4 animate-spin text-purple-600" />
                    <span>{{ pdfMessage }}</span>
                  </div>

                  <div v-if="pdfMessage && !isPdfParsing" class="text-xs font-bold text-emerald-800 bg-emerald-50 p-2.5 rounded-xl border border-emerald-200 flex justify-between items-center">
                    <span>{{ pdfMessage }}</span>
                    <button type="button" @click="downloadAsCsv" class="text-xs bg-white text-purple-700 hover:bg-purple-50 px-2.5 py-1 rounded-lg border border-purple-300 font-extrabold inline-flex items-center space-x-1 cursor-pointer shadow-xs">
                      <Download class="w-3.5 h-3.5 text-purple-600" />
                      <span>Convertir a Excel (.CSV) 📥</span>
                    </button>
                  </div>

                  <div v-if="excelError" class="text-xs font-bold text-red-700 bg-red-50 p-2.5 rounded-xl border border-red-200 flex items-center space-x-2">
                    <AlertTriangle class="w-4 h-4 text-red-600 flex-shrink-0" />
                    <span>{{ excelError }}</span>
                  </div>
                </div>

                <!-- Shared Preview Table for Excel / PDF Parsed Rows -->
                <div v-if="(activePassengerTab === 'excel' || activePassengerTab === 'pdf') && excelParsedRows.length > 0" class="mt-3 space-y-2">
                  <div class="flex items-center justify-between bg-emerald-50 p-2.5 rounded-xl border border-emerald-200 text-xs">
                    <div class="flex items-center space-x-1.5 text-emerald-800 font-extrabold">
                      <CheckCircle2 class="w-4 h-4 text-emerald-600" />
                      <span>{{ excelParsedRows.length }} Pasajeros auditados y listos para generación de manifiesto</span>
                    </div>
                    <button type="button" @click="downloadAsCsv" title="Descargar como archivo CSV" class="text-xs bg-white text-purple-700 hover:bg-purple-50 px-2.5 py-1 rounded-lg border border-purple-300 font-bold inline-flex items-center cursor-pointer shadow-xs">
                      <Download class="w-3.5 h-3.5 mr-1 text-purple-600" /> Convertir a Excel
                    </button>
                  </div>

                  <div class="max-h-40 overflow-y-auto border border-slate-200 rounded-xl bg-white divide-y divide-slate-100 shadow-inner">
                    <div v-for="(row, idx) in excelParsedRows" :key="idx" class="p-2.5 text-[11px] flex items-center justify-between hover:bg-slate-50">
                      <div>
                        <span class="font-bold text-slate-900 block font-mono">
                          #{{ idx+1 }} | DNI: {{ row.dni }} — {{ row.apellido_paterno }} {{ row.apellido_materno }}, {{ row.nombres }}
                        </span>
                        <span class="text-slate-500 text-[10px] block mt-0.5">
                          Empresa: <span class="font-bold text-slate-700">{{ row.empresa }}</span> | Área: <span class="font-bold text-blue-700">{{ row.area }}</span> | Ruta: <span class="font-semibold text-slate-800">{{ row.embarque }} ➔ {{ row.campamento }}</span>
                        </span>
                      </div>
                      <div class="flex space-x-1 whitespace-nowrap">
                        <span v-if="row.is_registered_trab" class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 font-bold border border-blue-200 text-[10px]">
                          ✓ DNI Auditado
                        </span>
                        <span v-else class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 font-bold border border-emerald-200 text-[10px]">
                          + Nuevo Trabajador
                        </span>
                        <span v-if="row.is_registered_emp" class="px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 font-bold border border-purple-200 text-[10px]">
                          ✓ Empresa Mapeada
                        </span>
                        <span v-else class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 font-bold border border-amber-200 text-[10px]">
                          + Alta Empresa
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

              </div>

              <div class="flex justify-end space-x-3 pt-3 border-t border-slate-100">
                <button type="button" @click="showModal = false" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancelar</button>
                <button 
                  type="submit" 
                  :disabled="form.processing || !origenSeleccionado || !destinoSeleccionado" 
                  class="px-5 py-2 text-sm bg-teal-600 text-white font-bold rounded-xl hover:bg-teal-500 shadow-md disabled:opacity-50"
                >
                  Generar Manifiesto ({{ (activePassengerTab === 'excel' || activePassengerTab === 'pdf') ? excelParsedRows.length : form.pasajeros.length }} pax)
                </button>
              </div>
            </form>
          </div>
        </div>
      </Teleport>

      <!-- Teleported Modal Detalle Pasajeros -->
      <Teleport to="body">
        <div v-if="selectedManifiesto" class="fixed inset-0 z-[9999] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
          <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl space-y-4 border border-slate-200 max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <div>
                <h3 class="font-extrabold text-slate-900 text-lg">Manifiesto {{ selectedManifiesto.codigo_manifiesto }}</h3>
                <p class="text-xs text-slate-500">
                  {{ selectedManifiesto.ruta?.origen }} ➔ {{ selectedManifiesto.ruta?.destino }} | Bus: {{ selectedManifiesto.vehiculo?.placa }}
                </p>
              </div>
              <button @click="selectedManifiesto = null" class="text-slate-400 hover:text-slate-600 cursor-pointer"><X class="w-5 h-5" /></button>
            </div>

            <!-- Ficha Técnica Tripulación -->
            <div class="grid grid-cols-2 gap-3 bg-slate-50 p-3 rounded-xl border border-slate-200 text-xs">
              <div>
                <span class="text-slate-400 font-bold block">CONDUCTOR PRINCIPAL</span>
                <span class="font-bold text-slate-900">{{ selectedManifiesto.conductor?.trabajador?.nombres }} {{ selectedManifiesto.conductor?.trabajador?.apellidos }}</span>
                <span class="text-slate-500 block font-mono">Licencia: {{ selectedManifiesto.conductor?.numero_licencia }} ({{ selectedManifiesto.conductor?.categoria_licencia }})</span>
              </div>
              <div>
                <span class="text-slate-400 font-bold block">COPILOTO DE RUTA</span>
                <span v-if="selectedManifiesto.copiloto" class="font-bold text-indigo-900">{{ selectedManifiesto.copiloto?.trabajador?.nombres }} {{ selectedManifiesto.copiloto?.trabajador?.apellidos }}</span>
                <span v-else class="text-slate-400 font-medium">Sin copiloto registrado</span>
              </div>
            </div>

            <!-- Lista de Pasajeros -->
            <div class="space-y-2">
              <h4 class="text-xs font-extrabold text-slate-800 uppercase tracking-wider">Pasajeros Embarcados ({{ selectedManifiesto.detalles?.length || 0 }})</h4>
              <div class="max-h-64 overflow-y-auto divide-y divide-slate-100 border border-slate-200 rounded-xl bg-white">
                <div v-for="d in selectedManifiesto.detalles" :key="d.id" class="p-3 flex items-center justify-between hover:bg-slate-50">
                  <div>
                    <span class="font-bold text-slate-900 text-xs block">
                      #{{ d.numero_asiento }} - {{ d.trabajador?.nombres }} {{ d.trabajador?.apellido_paterno || '' }} {{ d.trabajador?.apellido_materno || '' }} {{ (!d.trabajador?.apellido_paterno && !d.trabajador?.apellido_materno) ? d.trabajador?.apellidos : '' }}
                    </span>
                    <span class="text-[11px] text-slate-500">
                      DNI: <span class="font-mono font-bold text-slate-700">{{ d.trabajador?.dni }}</span> | Empresa: <span class="font-bold text-slate-800">{{ d.trabajador?.empresa?.razon_social || 'Magori' }}</span>
                    </span>
                    <span v-if="d.area || d.trabajador?.area" class="text-[11px] text-blue-600 block">Área: {{ d.area || d.trabajador?.area }}</span>
                  </div>
                  <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 border border-slate-200">{{ d.estado_embarque }}</span>
                </div>
                <div v-if="!selectedManifiesto.detalles || selectedManifiesto.detalles.length === 0" class="py-6 text-center text-xs text-slate-400">
                  No hay pasajeros asignados a este manifiesto.
                </div>
              </div>
            </div>

          </div>
        </div>
      </Teleport>

    </div>
  </AppLayout>
</template>
