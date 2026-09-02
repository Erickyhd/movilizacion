<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { 
  Users, 
  UserPlus, 
  Edit3, 
  Trash2, 
  RotateCcw, 
  X, 
  Eye, 
  EyeOff, 
  Search,
  ShieldCheck,
  Lock,
  CheckCircle2
} from 'lucide-vue-next';

const props = defineProps({
  users: Array,
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const activeTabFilter = ref('active'); // 'active', 'inactive', 'all'
const searchQuery = ref('');
const isDrawerOpen = ref(false);
const editingUser = ref(null);
const currentPage = ref(1);
const perPage = ref(15);

watch(searchQuery, () => {
  currentPage.value = 1;
});

const totalPages = computed(() => Math.ceil(filteredUsers.value.length / perPage.value) || 1);

const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  return filteredUsers.value.slice(start, start + perPage.value);
});
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
  rol: 'OPERADOR',
  permisos: {
    usuarios: 'LECTURA',
    empresas: 'ESCRITURA',
    trabajadores: 'ESCRITURA',
    rutas: 'ESCRITURA',
    flota: 'ESCRITURA',
    manifiestos: 'ESCRITURA',
  },
});

const openCreateDrawer = () => {
  editingUser.value = null;
  form.reset();
  form.rol = 'OPERADOR';
  form.permisos = {
    usuarios: 'LECTURA',
    empresas: 'ESCRITURA',
    trabajadores: 'ESCRITURA',
    rutas: 'ESCRITURA',
    flota: 'ESCRITURA',
    manifiestos: 'ESCRITURA',
  };
  isDrawerOpen.value = true;
};

