<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import { useThemeStore } from '@/Stores/themeStore';
import { 
  Bus, 
  Users, 
  ArrowRight, 
  CheckCircle2, 
  AlertTriangle, 
  X, 
  Zap, 
  RotateCcw, 
  Maximize2, 
  Keyboard, 
  ChevronDown, 
  ChevronUp, 
  Volume2, 
  VolumeX,
  Clock,
  Check,
  UserCheck,
  UserX,
  Trash2,
  UserPlus,
  ExternalLink,
  Save
} from 'lucide-vue-next';

const props = defineProps({
  manifiestos: {
    type: Array,
    default: () => []
  },
  trabajadores: {
    type: Array,
    default: () => []
  },
  pasajeros_asignados_hoy: {
    type: Array,
    default: () => []
  },
  empresas: {
    type: Array,
    default: () => []
  },
  canWrite: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['open-detail', 'open-create']);

const toast = useToast();
const themeStore = useThemeStore();

// Modo oscuro reactivo
const isDark = computed(() => themeStore.mode === 'dark');

// Estados principales
const isExpanded = ref(true);
const isKeypadModeActive = ref(true);
const isSoundActive = ref(true);
const showKioskModal = ref(false);

const selectedManifiestoId = ref(null);
const dniInput = ref('');
const isProcessing = ref(false);
const inputRef = ref(null);

// Historial y feedback
const lastCheckedInPassenger = ref(null);
const feedbackMessage = ref(null);

// Modal de Registro Rápido de Trabajador
const showRegisterModal = ref(false);
const isRegisteringWorker = ref(false);
const newWorkerForm = ref({
  dni: '',
  nombres: '',
  apellido_paterno: '',
  apellido_materno: '',
  empresa_id: '',
  area: 'OPERACIONES'
});

// Manifiestos activos listos para despacho (estado REGISTRADO)
const activeManifiestos = computed(() => {
  return (props.manifiestos || [])
    .filter(m => m.estado === 'REGISTRADO')
    .sort((a, b) => new Date(a.fecha_salida_programada || a.created_at) - new Date(b.fecha_salida_programada || b.created_at));
});

// Manifiesto seleccionado actualmente
const currentManifiesto = computed(() => {
  if (!selectedManifiestoId.value) return activeManifiestos.value[0] || null;
  return activeManifiestos.value.find(m => m.id === selectedManifiestoId.value) || activeManifiestos.value[0] || null;
});

// Auto-seleccionar primer manifiesto activo si no hay ninguno seleccionado
watch(activeManifiestos, (newVal) => {
  if (newVal.length > 0) {
    if (!selectedManifiestoId.value || !newVal.some(m => m.id === selectedManifiestoId.value)) {
      selectedManifiestoId.value = newVal[0].id;
    }
  } else {
    selectedManifiestoId.value = null;
  }
}, { immediate: true });

// Formato de 8 dígitos para casillas digitales
const displayDigits = computed(() => {
  const chars = (dniInput.value || '').slice(0, 8).split('');
  const slots = [];
  for (let i = 0; i < 8; i++) {
    slots.push(chars[i] || '');
  }
  return slots;
});

// PREVISUALIZACIÓN DE TRABAJADOR EN TIEMPO REAL (AL DIGITAR 8 DÍGITOS)
const previewWorker = computed(() => {
  const clean = (dniInput.value || '').trim();
  if (clean.length !== 8) return null;
  return (props.trabajadores || []).find(t => t.dni === clean) || null;
});

// ESTADO DE VALIDACIÓN DEL TRABAJADOR RESPECTO AL MANIFIESTO SELECCIONADO
const previewStatus = computed(() => {
  if (!previewWorker.value || !currentManifiesto.value) return null;
  
  const worker = previewWorker.value;
  const target = currentManifiesto.value;
  const currentCount = target.detalles?.length || 0;
  const maxCapacity = target.vehiculo?.capacidad_pasajeros || 46;

  // 1. Ya se encuentra a bordo en este bus
  const detailInBus = (target.detalles || []).find(d => d.trabajador_id === worker.id);
  if (detailInBus) {
    return {
      type: 'already_here',
      badge: 'YA A BORDO',
      message: `Ya se encuentra en este bus (Asiento #${detailInBus.numero_asiento})`,
      canSubmit: false,
      seat: detailInBus.numero_asiento
    };
  }

  // 2. Bus lleno (capacidad máxima)
  if (currentCount >= maxCapacity) {
    return {
      type: 'bus_full',
      badge: 'BUS LLENO (100%)',
      message: `Capacidad máxima alcanzada (${maxCapacity} asientos)`,
      canSubmit: false
    };
  }

  // 3. Asignado a otro bus hoy
  const isAssignedToday = (props.pasajeros_asignados_hoy || []).includes(worker.id);
  if (isAssignedToday) {
    return {
      type: 'assigned_today',
      badge: 'ASIGNADO HOY',
      message: 'Ya tiene otra movilización programada hoy',
      canSubmit: false
    };
  }

  // 4. Apto para confirmación y embarque
  const nextSeatNumber = currentCount + 1;
  return {
    type: 'ready',
    badge: 'APTO',
    message: `Asignar al Asiento #${nextSeatNumber}`,
    canSubmit: true,
    nextSeat: nextSeatNumber
  };
});

// HISTORIAL DINÁMICO DE PASAJEROS DEL MANIFIESTO SELECCIONADO (ÚLTIMOS 6)
const currentManifestPassengers = computed(() => {
  if (!currentManifiesto.value || !Array.isArray(currentManifiesto.value.detalles)) {
    return [];
  }
  return [...currentManifiesto.value.detalles]
    .sort((a, b) => {
      if (b.id && a.id) return b.id - a.id;
      return (b.numero_asiento || 0) - (a.numero_asiento || 0);
    })
    .slice(0, 6);
});

// Audio Synthesizer via Web Audio API
const playAudio = (type = 'success') => {
  if (!isSoundActive.value) return;
  try {
    const AudioCtx = window.AudioContext || window.webkitAudioContext;
    if (!AudioCtx) return;
    const ctx = new AudioCtx();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);

    if (type === 'success') {
      osc.type = 'sine';
      osc.frequency.setValueAtTime(587.33, ctx.currentTime);
      osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.1);
      gain.gain.setValueAtTime(0.18, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.005, ctx.currentTime + 0.35);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.35);
    } else if (type === 'error') {
      osc.type = 'sawtooth';
      osc.frequency.setValueAtTime(170, ctx.currentTime);
      gain.gain.setValueAtTime(0.2, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.005, ctx.currentTime + 0.3);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.3);
    } else if (type === 'keypress') {
      osc.type = 'triangle';
      osc.frequency.setValueAtTime(440, ctx.currentTime);
      gain.gain.setValueAtTime(0.04, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.05);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.05);
    } else if (type === 'delete') {
      osc.type = 'triangle';
      osc.frequency.setValueAtTime(320, ctx.currentTime);
      osc.frequency.exponentialRampToValueAtTime(200, ctx.currentTime + 0.15);
      gain.gain.setValueAtTime(0.15, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.005, ctx.currentTime + 0.2);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.2);
    }
  } catch (e) {
    // Audio context may be suspended
  }
};

