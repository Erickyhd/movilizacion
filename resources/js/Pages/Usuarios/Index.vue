<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Users, UserPlus, Key, Shield, CheckCircle, Mail } from 'lucide-vue-next';

defineProps({
  users: Array,
});

const showCreateModal = ref(false);
const selectedUserForReset = ref(null);

const createForm = useForm({
  name: '',
  email: '',
  password: '',
});

const resetForm = useForm({
  password: '',
});

const submitCreate = () => {
  createForm.post(route('usuarios.store'), {
    onSuccess: () => {
      createForm.reset();
      showCreateModal.value = false;
    },
  });
};

const submitReset = () => {
  if (!selectedUserForReset.value) return;
  resetForm.post(route('usuarios.reset-password', selectedUserForReset.value.id), {
    onSuccess: () => {
      resetForm.reset();
      selectedUserForReset.value = null;
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
            <Users class="w-6 h-6 text-blue-600 mr-2" /> Gestión de Usuarios del Sistema
          </h2>
          <p class="text-sm text-slate-500 mt-1">Administra los accesos y credenciales de los operadores locales.</p>
        </div>
        <button 
          @click="showCreateModal = true"
          class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm flex items-center space-x-2 transition cursor-pointer"
        >
          <UserPlus class="w-4 h-4" />
          <span>Nuevo Usuario</span>
        </button>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase border-b border-slate-100">
              <tr>
                <th class="px-6 py-3">Nombre</th>
                <th class="px-6 py-3">Correo Electrónico</th>
                <th class="px-6 py-3">Fecha de Registro</th>
                <th class="px-6 py-3 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="u in users" :key="u.id" class="hover:bg-slate-50/80 transition">
                <td class="px-6 py-4 font-semibold text-slate-900 flex items-center space-x-3">
                  <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center text-xs">
                    {{ u.name.substring(0,2).toUpperCase() }}
                  </div>
                  <span>{{ u.name }}</span>
                </td>
                <td class="px-6 py-4 text-slate-700">{{ u.email }}</td>
                <td class="px-6 py-4 text-xs text-slate-500">
                  {{ new Date(u.created_at).toLocaleDateString('es-PE') }}
                </td>
                <td class="px-6 py-4 text-right">
                  <button 
                    @click="selectedUserForReset = u"
                    class="text-xs font-semibold text-amber-600 hover:text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 px-3 py-1.5 rounded-lg inline-flex items-center space-x-1 transition cursor-pointer"
                  >
                    <Key class="w-3.5 h-3.5" />
                    <span>Cambiar Clave</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Create User Modal -->
      <div v-if="showCreateModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="font-bold text-slate-900 text-lg">Crear Nuevo Usuario</h3>
            <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
          </div>
          <form @submit.prevent="submitCreate" class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nombre Completo</label>
              <input v-model="createForm.name" type="text" required class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-900 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Juan Pérez" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Correo Electrónico</label>
              <input v-model="createForm.email" type="email" required class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-900 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="juan@empresa.com" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Contraseña Inicial</label>
              <input v-model="createForm.password" type="password" required class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-900 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="••••••••" />
            </div>
            <div class="flex justify-end space-x-2 pt-2">
              <button type="button" @click="showCreateModal = false" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg">Cancelar</button>
              <button type="submit" :disabled="createForm.processing" class="px-4 py-2 text-sm bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-500">Guardar</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Reset Password Modal -->
      <div v-if="selectedUserForReset" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="font-bold text-slate-900 text-lg">Cambiar Clave: {{ selectedUserForReset.name }}</h3>
            <button @click="selectedUserForReset = null" class="text-slate-400 hover:text-slate-600">✕</button>
          </div>
          <form @submit.prevent="submitReset" class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nueva Contraseña</label>
              <input v-model="resetForm.password" type="password" required class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-900 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-amber-500 outline-none" placeholder="••••••••" />
            </div>
            <div class="flex justify-end space-x-2 pt-2">
              <button type="button" @click="selectedUserForReset = null" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg">Cancelar</button>
              <button type="submit" :disabled="resetForm.processing" class="px-4 py-2 text-sm bg-amber-600 text-white font-semibold rounded-lg hover:bg-amber-500">Actualizar Clave</button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </AppLayout>
</template>