const openEditDrawer = (u) => {
  editingUser.value = u;
  form.name = u.name;
  form.email = u.email;
  form.password = '';
  form.rol = u.rol || 'OPERADOR';
  form.permisos = {
    usuarios: u.permisos?.usuarios || 'LECTURA',
    empresas: u.permisos?.empresas || 'ESCRITURA',
    trabajadores: u.permisos?.trabajadores || 'ESCRITURA',
    rutas: u.permisos?.rutas || 'ESCRITURA',
    flota: u.permisos?.flota || 'ESCRITURA',
    manifiestos: u.permisos?.manifiestos || 'ESCRITURA',
  };
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

const showConfirmModal = ref(false);
const userToToggle = ref(null);

const confirmToggleEstado = (u) => {
  userToToggle.value = u;
  showConfirmModal.value = true;
};

const executeToggleEstado = () => {
  if (userToToggle.value) {
    router.delete(route('usuarios.destroy', userToToggle.value.id), {
      onSuccess: () => {
        showConfirmModal.value = false;
        userToToggle.value = null;
      }
    });
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
            <Users class="w-6 h-6 text-blue-600 mr-2.5" /> Administración de Usuarios y Permisos
          </h2>
          <p class="text-sm text-slate-500 mt-1">Configura roles, jerarquías y privilegios de lectura/escritura por módulo.</p>
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
                <th class="px-6 py-3.5">Rol / Jerarquía</th>
                <th class="px-6 py-3.5">Estado</th>
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
                  <span v-if="u.rol === 'ADMIN'" class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-purple-100 text-purple-800 border border-purple-200 inline-flex items-center">
                    <ShieldCheck class="w-3.5 h-3.5 mr-1 text-purple-600" /> Super Administrador
                  </span>
                  <span v-else-if="u.rol === 'OPERADOR'" class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                    Operador Modulante
                  </span>
                  <span v-else class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                    Lector (Solo Lectura)
                  </span>
                </td>
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
                
                <td class="px-6 py-4 text-right space-x-1 whitespace-nowrap">
                  <button 
                    @click="openEditDrawer(u)"
                    title="Editar usuario y permisos"
                    class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50/80 rounded-lg transition cursor-pointer"
                  >
                    <Edit3 class="w-3.5 h-3.5" />
                  </button>
                  <button 
                    @click="confirmToggleEstado(u)"
                    :title="u.estado == 1 ? 'Desactivar usuario' : 'Reactivar usuario'"
                    :class="[
                      'p-1.5 rounded-lg transition cursor-pointer',
                      u.estado == 1 
                        ? 'text-slate-400 hover:text-red-600 hover:bg-red-50/80' 
                        : 'text-slate-400 hover:text-emerald-600 hover:bg-emerald-50/80'
                    ]"
                  >
                    <component :is="u.estado == 1 ? Trash2 : RotateCcw" class="w-3.5 h-3.5" />
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

      <!-- Teleported Modern Slide-Over Drawer with Permissions Matrix -->
      <Teleport to="body">
        <div v-if="isDrawerOpen" class="fixed inset-0 z-[9999] overflow-hidden">
          <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="isDrawerOpen = false"></div>

          <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
            <div class="w-screen max-w-lg bg-white shadow-2xl flex flex-col transform transition duration-300 border-l border-slate-200">
              
              <!-- Drawer Header -->
              <div class="p-6 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white">
                    <UserPlus v-if="!editingUser" class="w-5 h-5" />
                    <Edit3 v-else class="w-5 h-5" />
                  </div>
                  <div>
                    <h3 class="font-extrabold text-lg text-slate-100">
                      {{ editingUser ? 'Editar Usuario y Permisos' : 'Nuevo Usuario' }}
                    </h3>
                    <span class="text-xs text-blue-300 block">Formulario de control de accesos</span>
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

                <div>
                  <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Rol del Usuario</label>
                  <select v-model="form.rol" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="ADMIN">ADMIN - Superusuario (Acceso Total)</option>
                    <option value="OPERADOR">OPERADOR - Permisos Personalizables por Módulo</option>
                    <option value="LECTOR">LECTOR - Solo Lectura en Todo el Sistema</option>
                  </select>
                </div>

                <!-- Matriz de Permisos por Módulo -->
                <div v-if="form.rol !== 'ADMIN'" class="space-y-3 pt-2 border-t border-slate-100">
                  <div class="flex items-center justify-between">
                    <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Permisos por Módulo</h4>
                    <span class="text-[11px] text-slate-500">Lectura vs Escritura</span>
                  </div>

                  <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80 space-y-3">
                    
                    <div class="flex items-center justify-between">
                      <span class="text-xs font-bold text-slate-800">Módulo Usuarios</span>
                      <select v-model="form.permisos.usuarios" class="text-xs font-semibold bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 focus:ring-1 focus:ring-blue-500 outline-none">
                        <option value="ESCRITURA">Escritura (Crear, Editar, Eliminar)</option>
                        <option value="LECTURA">Lectura (Solo ver datos)</option>
                      </select>
                    </div>

                    <div class="flex items-center justify-between">
                      <span class="text-xs font-bold text-slate-800">Módulo Empresas</span>
                      <select v-model="form.permisos.empresas" class="text-xs font-semibold bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 focus:ring-1 focus:ring-blue-500 outline-none">
                        <option value="ESCRITURA">Escritura (Crear, Editar, Eliminar)</option>
                        <option value="LECTURA">Lectura (Solo ver datos)</option>
                      </select>
                    </div>

                    <div class="flex items-center justify-between">
                      <span class="text-xs font-bold text-slate-800">Módulo Trabajadores</span>
                      <select v-model="form.permisos.trabajadores" class="text-xs font-semibold bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 focus:ring-1 focus:ring-blue-500 outline-none">
                        <option value="ESCRITURA">Escritura (Crear, Editar, Eliminar)</option>
                        <option value="LECTURA">Lectura (Solo ver datos)</option>
                      </select>
                    </div>

                    <div class="flex items-center justify-between">
                      <span class="text-xs font-bold text-slate-800">Módulo Rutas</span>
                      <select v-model="form.permisos.rutas" class="text-xs font-semibold bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 focus:ring-1 focus:ring-blue-500 outline-none">
                        <option value="ESCRITURA">Escritura (Crear, Editar, Eliminar)</option>
                        <option value="LECTURA">Lectura (Solo ver datos)</option>
                      </select>
                    </div>

                    <div class="flex items-center justify-between">
                      <span class="text-xs font-bold text-slate-800">Módulo Flota & Choferes</span>
                      <select v-model="form.permisos.flota" class="text-xs font-semibold bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 focus:ring-1 focus:ring-blue-500 outline-none">
                        <option value="ESCRITURA">Escritura (Crear, Editar, Eliminar)</option>
                        <option value="LECTURA">Lectura (Solo ver datos)</option>
                      </select>
                    </div>

                    <div class="flex items-center justify-between">
                      <span class="text-xs font-bold text-slate-800">Módulo Manifiestos</span>
                      <select v-model="form.permisos.manifiestos" class="text-xs font-semibold bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 focus:ring-1 focus:ring-blue-500 outline-none">
                        <option value="ESCRITURA">Escritura (Crear, Editar, Cancelar)</option>
                        <option value="LECTURA">Lectura (Solo ver datos)</option>
                      </select>
                    </div>

                  </div>
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
      </Teleport>

          <!-- Reusable Confirmation Modal -->
      <ConfirmModal 
        :show="showConfirmModal"
        :title="userToToggle && userToToggle.estado == 1 ? 'Inhabilitar Usuario' : 'Reactivar Usuario'"
        :message="userToToggle ? 'Desea ' + (userToToggle.estado == 1 ? 'desactivar' : 'reactivar') + ' la cuenta del usuario ' + userToToggle.name + '?' : ''"
        :confirmText="userToToggle && userToToggle.estado == 1 ? 'Sí, Inhabilitar' : 'Sí, Reactivar'"
        :variant="userToToggle && userToToggle.estado == 1 ? 'danger' : 'success'"
        @close="showConfirmModal = false"
        @confirm="executeToggleEstado"
      />
    </div>
  </AppLayout>
</template>