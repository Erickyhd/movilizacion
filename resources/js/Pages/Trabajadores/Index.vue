<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { UserCheck, UserPlus, ShieldCheck, AlertTriangle, Ban } from 'lucide-vue-next';

defineProps({
  trabajadores: Array,
  empresas: Array,
});

const showModal = ref(false);
const form = useForm({
  empresa_id: '',
  dni: '',
  nombres: '',
  apellidos: '',
  cargo: '',
  grupo_sanguineo: 'O+',
  telefono_emergencia: '',
  estado_acreditacion: 'APTO',
});

const submit = () => {
  form.post(route('trabajadores.store'), {
    onSuccess: () => {
      form.reset();
      showModal.value = false;
    },
  });
};

const getAccreditationBadge = (status) => {
  switch (status) {
    case 'APTO': return { text: 'APTO', bg: 'bg-emerald-100 text-emerald-800 border-emerald-200', icon: ShieldCheck };
    case 'OBSERVADO': return { text: 'OBSERVADO', bg: 'bg-amber-100 text-amber-800 border-amber-200', icon: AlertTriangle };
    case 'BLOQUEADO': return { text: 'BLOQUEADO', bg: 'bg-red-100 text-red-800 border-red-200', icon: Ban };
    default: return { text: status, bg: 'bg-slate-100 text-slate-800', icon: ShieldCheck };
  }
};
</script>

<template>
  <AppLayout>
    <div class="w-full space-y-6">
      
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
          <h2 class="text-xl font-bold text-slate-900 flex items-center">
            <UserCheck class="w-6 h-6 text-blue-600 mr-2" /> Padrón de Trabajadores y Acreditación
          </h2>
          <p class="text-sm text-slate-500 mt-1">Control de aptitud médica EMO, pases de ingreso e información de emergencia.</p>
        </div>
        <button 
          @click="showModal = true"
          class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm flex items-center space-x-2 transition cursor-pointer"
        >
          <UserPlus class="w-4 h-4" />
          <span>Nuevo Trabajador</span>
        </button>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase border-b border-slate-100">
              <tr>
                <th class="px-6 py-3">DNI</th>
                <th class="px-6 py-3">Nombres y Apellidos</th>
                <th class="px-6 py-3">Cargo / Puesto</th>
                <th class="px-6 py-3">Empresa</th>
                <th class="px-6 py-3">Grupo Sang.</th>
                <th class="px-6 py-3">Estado HSEQ</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="t in trabajadores" :key="t.id" class="hover:bg-slate-50/80 transition">
                <td class="px-6 py-4 font-mono font-bold text-slate-900">{{ t.dni }}</td>
                <td class="px-6 py-4 font-semibold text-slate-800">{{ t.nombres }} {{ t.apellidos }}</td>
                <td class="px-6 py-4 text-slate-600">{{ t.cargo || '-' }}</td>
                <td class="px-6 py-4 text-slate-700 font-medium">{{ t.empresa?.razon_social }}</td>
                <td class="px-6 py-4 font-mono text-xs font-bold text-red-600">{{ t.grupo_sanguineo || 'N/A' }}</td>
                <td class="px-6 py-4">
                  <span :class="['px-2.5 py-1 rounded-full text-xs font-bold border inline-flex items-center space-x-1', getAccreditationBadge(t.estado_acreditacion).bg]">
                    <component :is="getAccreditationBadge(t.estado_acreditacion).icon" class="w-3.5 h-3.5 mr-1" />
                    {{ t.estado_acreditacion }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal Nuevo Trabajador -->
      <div v-if="showModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="font-bold text-slate-900 text-lg">Registrar Trabajador</h3>
            <button @click="showModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
          </div>
          <form @submit.prevent="submit" class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">DNI</label>
              <input v-model="form.dni" type="text" maxlength="15" required class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-900 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none font-mono" placeholder="71234567" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Empresa</label>
              <select v-model="form.empresa_id" required class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-900 font-medium focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="" disabled>Seleccione Empresa</option>
                <option v-for="e in empresas" :key="e.id" :value="e.id">{{ e.razon_social }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nombres</label>
              <input v-model="form.nombres" type="text" required class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-900 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Carlos" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Apellidos</label>
              <input v-model="form.apellidos" type="text" required class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-900 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Mendoza" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Cargo</label>
              <input v-model="form.cargo" type="text" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-900 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Ingeniero de Campo" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Grupo Sanguíneo</label>
              <select v-model="form.grupo_sanguineo" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-900 font-medium focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="O+">O+</option>
                <option value="O-">O-</option>
                <option value="A+">A+</option>
                <option value="A-">A-</option>
                <option value="B+">B+</option>
                <option value="AB+">AB+</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Teléfono Emergencia</label>
              <input v-model="form.telefono_emergencia" type="text" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-900 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="987654321" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Estado Acreditación</label>
              <select v-model="form.estado_acreditacion" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-900 font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="APTO">APTO</option>
                <option value="OBSERVADO">OBSERVADO</option>
                <option value="BLOQUEADO">BLOQUEADO</option>
              </select>
            </div>

            <div class="col-span-2 flex justify-end space-x-2 pt-3 border-t border-slate-100">
              <button type="button" @click="showModal = false" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg">Cancelar</button>
              <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-500">Guardar</button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </AppLayout>
</template>