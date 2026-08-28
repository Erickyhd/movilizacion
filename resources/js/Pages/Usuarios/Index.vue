<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
  Users, 
  UserPlus, 
  Edit3, 
  Trash2, 
  RotateCcw, 
  X, 
  Eye, 
  EyeOff, 
  Search
} from 'lucide-vue-next';

const props = defineProps({
  users: Array,
});

const activeTabFilter = ref('active'); // 'active', 'inactive', 'all'
const searchQuery = ref('');
const isDrawerOpen = ref(false);
const editingUser = ref(null);
const showPassword = ref(false);

const filteredUsers = computed(() => {
  return (props.users || []).filter(u => {
    const matchesFilter = 
      activeTabFilter.value === 'all' ? true :
      activeTabFilter.value === 'active' ? u.estado == 1 :
      u.estado == 0;
    
    const matchesSearch = 
      u.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      u.email.toLowerCase().includes(searchQuery.value.toLowerCase());

    return matchesFilter && matchesSearch;
  });
});

const form = useForm({
  name: '',
  email: '',
  password: '',
});

const openCreateDrawer = () => {
  editingUser.value = null;
  form.reset();
  isDrawerOpen.value = true;
};

const openEditDrawer = (u) => {
  editingUser.value = u;
  form.name = u.name;
  form.email = u.email;
  form.password = '';
  isDrawerOpen.value = true;
};

const submitForm = () => {
  if (editingUser.value) {
    form.put(route('usuarios.update', editingUser.value.id), {
      onSuccess: () => {
        form.reset();
        isDrawerOpen.value = false;
        editingUser.value = null;
      },
    });
  } else {
    form.post(route('usuarios.store'), {
      onSuccess: () => {
        form.reset();
        isDrawerOpen.value = false;
      },
    });
  }
};