const selectGate = (manifiestoId) => {
  selectedManifiestoId.value = manifiestoId;
  focusInput();
};

const focusInput = () => {
  if (inputRef.value) {
    inputRef.value.focus();
  }
};

const clearInput = () => {
  dniInput.value = '';
  feedbackMessage.value = null;
  focusInput();
};

const handleInputEvent = (e) => {
  const cleanVal = (e.target.value || '').replace(/\D/g, '').slice(0, 8);
  dniInput.value = cleanVal;
  if (e.target.value !== cleanVal) {
    e.target.value = cleanVal;
  }
  playAudio('keypress');
};

const formatHora = (dateStr) => {
  if (!dateStr) return '';
  try {
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return '';
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  } catch (e) {
    return '';
  }
};

const processCheckIn = (rawDni = null) => {
  const dni = (rawDni || dniInput.value || '').trim();
  if (!dni) return;

  if (!currentManifiesto.value) {
    playAudio('error');
    setFeedback('error', 'Sin Bus Activo', 'No hay ningún manifiesto en estado REGISTRADO seleccionado.');
    toast.error('Seleccione un manifiesto activo para embarcar.');
    return;
  }

  const target = currentManifiesto.value;
  const maxCapacity = target.vehiculo?.capacidad_pasajeros || 46;
  const currentCount = target.detalles?.length || 0;

  if (currentCount >= maxCapacity) {
    playAudio('error');
    setFeedback('error', 'Bus Lleno (100%)', `El vehículo ${target.vehiculo?.placa || ''} ha alcanzado su capacidad máxima (${maxCapacity} asientos).`);
    toast.error(`El bus ${target.vehiculo?.placa} ya completó su capacidad máxima.`);
    dniInput.value = '';
    return;
  }

  const worker = (props.trabajadores || []).find(t => t.dni === dni);
  if (!worker) {
    playAudio('error');
    setFeedback('error', 'DNI No Encontrado', `El DNI ${dni} no figura en el padrón o no se encuentra con estado APTO.`);
    toast.warning(`DNI ${dni} no registrado en el padrón de trabajadores aptos.`);
    // Abrir modal de registro o dejar listo
    return;
  }

  const alreadyInThis = (target.detalles || []).some(d => d.trabajador_id === worker.id);
  if (alreadyInThis) {
    playAudio('error');
    setFeedback('warning', 'Ya Registrado en Este Bus', `${worker.nombres} ${worker.apellidos || ''} ya se encuentra a bordo de este manifiesto (${target.codigo_manifiesto}).`);
    toast.warning(`${worker.nombres} ya está en este manifiesto.`);
    dniInput.value = '';
    return;
  }

  const alreadyAssignedToday = (props.pasajeros_asignados_hoy || []).includes(worker.id);
  if (alreadyAssignedToday) {
    playAudio('error');
    setFeedback('warning', 'Asignado a Otro Bus Hoy', `${worker.nombres} ${worker.apellidos || ''} ya fue programado en otro manifiesto el día de hoy.`);
    toast.warning(`Atención: ${worker.nombres} ya tiene movilización registrada hoy.`);
    dniInput.value = '';
    return;
  }

  isProcessing.value = true;
  playAudio('success');

  const nextSeatNumber = currentCount + 1;
  const workerFullName = `${worker.nombres} ${worker.apellidos || (worker.apellido_paterno + ' ' + (worker.apellido_materno || ''))}`.trim();

  lastCheckedInPassenger.value = {
    trabajador: worker,
    nombre_completo: workerFullName,
    manifiesto: target,
    asiento: nextSeatNumber,
    hora: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
    timestamp: Date.now()
  };

  router.post(route('manifiestos.addPasajeros', target.id), {
    trabajador_ids: [worker.id],
    trabajador_id: worker.id,
    numero_asiento: nextSeatNumber,
    embarque: target.ruta?.origen,
    campamento: target.ruta?.destino,
    observacion: 'Check-in rápido en mesa de embarque'
  }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      isProcessing.value = false;
      dniInput.value = '';
      setFeedback('success', '¡Embarque Exitoso!', `${workerFullName} asignado al Asiento N° ${nextSeatNumber} en ${target.ruta?.origen} → ${target.ruta?.destino}`);
      toast.success(`✓ Pasajero ${workerFullName} embarcado en Asiento N° ${nextSeatNumber}`);
      focusInput();
    },
    onError: (errs) => {
      isProcessing.value = false;
      playAudio('error');
      const msg = Object.values(errs)[0] || 'Error al registrar el pasajero en el bus.';
      setFeedback('error', 'Error en Embarque', msg);
      toast.error(msg);
      focusInput();
    }
  });
};

const setFeedback = (type, title, message) => {
  feedbackMessage.value = { type, title, message };
};

// Eliminar pasajero del manifiesto actualmente seleccionado
const removePasajeroFromCurrent = (detalle) => {
  if (!currentManifiesto.value) return;

  const target = currentManifiesto.value;
  const worker = detalle.trabajador;
  const workerName = worker 
    ? `${worker.nombres} ${worker.apellidos || (worker.apellido_paterno + ' ' + (worker.apellido_materno || ''))}`.trim()
    : 'este pasajero';

  if (!confirm(`¿Desea retirar a "${workerName}" (Asiento #${detalle.numero_asiento}) del bus ${target.vehiculo?.placa || ''} (${target.ruta?.destino})?`)) {
    return;
  }

  isProcessing.value = true;
  router.delete(route('manifiestos.removePasajero', [target.id, detalle.id]), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      isProcessing.value = false;
      playAudio('delete');
      if (lastCheckedInPassenger.value?.trabajador?.id === detalle.trabajador_id) {
        lastCheckedInPassenger.value = null;
      }
      toast.info(`Se retiró a ${workerName} del manifiesto.`);
      setFeedback('warning', 'Pasajero Retirado', `Se retiró a ${workerName} del bus ${target.vehiculo?.placa}.`);
      focusInput();
    },
    onError: (errs) => {
      isProcessing.value = false;
      playAudio('error');
      const msg = Object.values(errs)[0] || 'Error al retirar el pasajero del bus.';
      toast.error(msg);
      focusInput();
    }
  });
};

// Modal de registro rápido
const openQuickRegisterModal = () => {
  const defaultEmpresaId = props.empresas && props.empresas.length > 0 ? props.empresas[0].id : '';
  newWorkerForm.value = {
    dni: dniInput.value || '',
    nombres: '',
    apellido_paterno: '',
    apellido_materno: '',
    empresa_id: defaultEmpresaId,
    area: 'OPERACIONES'
  };
  showRegisterModal.value = true;
};

