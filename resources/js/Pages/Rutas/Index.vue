<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { MapPin, Plus, Clock, Navigation } from 'lucide-vue-next';

defineProps({
  rutas: Array,
});

const showModal = ref(false);
const form = useForm({
  origen: '',
  destino: '',
  duracion_estimada_minutos: 120,
});

const submit = () => {
  form.post(route('rutas.store'), {
    onSuccess: () => {
      form.reset();
      showModal.value = false;
    },
  });
};
</script>

<template>
  <AppLayout>
    <div class="w-full space-y-6">
      
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
          <h2 class="text-xl font-bold text-slate-900 flex items-center">
            <MapPin class="w-6 h-6 text-blue-600 mr-2" /> Catálogo de Rutas y Puntos
          </h2>
          <p class="text-sm text-slate-500 mt-1">Configuración de origenes, destinos y tiempos estimados de viaje.</p>
        </div>
        <button 
          @click="showModal = true"
          class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm flex items-center space-x-2 transition cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>Nueva Ruta</span>
        </button>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase border-b border-slate-100">
              <tr>
                <th class="px-6 py-3">Origen</th>
                <th class="px-6 py-3">Destino</th>
                <th class="px-6 py-3">Duración Estimada</th>
                <th class="px-6 py-3">Estado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="r in rutas" :key="r.id" class="hover:bg-slate-50/80 transition">
                <td class="px-6 py-4 font-semibold text-slate-900 flex items-center">
                  <Navigation class="w-4 h-4 text-blue-500 mr-2" /> {{ r.origen }}
                </td>
                <td class="px-6 py-4 font-semibold text-slate-900">{{ r.destino }}</td>
                <td class="px-6 py-4 text-slate-700">
                  <span class="inline-flex items-center text-xs font-semibold bg-slate-100 px-2.5 py-1 rounded-md text-slate-700">
                    <Clock class="w-3.5 h-3.5 mr-1 text-slate-400" />
                    {{ Math.floor(r.duracion_estimada_minutos / 60) }}h {{ r.duracion_estimada_minutos % 60 }}m
                  </span>
                </td>
                <td class="px-6 py-4">
                  <span v-if="r.activa" class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Activa</span>
                  <span v-else class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">Inactiva</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal -->
      <div v-if="showModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="font-bold text-slate-900 text-lg">Registrar Ruta</h3>
            <button @click="showModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
          </div>
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Origen</label>
              <input v-model="form.origen" type="text" required class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-900 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Arequipa (Base Central)" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Destino</label>
              <input v-model="form.destino" type="text" required class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-900 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Mina Las Bambas" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Duración Estimada (Minutos)</label>
              <input v-model="form.duracion_estimada_minutos" type="number" min="1" required class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-900 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="240" />
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