const toggleEstado = (u) => {
  const accion = u.estado == 1 ? 'eliminar' : 'reactivar';
  if (confirm(`¿Confirmas que deseas ${accion} al usuario ${u.name}?`)) {
    router.delete(route('usuarios.destroy', u.id));
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
            <Users class="w-6 h-6 text-blue-600 mr-2.5" /> Gestión de Usuarios
          </h2>
          <p class="text-sm text-slate-500 mt-1">Administración de operadores y accesos del sistema.</p>
        </div>
        <button 
          @click="openCreateDrawer"
          class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-md hover:shadow-blue-500/20 flex items-center space-x-2 transition cursor-pointer"
        >
          <UserPlus class="w-4 h-4" />
          <span>Nuevo Usuario</span>
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
            Activos ({{ users.filter(u => u.estado == 1).length }})
          </button>
          <button 
            @click="activeTabFilter = 'inactive'"
            :class="['px-4 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer', activeTabFilter === 'inactive' ? 'bg-white text-red-700 shadow-sm' : 'text-slate-600 hover:text-slate-900']"
          >
            Inactivos ({{ users.filter(u => u.estado == 0).length }})
          </button>
          <button 
            @click="activeTabFilter = 'all'"
            :class="['px-4 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer', activeTabFilter === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900']"
          >
            Todos ({{ users.length }})
          </button>
        </div>

        <!-- Search input -->
        <div class="relative w-full sm:w-72">
          <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Buscar usuario o correo..." 
            class="w-full bg-white border border-slate-300 rounded-xl pl-9 pr-4 py-2 text-xs text-slate-900 font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none shadow-sm"
          />
        </div>
      </div>

      <!-- Users Table Container -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase border-b border-slate-100">
              <tr>
                <th class="px-6 py-3.5">Usuario</th>
                <th class="px-6 py-3.5">Correo Electrónico</th>
                <th class="px-6 py-3.5">Estado</th>
                <th class="px-6 py-3.5">Fecha Registro</th>
                <th class="px-6 py-3.5 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="u in filteredUsers" :key="u.id" :class="['hover:bg-slate-50/80 transition', u.estado == 0 ? 'bg-red-50/30 opacity-75' : '']">
                <td class="px-6 py-4 font-semibold text-slate-900 flex items-center space-x-3">
                  <div :class="['w-9 h-9 rounded-xl font-extrabold flex items-center justify-center text-xs shadow-inner', u.estado == 1 ? 'bg-blue-100 text-blue-800' : 'bg-slate-200 text-slate-600']">
                    {{ u.name.substring(0,2).toUpperCase() }}
                  </div>
                  <div>
                    <span class="font-bold text-slate-900 block">{{ u.name }}</span>
                  </div>
                </td>
                <td class="px-6 py-4 text-slate-800 font-medium">{{ u.email }}</td>
                <td class="px-6 py-4">
                  <span v-if="u.estado == 1" class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 inline-flex items-center space-x-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 mr-1"></span>
                    Activo
                  </span>
                  <span v-else class="px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200 inline-flex items-center space-x-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-600 mr-1"></span>
                    Inactivo
                  </span>
                </td>
                <td class="px-6 py-4 text-xs text-slate-500">
                  {{ new Date(u.created_at).toLocaleDateString('es-PE') }}
                </td>
                
                <!-- Direct Action Buttons Column (Clean, no popups or distortion) -->
                <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                  <button 
                    @click="openEditDrawer(u)"
                    title="Editar usuario"
                    class="p-2 text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 border border-blue-200/80 rounded-xl transition cursor-pointer"
                  >
                    <Edit3 class="w-4 h-4" />
                  </button>
                  <button 
                    @click="toggleEstado(u)"
                    :title="u.estado == 1 ? 'Eliminar usuario' : 'Reactivar usuario'"
                    :class="[
                      'p-2 rounded-xl border transition cursor-pointer',
                      u.estado == 1 
                        ? 'text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 border-red-200/80' 
                        : 'text-emerald-600 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border-emerald-200/80'
                    ]"
                  >
                    <component :is="u.estado == 1 ? Trash2 : RotateCcw" class="w-4 h-4" />
                  </button>
                </td>
              </tr>
              <tr v-if="!filteredUsers || filteredUsers.length === 0">
                <td colspan="5" class="px-6 py-8 text-center text-slate-400 text-sm">
                  No se encontraron usuarios en este listado.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modern Slide-Over Drawer -->
      <div v-if="isDrawerOpen" class="fixed inset-0 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="isDrawerOpen = false"></div>

        <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
          <div class="w-screen max-w-md bg-white shadow-2xl flex flex-col transform transition duration-300 border-l border-slate-200">
            
            <!-- Drawer Header -->
            <div class="p-6 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
              <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white">
                  <UserPlus v-if="!editingUser" class="w-5 h-5" />
                  <Edit3 v-else class="w-5 h-5" />
                </div>
                <div>
                  <h3 class="font-extrabold text-lg text-slate-100">
                    {{ editingUser ? 'Editar Usuario' : 'Nuevo Usuario' }}
                  </h3>
                  <span class="text-xs text-blue-300 block">Formulario de registro</span>
                </div>
              </div>
              <button @click="isDrawerOpen = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 cursor-pointer">
                <X class="w-5 h-5" />
              </button>
            </div>

            <!-- Drawer Form -->
            <form @submit.prevent="submitForm" class="flex-1 overflow-y-auto p-6 space-y-5">
              
              <div>
                <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Nombre Completo</label>
                <input 
                  v-model="form.name" 
                  type="text" 
                  required 
                  class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none shadow-xs" 
                  placeholder="Carlos Mendoza" 
                />
              </div>

              <div>
                <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Correo Electrónico</label>
                <input 
                  v-model="form.email" 
                  type="email" 
                  required 
                  class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none shadow-xs" 
                  placeholder="carlos@empresa.com" 
                />
              </div>

              <div>
                <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                  {{ editingUser ? 'Nueva Contraseña (Opcional)' : 'Contraseña' }}
                </label>
                <div class="relative">
                  <input 
                    v-model="form.password" 
                    :type="showPassword ? 'text' : 'password'" 
                    :required="!editingUser"
                    class="w-full bg-white border border-slate-300 rounded-xl pl-3.5 pr-12 py-2.5 text-sm text-slate-900 font-semibold placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none shadow-xs" 
                    placeholder="••••••••" 
                  />
                  <button 
                    type="button" 
                    @click="showPassword = !showPassword"
                    class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-700 p-1 cursor-pointer"
                  >
                    <component :is="showPassword ? EyeOff : Eye" class="w-4 h-4" />
                  </button>
                </div>
                <span v-if="editingUser" class="text-[11px] text-slate-400 mt-1 block">Déjalo en blanco si no deseas cambiar la clave actual.</span>
              </div>

              <!-- Footer Actions -->
              <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                <button 
                  type="button" 
                  @click="isDrawerOpen = false" 
                  class="px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer"
                >
                  Cancelar
                </button>
                <button 
                  type="submit" 
                  :disabled="form.processing"
                  class="px-5 py-2.5 text-sm bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl shadow-md hover:shadow-blue-500/20 transition disabled:opacity-50 cursor-pointer"
                >
                  {{ editingUser ? 'Guardar Cambios' : 'Registrar Usuario' }}
                </button>
              </div>

            </form>

          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>