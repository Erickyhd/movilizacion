<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Building2, Plus, Check, ShieldCheck } from 'lucide-vue-next';

defineProps({
  empresas: Array,
});

const showModal = ref(false);
const form = useForm({
  ruc: '',
  razon_social: '',
  es_contratista: true,
});

const submit = () => {
  form.post(route('empresas.store'), {
    onSuccess: () => {
      form.reset();
      showModal.value = false;
    },
  });
};
</script>

<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto space-y-6">
      
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
          <h2 class="text-xl font-bold text-slate-900 flex items-center">
            <Building2 class="w-6 h-6 text-blue-600 mr-2" /> Empresas y Contratistas
          </h2>
          <p class="text-sm text-slate-500 mt-1">Directorio de empresas titulares y contratistas para asignación de trabajadores.</p>
        </div>
        <button 
          @click="showModal = true"
          class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm flex items-center space-x-2 transition"
        >
          <Plus class="w-4 h-4" />
          <span>Registrar Empresa</span>
        </button>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase border-b border-slate-100">
              <tr>
                <th class="px-6 py-3">RUC</th>
                <th class="px-6 py-3">Razón Social</th>
                <th class="px-6 py-3">Tipo de Empresa</th>
                <th class="px-6 py-3">Trabajadores Asignados</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="e in empresas" :key="e.id" class="hover:bg-slate-50/80 transition">
                <td class="px-6 py-4 font-mono font-bold text-slate-900">{{ e.ruc }}</td>
                <td class="px-6 py-4 font-semibold text-slate-800">{{ e.razon_social }}</td>
                <td class="px-6 py-4">
                  <span v-if="e.es_contratista" class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                    Contratista / Tercero
                  </span>
                  <span v-else class="px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                    Empresa Principal
                  </span>
                </td>
                <td class="px-6 py-4 text-slate-700 font-bold">
                  {{ e.trabajadores_count || 0 }} trabajadores
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal Registrar -->
      <div v-if="showModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="font-bold text-slate-900 text-lg">Registrar Nueva Empresa</h3>
            <button @click="showModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
          </div>
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">RUC (11 dígitos)</label>
              <input v-model="form.ruc" type="text" maxlength="11" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none font-mono" placeholder="20123456789" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Razón Social</label>
              <input v-model="form.razon_social" type="text" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Servicios Mineros S.A.C." />
            </div>
            <div class="flex items-center space-x-2 pt-1">
              <input v-model="form.es_contratista" type="checkbox" id="es_c" class="w-4 h-4 rounded text-blue-600 border-slate-300" />
              <label for="es_c" class="text-sm text-slate-700 cursor-pointer">Es Empresa Contratista</label>
            </div>
            <div class="flex justify-end space-x-2 pt-2">
              <button type="button" @click="showModal = false" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg">Cancelar</button>
              <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-500">Guardar</button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </AppLayout>
</template>