const submitQuickRegister = async () => {
  if (!newWorkerForm.value.dni || newWorkerForm.value.dni.length < 8) {
    toast.warning('Ingrese un número de DNI válido de 8 dígitos.');
    return;
  }
  if (!newWorkerForm.value.nombres || !newWorkerForm.value.apellido_paterno) {
    toast.warning('Ingrese los nombres y apellido paterno del trabajador.');
    return;
  }

  isRegisteringWorker.value = true;
  try {
    const selectedEmp = (props.empresas || []).find(e => e.id == newWorkerForm.value.empresa_id);
    const empNombre = selectedEmp ? selectedEmp.razon_social : 'CONTRATISTA GENERAL';

    const payload = {
      trabajadores: [
        {
          dni: newWorkerForm.value.dni.trim(),
          nombres: newWorkerForm.value.nombres.trim(),
          apellido_paterno: newWorkerForm.value.apellido_paterno.trim(),
          apellido_materno: (newWorkerForm.value.apellido_materno || '').trim(),
          empresa_id: newWorkerForm.value.empresa_id || null,
          empresa_nombre: empNombre,
          area: (newWorkerForm.value.area || 'OPERACIONES').trim()
        }
      ]
    };

    const response = await fetch(route('manifiestos.autoRegisterTrabajadores'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    const data = await response.json();
    if (data.success && (data.trabajadores || data.created_workers)) {
      const createdList = data.trabajadores || data.created_workers;
      const created = createdList[0];
      if (created) {
        if (!props.trabajadores.some(t => t.id === created.id)) {
          props.trabajadores.push(created);
        }
        dniInput.value = created.dni;
        showRegisterModal.value = false;
        playAudio('success');
        toast.success(`✓ Trabajador ${created.nombres} ${created.apellidos || ''} registrado como APTO.`);
        focusInput();
      }
    } else {
      playAudio('error');
      toast.error(data.error || 'No se pudo registrar el trabajador.');
    }
  } catch (err) {
    playAudio('error');
    toast.error('Error de red al registrar el trabajador.');
  } finally {
    isRegisteringWorker.value = false;
  }
};

// Listener global para teclado numérico físico (Numpad) o lectores de código de barras
const handleGlobalKeyDown = (e) => {
  if (!isKeypadModeActive.value) return;
  if (showRegisterModal.value) return; // Si el modal de registro está abierto, no capturar

  const activeTag = document.activeElement?.tagName;
  const isOurInput = document.activeElement === inputRef.value;

  // Atajo [Alt + 1..9] para alternar entre puertas/buses activos
  if (e.altKey && !isNaN(e.key) && Number(e.key) >= 1 && Number(e.key) <= 9) {
    e.preventDefault();
    const index = Number(e.key) - 1;
    if (activeManifiestos.value[index]) {
      selectGate(activeManifiestos.value[index].id);
      toast.info(`Cambio a Puerta [${index + 1}]: ${activeManifiestos.value[index].ruta?.origen} → ${activeManifiestos.value[index].ruta?.destino}`);
    }
    return;
  }

  // Si el foco está en el input nativo de DNI
  if (isOurInput) {
    if (e.key === 'Escape') {
      clearInput();
    }
    return;
  }

  // Si el foco está en otro campo de texto, ignorar
  if (activeTag === 'INPUT' || activeTag === 'TEXTAREA' || activeTag === 'SELECT') {
    return;
  }

  // Teclas numéricas desde teclado físico externo
  if (/^[0-9]$/.test(e.key)) {
    e.preventDefault();
    if (dniInput.value.length < 8) {
      dniInput.value += e.key;
      playAudio('keypress');
    }
    focusInput();
  } else if (e.key === 'Enter') {
    e.preventDefault();
    processCheckIn();
  } else if (e.key === 'Backspace') {
    e.preventDefault();
    dniInput.value = dniInput.value.slice(0, -1);
    focusInput();
  } else if (e.key === 'Escape') {
    clearInput();
  }
};

onMounted(() => {
  window.addEventListener('keydown', handleGlobalKeyDown);
  setTimeout(focusInput, 300);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleGlobalKeyDown);
});
</script>

<template>
  <div :class="[
    'w-full rounded-2xl overflow-hidden border shadow-lg transition-colors duration-200',
    isDark ? 'bg-slate-900/90 border-slate-800' : 'bg-white border-slate-200'
  ]">
    
    <!-- Barra Superior -->
    <div :class="[
      'p-4 border-b flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 transition-colors duration-200',
      isDark 
        ? 'bg-gradient-to-r from-slate-900 via-blue-950/40 to-slate-900 border-slate-800 text-white' 
        : 'bg-gradient-to-r from-blue-50/90 via-slate-50 to-white border-slate-200 text-slate-900'
    ]">
      <div class="flex items-center space-x-3">
        <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-md shadow-blue-500/20 shrink-0">
          <Zap class="w-5 h-5 animate-pulse" />
        </div>
        <div>
          <div class="flex items-center space-x-2">
            <h3 :class="['text-base font-extrabold tracking-tight flex items-center', isDark ? 'text-white' : 'text-slate-900']">
              Mesa de Embarque Rápido
              <span :class="[
                'ml-2 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest border',
                isDark 
                  ? 'bg-blue-900/60 text-blue-300 border-blue-700/50' 
                  : 'bg-blue-100 text-blue-800 border-blue-200'
              ]">
                Check-in Express
              </span>
            </h3>
          </div>
          <p :class="['text-xs font-medium', isDark ? 'text-slate-400' : 'text-slate-600']">
            Soporte para teclado numérico externo · Digite 8 dígitos y presione Enter para confirmar embarque
          </p>
        </div>
      </div>

      <!-- Controles de barra superior -->
      <div class="flex items-center flex-wrap gap-2">
        <!-- Sonido -->
        <button
          type="button"
          @click="isSoundActive = !isSoundActive"
          :class="[
            'p-2 rounded-xl border transition cursor-pointer',
            isSoundActive 
              ? (isDark ? 'bg-blue-900/40 text-blue-400 border-blue-800' : 'bg-blue-50 text-blue-600 border-blue-200') 
              : (isDark ? 'bg-slate-800 text-slate-500 border-slate-700' : 'bg-white text-slate-400 border-slate-200')
          ]"
          :title="isSoundActive ? 'Silenciar efectos' : 'Activar efectos de sonido'"
        >
          <Volume2 v-if="isSoundActive" class="w-4 h-4" />
          <VolumeX v-else class="w-4 h-4" />
        </button>

        <!-- Modo Kiosco -->
        <button
          type="button"
          @click="showKioskModal = true"
          :class="[
            'px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 border cursor-pointer',
            isDark 
              ? 'bg-slate-800 hover:bg-slate-700 text-slate-300 border-slate-700' 
              : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-300 shadow-xs'
          ]"
          title="Abrir pantalla para el pasajero"
        >
          <Maximize2 class="w-3.5 h-3.5" />
          <span>Modo Kiosco</span>
        </button>

        <!-- Colapsar / Expandir -->
        <button
          type="button"
          @click="isExpanded = !isExpanded"
          :class="[
            'p-2 rounded-xl border transition cursor-pointer',
            isDark 
              ? 'bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white border-slate-700' 
              : 'bg-white hover:bg-slate-50 text-slate-500 hover:text-slate-800 border-slate-200 shadow-xs'
          ]"
        >
          <ChevronUp v-if="isExpanded" class="w-4 h-4" />
          <ChevronDown v-else class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Cuerpo Principal -->
    <div v-show="isExpanded" class="p-4 sm:p-5 space-y-4">

      <!-- SECCIÓN 1: PUERTAS DE EMBARQUE ACTIVAS -->
      <div>
        <div class="flex items-center justify-between mb-2">
          <span :class="['text-xs font-extrabold uppercase tracking-wider flex items-center', isDark ? 'text-white' : 'text-slate-900']">
            <Bus class="w-4 h-4 mr-1.5 text-blue-600 dark:text-cyan-400" />
            Puertas de Embarque Activas (Buses en Despacho)
          </span>
          <span :class="['text-[11px] font-bold', isDark ? 'text-slate-400' : 'text-slate-500']">
            {{ activeManifiestos.length }} manifiesto(s) · Use [Alt + 1..9] para alternar
          </span>
        </div>

        <!-- Tarjetas de Puertas -->
        <div v-if="activeManifiestos.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
          <div
            v-for="(m, idx) in activeManifiestos"
            :key="m.id"
            @click="selectGate(m.id)"
            :class="[
              'p-3 rounded-xl border-2 transition-all duration-200 cursor-pointer text-left relative overflow-hidden',
              selectedManifiestoId === m.id
                ? (isDark 
                    ? 'bg-gradient-to-br from-blue-950/80 via-slate-900 to-slate-900 border-blue-500 shadow-md ring-2 ring-blue-500/20' 
                    : 'bg-gradient-to-br from-blue-50/90 via-white to-blue-50/40 border-blue-600 shadow-md ring-2 ring-blue-100')
                : (isDark 
                    ? 'bg-slate-900/60 border-slate-800 hover:border-slate-700 text-slate-300' 
                    : 'bg-slate-50/70 border-slate-200 hover:border-slate-300 text-slate-700')
            ]"
          >
            <!-- Cabecera de Tarjeta -->
            <div class="flex items-center justify-between mb-1">
              <div class="flex items-center space-x-2">
                <span 
                  :class="[
                    'px-2 py-0.5 rounded text-[10px] font-black font-mono',
                    selectedManifiestoId === m.id 
                      ? 'bg-blue-600 text-white' 
                      : (isDark ? 'bg-slate-800 text-slate-300' : 'bg-slate-200 text-slate-700')
                  ]"
                >
                  PUERTA {{ idx + 1 }} · Alt+{{ idx + 1 }}
                </span>
                <span :class="['text-[10px] font-bold font-mono', isDark ? 'text-slate-400' : 'text-slate-500']">
                  {{ m.codigo_manifiesto }}
                </span>
              </div>
              
              <span 
                v-if="selectedManifiestoId === m.id"
                :class="[
                  'flex items-center space-x-1 px-1.5 py-0.5 rounded text-[10px] font-extrabold border',
                  isDark 
                    ? 'bg-blue-900/60 text-blue-200 border-blue-700' 
                    : 'bg-blue-100 text-blue-800 border-blue-300'
                ]"
              >
                <Check class="w-3 h-3 text-blue-600 dark:text-blue-400" />
                <span>ACTIVO</span>
              </span>
            </div>

            <!-- Ruta -->
            <div class="my-1">
              <div class="flex items-center space-x-1.5 text-xs font-black">
                <span :class="['uppercase truncate', isDark ? 'text-white' : 'text-slate-900']">{{ m.ruta?.origen }}</span>
                <ArrowRight class="w-3.5 h-3.5 text-blue-500 shrink-0" />
                <span :class="['uppercase truncate font-black', isDark ? 'text-cyan-400' : 'text-blue-600']">{{ m.ruta?.destino }}</span>
              </div>
              <div :class="['text-[11px] mt-0.5 flex items-center justify-between font-medium', isDark ? 'text-slate-400' : 'text-slate-600']">
                <span>Bus: <strong :class="['font-mono', isDark ? 'text-slate-200' : 'text-slate-900']">{{ m.vehiculo?.placa }}</strong></span>
                <span class="truncate ml-2">Chofer: {{ m.conductor?.nombres || m.conductor?.trabajador?.nombres || 'Asignado' }}</span>
              </div>
            </div>

            <!-- Barra de Ocupación -->
            <div :class="['mt-2 pt-1.5 border-t', isDark ? 'border-slate-800' : 'border-slate-100']">
              <div class="flex items-center justify-between text-[11px] font-bold mb-1">
                <span :class="isDark ? 'text-slate-400' : 'text-slate-500'">Ocupación:</span>
                <span :class="[
                  (m.detalles?.length || 0) >= (m.vehiculo?.capacidad_pasajeros || 46) 
                    ? 'text-red-500 font-black' 
                    : (isDark ? 'text-white' : 'text-slate-900')
                ]">
                  {{ m.detalles?.length || 0 }} / {{ m.vehiculo?.capacidad_pasajeros || 46 }}
                </span>
              </div>
              <div :class="['w-full h-1.5 rounded-full overflow-hidden', isDark ? 'bg-slate-800' : 'bg-slate-200']">
                <div 
                  class="h-full rounded-full transition-all duration-500"
                  :style="{ width: `${Math.min(100, Math.round(((m.detalles?.length || 0) / (m.vehiculo?.capacidad_pasajeros || 46)) * 100))}%` }"
                  :class="[
                    (m.detalles?.length || 0) >= (m.vehiculo?.capacidad_pasajeros || 46) 
                      ? 'bg-red-500' 
                      : (m.detalles?.length || 0) >= (m.vehiculo?.capacidad_pasajeros || 46) * 0.8 
                        ? 'bg-amber-500' 
                        : 'bg-emerald-500'
                  ]"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Estado vacío -->
        <div v-else :class="[
          'p-5 rounded-2xl border border-dashed text-center space-y-2',
          isDark ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-slate-300'
        ]">
          <Bus class="w-8 h-8 mx-auto text-slate-400" />
          <h4 :class="['text-sm font-bold', isDark ? 'text-white' : 'text-slate-800']">No hay manifiestos en estado REGISTRADO</h4>
          <p class="text-xs text-slate-500">Cree un manifiesto para iniciar el despacho.</p>
          <button 
            type="button"
            @click="emit('open-create')"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold shadow transition cursor-pointer"
          >
            + Crear Manifiesto Ahora
          </button>
        </div>
      </div>

      <!-- SECCIÓN 2: TERMINAL DE DNI Y LISTA DE PASAJEROS -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        
        <!-- Izquierda: Terminal de Captura de DNI (7 cols) -->
        <div :class="[
          'lg:col-span-7 rounded-2xl p-4 sm:p-5 border shadow-sm flex flex-col justify-between space-y-3 transition-colors duration-200',
          isDark ? 'bg-slate-900/90 border-slate-800 text-white' : 'bg-white border-slate-200 text-slate-900'
        ]">
          
          <div>
            <div class="flex items-center justify-between mb-2">
              <span :class="['text-xs font-extrabold uppercase tracking-wider flex items-center', isDark ? 'text-white' : 'text-slate-900']">
                <Keyboard class="w-4 h-4 mr-1.5 text-blue-600 dark:text-cyan-400" />
                Terminal de Captura de DNI
              </span>
              <span :class="['text-[11px] font-medium', isDark ? 'text-slate-400' : 'text-slate-500']">
                Destino activo: <strong :class="['uppercase font-bold', isDark ? 'text-cyan-400' : 'text-blue-600']">{{ currentManifiesto?.ruta?.destino || 'Ninguno' }}</strong>
              </span>
            </div>

            <!-- Casillas LED de 8 Dígitos -->
            <div :class="[
              'rounded-xl p-3 sm:p-4 border shadow-inner flex flex-col items-center justify-center space-y-2.5 transition-colors duration-200',
              isDark ? 'bg-slate-950 border-slate-800' : 'bg-slate-100/90 border-slate-300/80'
            ]">
              <span :class="['text-[10px] uppercase font-mono font-bold tracking-widest', isDark ? 'text-slate-400' : 'text-slate-600']">
                NÚMERO DE DNI (8 DÍGITOS)
              </span>

              <!-- 8 Casillas -->
              <div class="flex items-center justify-center gap-1.5 sm:gap-2 w-full">
                <div 
                  v-for="(digit, i) in displayDigits" 
                  :key="i"
                  :class="[
                    'w-9 h-11 sm:w-10 sm:h-12 rounded-lg flex items-center justify-center text-lg sm:text-xl font-mono font-black border-2 transition-all duration-150',
                    digit 
                      ? (isDark 
                          ? 'bg-cyan-950/90 border-cyan-400 text-cyan-300 shadow-md shadow-cyan-500/20' 
                          : 'bg-white border-blue-600 text-blue-700 shadow-xs')
                      : (isDark 
                          ? 'bg-slate-900 border-slate-800 text-slate-600' 
                          : 'bg-white/80 border-slate-300 text-slate-400')
                  ]"
                >
                  {{ digit || '·' }}
                </div>
              </div>

              <!-- Input para teclado físico o lector de código de barras -->
              <div class="w-full max-w-md pt-1 flex items-center gap-2">
                <input
                  ref="inputRef"
                  :value="dniInput"
                  @input="handleInputEvent"
                  @keydown.enter.prevent="processCheckIn()"
                  type="text"
                  inputmode="numeric"
                  maxlength="8"
                  placeholder="Escriba o escanee DNI..."
                  :class="[
                    'flex-1 font-mono text-center text-sm font-bold tracking-widest px-3 py-2 rounded-xl border transition focus:outline-none',
                    isDark 
                      ? 'bg-slate-900 text-white border-slate-700 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 placeholder:text-slate-500' 
                      : 'bg-white text-slate-900 border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 placeholder:text-slate-400'
                  ]"
                />
                <button
                  type="button"
                  @click="processCheckIn()"
                  :disabled="dniInput.length === 0 || isProcessing"
                  class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-extrabold text-xs rounded-xl shadow-xs transition disabled:opacity-50 cursor-pointer flex items-center space-x-1 shrink-0 active:scale-95"
                >
                  <CheckCircle2 class="w-4 h-4" />
                  <span>EMBARCAR (Enter)</span>
                </button>
                <button
                  type="button"
                  @click="clearInput()"
                  :class="[
                    'p-2 rounded-xl border transition cursor-pointer',
                    isDark 
                      ? 'bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white border-slate-700' 
                      : 'bg-white hover:bg-slate-100 text-slate-500 hover:text-slate-800 border-slate-300 shadow-xs'
                  ]"
                  title="Borrar entrada"
                >
                  <RotateCcw class="w-4 h-4" />
                </button>
              </div>
            </div>

            <!-- =================================================================== -->
            <!-- PREVISUALIZACIÓN MINIMALISTA Y COMPACTA DEL TRABAJADOR -->
            <!-- =================================================================== -->
            <div v-if="dniInput.length === 8" class="w-full mt-2.5">
              
              <!-- CASO A: TRABAJADOR ENCONTRADO EN PADRÓN -->
              <div 
                v-if="previewWorker"
                :class="[
                  'p-3 rounded-xl border flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 transition-all text-xs',
                  previewStatus?.canSubmit
                    ? (isDark ? 'bg-slate-900/90 border-emerald-500/70 text-white' : 'bg-emerald-50/90 border-emerald-400 text-slate-900')
                    : (isDark ? 'bg-slate-900/90 border-amber-500/70 text-white' : 'bg-amber-50/90 border-amber-400 text-slate-900')
                ]"
              >
                <!-- Identidad del Trabajador -->
                <div class="flex items-center space-x-2.5 min-w-0">
                  <div :class="[
                    'w-8 h-8 rounded-lg flex items-center justify-center shrink-0 font-bold',
                    previewStatus?.canSubmit
                      ? (isDark ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-emerald-100 text-emerald-700')
                      : (isDark ? 'bg-amber-950 text-amber-400 border border-amber-800' : 'bg-amber-100 text-amber-700')
                  ]">
                    <UserCheck v-if="previewStatus?.canSubmit" class="w-4 h-4 text-emerald-500" />
                    <AlertTriangle v-else class="w-4 h-4 text-amber-500" />
                  </div>

                  <div class="min-w-0">
                    <div class="flex items-center space-x-2">
                      <span class="font-black text-sm uppercase truncate">
                        {{ previewWorker.nombres }} {{ previewWorker.apellidos || (previewWorker.apellido_paterno + ' ' + (previewWorker.apellido_materno || '')) }}
                      </span>
                      <span 
                        v-if="previewStatus?.canSubmit" 
                        class="px-1.5 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300"
                      >
                        Asiento #{{ previewStatus.nextSeat }}
                      </span>
                    </div>
                    <div class="text-[11px] opacity-80 flex items-center gap-2 truncate mt-0.5">
                      <span>Empresa: <strong>{{ previewWorker.empresa?.razon_social || previewWorker.empresa_nombre || 'Contratista' }}</strong></span>
                      <span v-if="previewWorker.area">· Área: {{ previewWorker.area }}</span>
                    </div>
                  </div>
                </div>

                <!-- Estado o Acción -->
                <div class="flex items-center space-x-2 shrink-0 self-end sm:self-auto">
                  <span v-if="previewStatus?.canSubmit" class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 flex items-center">
                    Presione <kbd class="mx-1 px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-800 font-mono text-[10px]">Enter</kbd> para embarcar
                  </span>
                  <span v-else class="text-[11px] font-bold text-amber-600 dark:text-amber-400">
                    {{ previewStatus?.message }}
                  </span>
                </div>
              </div>

              <!-- CASO B: DNI NO ENCONTRADO EN PADRÓN (BOTÓN REGISTRAR) -->
              <div 
                v-else
                :class="[
                  'p-3 rounded-xl border flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 transition-all text-xs',
                  isDark ? 'bg-slate-900/90 border-amber-600/70 text-white' : 'bg-amber-50 border-amber-300 text-slate-900'
                ]"
              >
                <div class="flex items-center space-x-2.5 min-w-0">
                  <div :class="['w-8 h-8 rounded-lg flex items-center justify-center shrink-0', isDark ? 'bg-amber-950 text-amber-400 border border-amber-800' : 'bg-amber-100 text-amber-700']">
                    <UserX class="w-4 h-4" />
                  </div>
                  <div class="min-w-0">
                    <div class="font-bold text-xs truncate">
                      DNI <strong class="font-mono text-amber-600 dark:text-amber-400">{{ dniInput }}</strong> no registrado en el sistema
                    </div>
                    <div class="text-[11px] opacity-75">
                      El trabajador no figura en el padrón de trabajadores aptos.
                    </div>
                  </div>
                </div>

                <div class="flex items-center space-x-2 shrink-0">
                  <button
                    type="button"
                    @click="openQuickRegisterModal()"
                    class="px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-xs font-bold shadow-xs transition cursor-pointer flex items-center space-x-1"
                  >
                    <UserPlus class="w-3.5 h-3.5" />
                    <span>Registrar Trabajador</span>
                  </button>
                  <button
                    type="button"
                    @click="clearInput()"
                    :class="[
                      'px-2.5 py-1.5 rounded-lg border text-xs font-semibold transition cursor-pointer',
                      isDark ? 'border-slate-700 hover:bg-slate-800 text-slate-400' : 'border-slate-300 hover:bg-slate-100 text-slate-600'
                    ]"
                  >
                    Limpiar
                  </button>
                </div>
              </div>

            </div>

            <!-- Indicador sutil mientras digita -->
            <div v-else-if="dniInput.length > 0" class="w-full text-center py-1">
              <span :class="['text-xs font-mono font-medium', isDark ? 'text-slate-400' : 'text-slate-500']">
                DNI: <strong>{{ dniInput.length }}/8 dígitos</strong>...
              </span>
            </div>

          </div>

          <!-- Banner de Aviso / Notificación -->
          <div v-if="feedbackMessage" :class="[
            'p-2.5 rounded-xl border flex items-start space-x-2.5 text-xs',
            feedbackMessage.type === 'success' 
              ? (isDark ? 'bg-emerald-950/50 text-emerald-200 border-emerald-800' : 'bg-emerald-50 text-emerald-900 border-emerald-300')
              : feedbackMessage.type === 'warning' 
                ? (isDark ? 'bg-amber-950/50 text-amber-200 border-amber-800' : 'bg-amber-50 text-amber-900 border-amber-300')
                : (isDark ? 'bg-red-950/50 text-red-200 border-red-800' : 'bg-red-50 text-red-900 border-red-300')
          ]">
            <CheckCircle2 v-if="feedbackMessage.type === 'success'" class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" />
            <AlertTriangle v-else class="w-4 h-4 shrink-0 mt-0.5" :class="feedbackMessage.type === 'warning' ? 'text-amber-500' : 'text-red-500'" />
            <div class="flex-1">
              <strong class="font-bold">{{ feedbackMessage.title }}</strong>: {{ feedbackMessage.message }}
            </div>
            <button @click="feedbackMessage = null" :class="isDark ? 'text-slate-400 hover:text-slate-200' : 'text-slate-400 hover:text-slate-600'">
              <X class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>

        <!-- Derecha: Historial de Pasajeros del Bus Seleccionado (5 cols) -->
        <div :class="[
          'lg:col-span-5 rounded-2xl p-4 sm:p-5 border shadow-sm flex flex-col justify-between space-y-3 transition-colors duration-200',
          isDark ? 'bg-slate-900/90 border-slate-800 text-white' : 'bg-white border-slate-200 text-slate-900'
        ]">
          <div>
            <!-- Cabecera sincronizada con el bus activo -->
            <div :class="['flex items-center justify-between mb-2.5 border-b pb-2', isDark ? 'border-slate-800' : 'border-slate-200']">
              <div>
                <span :class="['text-xs font-extrabold uppercase tracking-wider flex items-center', isDark ? 'text-white' : 'text-slate-900']">
                  <Clock class="w-4 h-4 mr-1.5 text-blue-600 dark:text-cyan-400" />
                  Últimos Pasajeros a Bordo
                </span>
                <p :class="['text-[11px] font-medium mt-0.5', isDark ? 'text-slate-400' : 'text-slate-500']">
                  Destino: <strong :class="isDark ? 'text-cyan-300' : 'text-blue-600'">{{ currentManifiesto?.ruta?.destino || 'Bus Activo' }}</strong>
                </p>
              </div>
              <div class="text-right">
                <span :class="[
                  'px-2 py-0.5 rounded text-xs font-black font-mono border',
                  isDark ? 'bg-slate-800 text-cyan-300 border-slate-700' : 'bg-blue-50 text-blue-800 border-blue-200'
                ]">
                  {{ currentManifiesto?.detalles?.length || 0 }} / {{ currentManifiesto?.vehiculo?.capacidad_pasajeros || 46 }}
                </span>
              </div>
            </div>

            <!-- Lista de pasajeros del manifiesto seleccionado -->
            <div v-if="currentManifestPassengers.length > 0" class="space-y-2 max-h-[280px] overflow-y-auto pr-1">
              <div
                v-for="item in currentManifestPassengers"
                :key="item.id"
                :class="[
                  'p-2.5 rounded-xl border flex items-center justify-between text-xs transition shadow-xs group',
                  isDark 
                    ? 'bg-slate-800/60 border-slate-700/60 hover:bg-slate-800 text-white' 
                    : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-900'
                ]"
              >
                <div class="flex items-center space-x-2 min-w-0">
                  <div :class="[
                    'w-7 h-7 rounded-lg flex items-center justify-center font-black font-mono text-xs shrink-0',
                    isDark ? 'bg-blue-900/60 text-blue-200' : 'bg-blue-100 text-blue-800'
                  ]">
                    #{{ item.numero_asiento }}
                  </div>
                  <div class="min-w-0">
                    <span :class="['font-bold block truncate uppercase text-[11px]', isDark ? 'text-white' : 'text-slate-900']">
                      {{ item.trabajador?.nombres }} {{ item.trabajador?.apellidos || (item.trabajador?.apellido_paterno + ' ' + (item.trabajador?.apellido_materno || '')) }}
                    </span>
                    <span :class="['text-[10px] font-mono block truncate', isDark ? 'text-slate-400' : 'text-slate-500']">
                      DNI: {{ item.trabajador?.dni }} · {{ item.trabajador?.empresa?.razon_social || item.trabajador?.empresa_nombre || 'Contratista' }}
                    </span>
                  </div>
                </div>

                <div class="flex items-center space-x-1.5 shrink-0 ml-2">
                  <span :class="['text-[10px] font-mono', isDark ? 'text-slate-400' : 'text-slate-500']">
                    {{ formatHora(item.created_at) }}
                  </span>
                  <!-- Botón para retirar / eliminar pasajero -->
                  <button
                    type="button"
                    @click="removePasajeroFromCurrent(item)"
                    :disabled="isProcessing"
                    class="p-1 text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40 rounded transition cursor-pointer"
                    title="Retirar del bus"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
            </div>

            <!-- Estado vacío cuando no hay pasajeros aún en este bus -->
            <div v-else :class="['p-6 text-center space-y-1', isDark ? 'text-slate-400' : 'text-slate-500']">
              <Users class="w-7 h-7 mx-auto opacity-40 text-blue-500" />
              <p class="text-xs font-bold text-slate-700 dark:text-slate-300">Sin pasajeros a bordo aún</p>
              <p class="text-[11px]">
                Bus: <strong>{{ currentManifiesto?.vehiculo?.placa || '' }}</strong> ({{ currentManifiesto?.ruta?.destino }})
              </p>
            </div>
          </div>

          <!-- Footer de resumen -->
          <div :class="['pt-2 border-t text-[11px] flex items-center justify-between', isDark ? 'border-slate-800 text-slate-400' : 'border-slate-200 text-slate-500']">
            <span>Bus activo: <strong :class="['font-mono', isDark ? 'text-white' : 'text-slate-900']">{{ currentManifiesto?.vehiculo?.placa || 'Ninguno' }}</strong></span>
            <span>A bordo: <strong :class="['font-bold', isDark ? 'text-cyan-400' : 'text-blue-600']">{{ currentManifiesto?.detalles?.length || 0 }} pasajeros</strong></span>
          </div>
        </div>

      </div>

    </div>

    <!-- MODAL DE REGISTRO RÁPIDO DE TRABAJADOR -->
    <Teleport to="body">
      <div v-if="showRegisterModal" class="fixed inset-0 z-[10002] flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity" @click="showRegisterModal = false"></div>

        <div :class="[
          'relative w-full max-w-lg rounded-2xl p-6 shadow-2xl border transition-all z-10',
          isDark ? 'bg-slate-900 border-slate-700 text-white' : 'bg-white border-slate-200 text-slate-900'
        ]">
          <!-- Encabezado Modal -->
          <div class="flex items-center justify-between pb-3 border-b" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
            <div class="flex items-center space-x-2.5">
              <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-xs">
                <UserPlus class="w-5 h-5" />
              </div>
              <div>
                <h3 class="text-sm font-black uppercase tracking-tight">Registro Rápido de Trabajador</h3>
                <p :class="['text-xs font-medium', isDark ? 'text-slate-400' : 'text-slate-500']">
                  Habilite al trabajador en el padrón para proceder con su embarque
                </p>
              </div>
            </div>
            <button @click="showRegisterModal = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer">
              <X class="w-5 h-5" />
            </button>
          </div>

          <!-- Formulario -->
          <form @submit.prevent="submitQuickRegister" class="mt-4 space-y-3.5">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-[11px] font-bold uppercase mb-1" :class="isDark ? 'text-slate-300' : 'text-slate-700'">
                  DNI (8 Dígitos) *
                </label>
                <input
                  v-model="newWorkerForm.dni"
                  type="text"
                  maxlength="8"
                  required
                  class="w-full px-3 py-2 rounded-xl text-xs font-mono font-bold border focus:outline-none"
                  :class="isDark ? 'bg-slate-800 border-slate-700 text-white focus:border-cyan-500' : 'bg-slate-50 border-slate-300 focus:border-blue-500'"
                />
              </div>

              <div>
                <label class="block text-[11px] font-bold uppercase mb-1" :class="isDark ? 'text-slate-300' : 'text-slate-700'">
                  Nombres *
                </label>
                <input
                  v-model="newWorkerForm.nombres"
                  type="text"
                  required
                  placeholder="Ej: RÓMULO"
                  class="w-full px-3 py-2 rounded-xl text-xs font-bold uppercase border focus:outline-none"
                  :class="isDark ? 'bg-slate-800 border-slate-700 text-white focus:border-cyan-500' : 'bg-slate-50 border-slate-300 focus:border-blue-500'"
                />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-[11px] font-bold uppercase mb-1" :class="isDark ? 'text-slate-300' : 'text-slate-700'">
                  Apellido Paterno *
                </label>
                <input
                  v-model="newWorkerForm.apellido_paterno"
                  type="text"
                  required
                  placeholder="Ej: CARHUANCHO"
                  class="w-full px-3 py-2 rounded-xl text-xs font-bold uppercase border focus:outline-none"
                  :class="isDark ? 'bg-slate-800 border-slate-700 text-white focus:border-cyan-500' : 'bg-slate-50 border-slate-300 focus:border-blue-500'"
                />
              </div>

              <div>
                <label class="block text-[11px] font-bold uppercase mb-1" :class="isDark ? 'text-slate-300' : 'text-slate-700'">
                  Apellido Materno
                </label>
                <input
                  v-model="newWorkerForm.apellido_materno"
                  type="text"
                  placeholder="Ej: PÉREZ"
                  class="w-full px-3 py-2 rounded-xl text-xs font-bold uppercase border focus:outline-none"
                  :class="isDark ? 'bg-slate-800 border-slate-700 text-white focus:border-cyan-500' : 'bg-slate-50 border-slate-300 focus:border-blue-500'"
                />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-[11px] font-bold uppercase mb-1" :class="isDark ? 'text-slate-300' : 'text-slate-700'">
                  Empresa / Contratista *
                </label>
                <select
                  v-model="newWorkerForm.empresa_id"
                  required
                  class="w-full px-3 py-2 rounded-xl text-xs font-bold border focus:outline-none"
                  :class="isDark ? 'bg-slate-800 border-slate-700 text-white focus:border-cyan-500' : 'bg-slate-50 border-slate-300 focus:border-blue-500'"
                >
                  <option v-for="emp in (props.empresas || [])" :key="emp.id" :value="emp.id">
                    {{ emp.razon_social }}
                  </option>
                  <option v-if="!props.empresas || props.empresas.length === 0" value="">
                    CONTRATISTA GENERAL
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-[11px] font-bold uppercase mb-1" :class="isDark ? 'text-slate-300' : 'text-slate-700'">
                  Área de Trabajo
                </label>
                <input
                  v-model="newWorkerForm.area"
                  type="text"
                  list="areas-sugeridas"
                  placeholder="OPERACIONES"
                  class="w-full px-3 py-2 rounded-xl text-xs font-bold uppercase border focus:outline-none"
                  :class="isDark ? 'bg-slate-800 border-slate-700 text-white focus:border-cyan-500' : 'bg-slate-50 border-slate-300 focus:border-blue-500'"
                />
                <datalist id="areas-sugeridas">
                  <option value="OPERACIONES"></option>
                  <option value="MINA"></option>
                  <option value="PLANTA"></option>
                  <option value="MANTENIMIENTO"></option>
                  <option value="SEGURIDAD"></option>
                  <option value="ADMINISTRACIÓN"></option>
                </datalist>
              </div>
            </div>

            <!-- Footer / Botones -->
            <div class="pt-3 border-t flex items-center justify-between gap-2" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
              <a 
                href="/trabajadores" 
                target="_blank"
                class="text-xs text-blue-600 dark:text-cyan-400 font-semibold hover:underline flex items-center space-x-1"
              >
                <span>Ir al Módulo de Trabajadores</span>
                <ExternalLink class="w-3 h-3" />
              </a>

              <div class="flex items-center space-x-2">
                <button
                  type="button"
                  @click="showRegisterModal = false"
                  class="px-3 py-2 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  :disabled="isRegisteringWorker"
                  class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-extrabold shadow-md transition disabled:opacity-50 cursor-pointer flex items-center space-x-1.5"
                >
                  <Save class="w-3.5 h-3.5" />
                  <span>{{ isRegisteringWorker ? 'Registrando...' : 'Guardar y Habilitar' }}</span>
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- SECCIÓN 3: PANTALLA MODO KIOSCO -->
    <Teleport to="body">
      <div v-if="showKioskModal" class="fixed inset-0 z-[99999] bg-slate-950 flex flex-col justify-between p-6 sm:p-12 text-white">
        
        <div class="flex items-center justify-between border-b border-slate-800 pb-6">
          <div class="flex items-center space-x-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-cyan-500 flex items-center justify-center text-white shadow-xl shadow-cyan-500/30">
              <Bus class="w-8 h-8" />
            </div>
            <div>
              <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                MESA DE EMBARQUE · TERMINAL DE TRANSPORTE
              </h1>
              <p class="text-slate-400 text-sm">
                Control de Embarque y Asignación Directa de Pasajeros
              </p>
            </div>
          </div>

          <button
            type="button"
            @click="showKioskModal = false"
            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs flex items-center space-x-2 transition cursor-pointer"
          >
            <X class="w-4 h-4" />
            <span>Cerrar Pantalla</span>
          </button>
        </div>

        <div class="max-w-4xl mx-auto w-full py-8 text-center space-y-6">
          <div>
            <span class="text-xs font-mono font-bold uppercase tracking-widest text-cyan-400 mb-2 block">
              BUS DE DESTINO ACTIVO
            </span>
            <h2 class="text-3xl sm:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-cyan-300 to-emerald-400 uppercase">
              {{ currentManifiesto?.ruta?.origen }} → {{ currentManifiesto?.ruta?.destino }}
            </h2>
            <div class="mt-2 text-slate-400 text-lg font-mono">
              PLACA DE BUS: <span class="text-white font-bold">{{ currentManifiesto?.vehiculo?.placa || 'PENDIENTE' }}</span>
            </div>
          </div>

          <!-- Display Gigante -->
          <div class="p-8 sm:p-12 rounded-3xl bg-slate-900/90 border-2 border-slate-800 shadow-2xl flex flex-col items-center justify-center space-y-6">
            <span class="text-sm font-mono uppercase tracking-widest text-slate-400">
              DIGITE SU NÚMERO DE DNI EN EL TECLADO NUMÉRICO
            </span>

            <div class="flex items-center justify-center gap-2 sm:gap-3">
              <div
                v-for="(digit, i) in displayDigits"
                :key="i"
                :class="[
                  'w-12 h-16 sm:w-16 sm:h-22 rounded-2xl flex items-center justify-center text-3xl sm:text-5xl font-mono font-black border-2 transition-all duration-150',
                  digit 
                    ? 'bg-cyan-950 border-cyan-400 text-cyan-300 shadow-2xl shadow-cyan-500/40 scale-105' 
                    : 'bg-slate-950 border-slate-800 text-slate-700'
                ]"
              >
                {{ digit || '·' }}
              </div>
            </div>

            <!-- Preview Minimalista en Kiosk -->
            <div v-if="previewWorker && previewStatus?.canSubmit" class="w-full max-w-lg p-4 rounded-2xl bg-blue-950/80 border border-cyan-500/80 text-white shadow-xl space-y-1">
              <span class="text-xs uppercase font-mono font-black tracking-widest text-cyan-400 block">
                PASAJERO IDENTIFICADO
              </span>
              <h3 class="text-2xl font-black uppercase text-white">
                {{ previewWorker.nombres }} {{ previewWorker.apellidos || (previewWorker.apellido_paterno + ' ' + (previewWorker.apellido_materno || '')) }}
              </h3>
              <p class="text-sm text-slate-300">
                {{ previewWorker.empresa?.razon_social || previewWorker.empresa_nombre || 'Contratista' }}
              </p>
              <div class="pt-2 border-t border-blue-800 flex items-center justify-between">
                <span class="text-xs text-cyan-300">Asiento: <strong class="text-lg text-white font-mono">#{{ previewStatus.nextSeat }}</strong></span>
                <span class="text-xs text-emerald-400 font-bold">Presione ENTER para confirmar</span>
              </div>
            </div>

            <!-- Último pasajero confirmado -->
            <div v-else-if="lastCheckedInPassenger" class="w-full max-w-lg p-4 rounded-2xl bg-emerald-950/60 border border-emerald-500/80 text-emerald-200 shadow-xl space-y-1">
              <span class="text-xs uppercase font-mono font-black tracking-widest text-emerald-400 block">
                ✓ EMBARQUE CONFIRMADO
              </span>
              <h3 class="text-2xl font-black text-white uppercase">
                {{ lastCheckedInPassenger.nombre_completo }}
              </h3>
              <p class="text-base">
                ASIENTO: <strong class="text-2xl text-white font-mono font-black">N° {{ lastCheckedInPassenger.asiento }}</strong>
              </p>
            </div>

            <p class="text-xs text-slate-500 font-mono">
              Presione [Enter] en el teclado una vez completados sus 8 dígitos
            </p>
          </div>

        </div>

        <div class="border-t border-slate-800 pt-4 flex items-center justify-between text-xs text-slate-500 font-mono">
          <span>Sistema Integrado de Movilización de Personal</span>
          <span>Presione Esc para salir</span>
        </div>

      </div>
    </Teleport>

  </div>
</template>
