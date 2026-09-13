<script setup>
import { ref, computed, watch } from 'vue';
import TablePagination from '@/Components/TablePagination.vue';
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
  Sliders
} from 'lucide-vue-next';

const props = defineProps({
  users: Array,
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);
const canWrite = computed(() => {
  return currentUser.value?.rol === 'ADMIN';
});

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
      (u.name || '').toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      (u.email || '').toLowerCase().includes(searchQuery.value.toLowerCase());

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

// Sincronizar permisos automáticamente según el rol seleccionado
watch(() => form.rol, (newRol) => {
  if (newRol === 'ADMIN') {
    form.permisos = {
      usuarios: 'ESCRITURA',
      empresas: 'ESCRITURA',
      trabajadores: 'ESCRITURA',
      rutas: 'ESCRITURA',
      flota: 'ESCRITURA',
      manifiestos: 'ESCRITURA',
    };
  } else if (newRol === 'LECTOR') {
    form.permisos = {
      usuarios: 'LECTURA',
      empresas: 'LECTURA',
      trabajadores: 'LECTURA',
      rutas: 'LECTURA',
      flota: 'LECTURA',
      manifiestos: 'LECTURA',
    };
  } else {
    form.permisos = {
      usuarios: 'LECTURA',
      empresas: form.permisos?.empresas || 'ESCRITURA',
      trabajadores: form.permisos?.trabajadores || 'ESCRITURA',
      rutas: form.permisos?.rutas || 'ESCRITURA',
      flota: form.permisos?.flota || 'ESCRITURA',
      manifiestos: form.permisos?.manifiestos || 'ESCRITURA',
    };
  }
});

const openCreateDrawer = () => {
  if (!canWrite.value) return;
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
  if (!canWrite.value) return;
  editingUser.value = u;
  form.name = u.name;
  form.email = u.email;
  form.password = '';
  form.rol = u.rol || 'OPERADOR';

  if (form.rol === 'ADMIN') {
    form.permisos = {
      usuarios: 'ESCRITURA',
      empresas: 'ESCRITURA',
      trabajadores: 'ESCRITURA',
      rutas: 'ESCRITURA',
      flota: 'ESCRITURA',
      manifiestos: 'ESCRITURA',
    };
  } else if (form.rol === 'LECTOR') {
    form.permisos = {
      usuarios: 'LECTURA',
      empresas: 'LECTURA',
      trabajadores: 'LECTURA',
      rutas: 'LECTURA',
      flota: 'LECTURA',
      manifiestos: 'LECTURA',
    };
  } else {
    form.permisos = {
      usuarios: 'LECTURA',
      empresas: u.permisos?.empresas || 'ESCRITURA',
      trabajadores: u.permisos?.trabajadores || 'ESCRITURA',
      rutas: u.permisos?.rutas || 'ESCRITURA',
      flota: u.permisos?.flota || 'ESCRITURA',
      manifiestos: u.permisos?.manifiestos || 'ESCRITURA',
    };
  }

  isDrawerOpen.value = true;
};

const submitForm = () => {
  if (!canWrite.value) return;
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
  if (!canWrite.value) return;
  userToToggle.value = u;
  showConfirmModal.value = true;
};

const executeToggleEstado = () => {
  if (!canWrite.value) return;
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
      <!-- Header Banner & Main Actions -->
      <div class="theme-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h2 class="text-xl font-extrabold theme-text-title flex items-center">
            <Users class="w-6 h-6 text-blue-600 mr-2.5" /> Administración de Usuarios y Permisos
          </h2>
          <p class="text-sm theme-text-muted mt-1">Configura roles, jerarquías y privilegios de lectura/escritura por módulo.</p>
        </div>
        <button 
          v-if="canWrite"
          @click="openCreateDrawer"
          class="theme-btn-primary text-sm px-4 py-2.5 flex items-center space-x-2 shrink-0"
        >
          <UserPlus class="w-4 h-4" />
          <span>Nuevo Usuario</span>
        </button>
      </div>

      <!-- Filters & Search Bar Container (Matching Empresas style) -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 theme-card-subtle p-2.5">
        <!-- Filter Tabs -->
        <div class="flex bg-[var(--theme-pill-bg)] p-1 rounded-xl w-full sm:w-auto">
          <button 
            @click="activeTabFilter = 'active'"
            :class="['px-4 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer', activeTabFilter === 'active' ? 'theme-tab-active shadow-sm' : 'theme-tab-inactive']"
          >
            Activos ({{ users.filter(u => u.estado == 1).length }})
          </button>
          <button 
            @click="activeTabFilter = 'inactive'"
            :class="['px-4 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer', activeTabFilter === 'inactive' ? 'theme-tab-active shadow-sm' : 'theme-tab-inactive']"
          >
            Inactivos ({{ users.filter(u => u.estado == 0).length }})
          </button>
          <button 
            @click="activeTabFilter = 'all'"
            :class="['px-4 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer', activeTabFilter === 'all' ? 'theme-tab-active shadow-sm' : 'theme-tab-inactive']"
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
            class="w-full theme-input pl-9 pr-4 py-2 text-xs font-medium placeholder:text-[var(--theme-text-muted)] shadow-sm"
          />
        </div>
      </div>

      <!-- Users Table Container (Full Width) -->
      <div class="theme-card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="theme-card-subtle text-xs font-bold theme-text-muted uppercase border-b border-[var(--theme-border)]">
              <tr>
                <th class="px-6 py-3.5">Usuario</th>
                <th class="px-6 py-3.5">Correo Electrónico</th>
                <th class="px-6 py-3.5">Rol / Jerarquía</th>
                <th class="px-6 py-3.5">Estado</th>
                <th v-if="canWrite" class="px-6 py-3.5 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="u in paginatedUsers" :key="u.id" :class="['hover:bg-[var(--palette-50)]/5 transition', u.estado == 0 ? 'bg-red-50/30 opacity-75' : '']">
                <td class="px-6 py-4 font-semibold theme-text-title flex items-center space-x-3">
                  <div :class="['w-9 h-9 rounded-xl font-extrabold flex items-center justify-center text-xs shadow-inner', u.estado == 1 ? 'bg-[var(--palette-500)]/15 text-[var(--palette-400)] border border-[var(--palette-500)]/30' : 'bg-[var(--theme-card-subtle)] theme-text-muted border border-[var(--theme-border)]']">
                    {{ (u.name || 'US').substring(0,2).toUpperCase() }}
                  </div>
                  <div>
                    <span class="font-bold theme-text-title block">{{ u.name }}</span>
                  </div>
                </td>
                <td class="px-6 py-4 theme-text-body font-medium">{{ u.email }}</td>
                <td class="px-6 py-4">
                  <span v-if="u.rol === 'ADMIN'" class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-purple-100 text-purple-800 border border-purple-200 inline-flex items-center">
                    <ShieldCheck class="w-3.5 h-3.5 mr-1 text-purple-600" /> Super Administrador
                  </span>
                  <span v-else-if="u.rol === 'OPERADOR'" class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 inline-flex items-center">
                    <Sliders class="w-3.5 h-3.5 mr-1 text-blue-600" /> Operador Modulante
                  </span>
                  <span v-else class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200 inline-flex items-center">
                    <Lock class="w-3.5 h-3.5 mr-1 text-slate-500" /> Lector (Solo Lectura)
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
                
                <td v-if="canWrite" class="px-6 py-4 text-right space-x-1 whitespace-nowrap">
                  <button 
                    @click="openEditDrawer(u)"
                    title="Editar usuario y permisos"
                    class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50/80 rounded-lg transition cursor-pointer"
                  >
                    <Edit3 class="w-4 h-4" />
                  </button>
                  <button 
                    @click="confirmToggleEstado(u)"
                    :title="u.estado == 1 ? 'Inhabilitar usuario' : 'Reactivar usuario'"
                    :class="['p-1.5 rounded-lg transition cursor-pointer', u.estado == 1 ? 'text-slate-400 hover:text-red-600 hover:bg-red-50/80' : 'text-slate-400 hover:text-emerald-600 hover:bg-emerald-50/80']"
                  >
                    <Trash2 v-if="u.estado == 1" class="w-4 h-4" />
                    <RotateCcw v-else class="w-4 h-4" />
                  </button>
                </td>
              </tr>

              <tr v-if="filteredUsers.length === 0">
                <td :colspan="canWrite ? 5 : 4" class="px-6 py-12 text-center text-slate-400 text-xs">
                  No se encontraron usuarios que coincidan con la búsqueda o filtro aplicado.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <TablePagination
          :currentPage="currentPage"
          :totalPages="totalPages"
          :totalItems="filteredUsers.length"
          :perPage="perPage"
          @update:currentPage="currentPage = $event"
          @update:perPage="perPage = $event; currentPage = 1"
        />
      </div>

      <!-- Slide-Over / Drawer Panel -->
      <Teleport to="body">
        <div v-if="isDrawerOpen" class="fixed inset-0 z-50 overflow-hidden">
          <!-- Backdrop -->
          <div 
            class="absolute inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" 
            @click="isDrawerOpen = false"
          ></div>

          <div class="fixed inset-y-0 right-0 pl-10 max-w-full flex">
            <div class="w-screen max-w-md bg-white shadow-2xl flex flex-col">
              
              <!-- Drawer Header -->
              <div class="px-6 py-5 bg-slate-900 text-white flex items-center justify-between">
                <div>
                  <h3 class="text-base font-extrabold flex items-center">
                    <ShieldCheck class="w-5 h-5 mr-2 text-blue-400" />
                    {{ editingUser ? 'Modificar Usuario' : 'Nuevo Usuario' }}
                  </h3>
                  <p class="text-xs text-slate-400 mt-0.5">
                    {{ editingUser ? 'Actualiza los datos y matriz de privilegios' : 'Crea una cuenta con credenciales y rol' }}
                  </p>
                </div>
                <button 
                  @click="isDrawerOpen = false" 
                  class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition cursor-pointer"
                >
                  <X class="w-5 h-5" />
                </button>
              </div>

              <!-- Drawer Form Body -->
              <form @submit.prevent="submitForm" class="flex-1 overflow-y-auto p-6 space-y-5">
                
                <!-- Nombre Completo -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Nombre Completo <span class="text-red-500">*</span>
                  </label>
                  <input 
                    v-model="form.name" 
                    type="text" 
                    placeholder="Ej. Juan Pérez Ramos" 
                    required 
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 font-semibold focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition"
                  />
                  <span v-if="form.errors.name" class="text-[11px] text-red-500 font-bold mt-1 block">{{ form.errors.name }}</span>
                </div>

                <!-- Correo Electrónico -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Correo Electrónico <span class="text-red-500">*</span>
                  </label>
                  <input 
                    v-model="form.email" 
                    type="email" 
                    placeholder="usuario@empresa.com" 
                    required 
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 font-semibold focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition"
                  />
                  <span v-if="form.errors.email" class="text-[11px] text-red-500 font-bold mt-1 block">{{ form.errors.email }}</span>
                </div>

                <!-- Contraseña -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    {{ editingUser ? 'Nueva Contraseña (Opcional)' : 'Contraseña de Acceso *' }}
                  </label>
                  <div class="relative">
                    <input 
                      v-model="form.password" 
                      :type="showPassword ? 'text' : 'password'" 
                      :placeholder="editingUser ? 'Dejar en blanco para no cambiar' : 'Mínimo 6 caracteres'" 
                      :required="!editingUser"
                      class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-3.5 pr-10 py-2.5 text-xs text-slate-900 font-semibold focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition"
                    />
                    <button 
                      type="button" 
                      @click="showPassword = !showPassword" 
                      class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 cursor-pointer"
                    >
                      <EyeOff v-if="showPassword" class="w-4 h-4" />
                      <Eye v-else class="w-4 h-4" />
                    </button>
                  </div>
                  <span v-if="form.errors.password" class="text-[11px] text-red-500 font-bold mt-1 block">{{ form.errors.password }}</span>
                </div>

                <!-- Selector de Rol Principal -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Rol / Nivel de Acceso <span class="text-red-500">*</span>
                  </label>
                  <select 
                    v-model="form.rol" 
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 font-bold focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition cursor-pointer"
                  >
                    <option value="OPERADOR">Operador (Permisos Personalizables)</option>
                    <option value="LECTOR">Lector (Solo Lectura Global)</option>
                    <option value="ADMIN">Super Administrador (Control Total)</option>
                  </select>
                </div>

                <!-- Tarjeta Informativa para ADMIN -->
                <div v-if="form.rol === 'ADMIN'" class="p-4 bg-purple-50/80 border border-purple-200/80 rounded-2xl flex items-start space-x-3.5">
                  <div class="p-2 bg-purple-100 text-purple-700 rounded-xl shrink-0">
                    <ShieldCheck class="w-5 h-5" />
                  </div>
                  <div>
                    <h5 class="text-xs font-extrabold text-purple-900">Modo Super Administrador Total</h5>
                    <p class="text-xs text-purple-700 mt-0.5 leading-relaxed">
                      El usuario <strong>ADMIN</strong> tiene permisos automáticos de <strong>Escritura Total</strong> en todos los módulos del sistema, incluyendo la gestión de otros usuarios, roles y auditoría.
                    </p>
                  </div>
                </div>

                <!-- Tarjeta Informativa para LECTOR -->
                <div v-else-if="form.rol === 'LECTOR'" class="p-4 bg-amber-50/80 border border-amber-200/80 rounded-2xl flex items-start space-x-3.5">
                  <div class="p-2 bg-amber-100 text-amber-700 rounded-xl shrink-0">
                    <Lock class="w-5 h-5" />
                  </div>
                  <div>
                    <h5 class="text-xs font-extrabold text-amber-900">Modo Solo Lectura Global</h5>
                    <p class="text-xs text-amber-700 mt-0.5 leading-relaxed">
                      El rol <strong>LECTOR</strong> tiene permisos fijos de <strong>Solo Lectura</strong> en todos los módulos. Solo puede consultar datos, aplicar filtros y exportar PDFs; no puede registrar ni modificar información.
                    </p>
                  </div>
                </div>

                <!-- Matriz de Permisos Personalizables ÚNICAMENTE para OPERADOR -->
                <div v-else-if="form.rol === 'OPERADOR'" class="space-y-3 pt-2 border-t border-slate-100">
                  <div class="flex items-center justify-between">
                    <div>
                      <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider flex items-center space-x-1.5">
                        <Sliders class="w-4 h-4 text-blue-600" />
                        <span>Permisos por Módulo</span>
                      </h4>
                      <span class="text-[11px] text-slate-500">Personaliza la autorización para este Operador:</span>
                    </div>
                  </div>

                  <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80 space-y-3">
                    
                    <div class="flex items-center justify-between">
                      <div>
                        <span class="text-xs font-bold text-slate-800 block">Módulo Empresas</span>
                        <span class="text-[10px] text-slate-400">RUC y Razón Social</span>
                      </div>
                      <select v-model="form.permisos.empresas" class="text-xs font-bold bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 focus:ring-1 focus:ring-blue-500 outline-none">
                        <option value="ESCRITURA">Escritura (Crear / Editar)</option>
                        <option value="LECTURA">Lectura (Solo ver)</option>
                      </select>
                    </div>

                    <div class="flex items-center justify-between">
                      <div>
                        <span class="text-xs font-bold text-slate-800 block">Módulo Trabajadores</span>
                        <span class="text-[10px] text-slate-400">Padrón de personal</span>
                      </div>
                      <select v-model="form.permisos.trabajadores" class="text-xs font-bold bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 focus:ring-1 focus:ring-blue-500 outline-none">
                        <option value="ESCRITURA">Escritura (Crear / Editar)</option>
                        <option value="LECTURA">Lectura (Solo ver)</option>
                      </select>
                    </div>

                    <div class="flex items-center justify-between">
                      <div>
                        <span class="text-xs font-bold text-slate-800 block">Módulo Rutas</span>
                        <span class="text-[10px] text-slate-400">Tramos y destinos</span>
                      </div>
                      <select v-model="form.permisos.rutas" class="text-xs font-bold bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 focus:ring-1 focus:ring-blue-500 outline-none">
                        <option value="ESCRITURA">Escritura (Crear / Editar)</option>
                        <option value="LECTURA">Lectura (Solo ver)</option>
                      </select>
                    </div>

                    <div class="flex items-center justify-between">
                      <div>
                        <span class="text-xs font-bold text-slate-800 block">Módulo Flota & Choferes</span>
                        <span class="text-[10px] text-slate-400">Buses, SOAT y licencias</span>
                      </div>
                      <select v-model="form.permisos.flota" class="text-xs font-bold bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 focus:ring-1 focus:ring-blue-500 outline-none">
                        <option value="ESCRITURA">Escritura (Crear / Editar)</option>
                        <option value="LECTURA">Lectura (Solo ver)</option>
                      </select>
                    </div>

                    <div class="flex items-center justify-between">
                      <div>
                        <span class="text-xs font-bold text-slate-800 block">Módulo Manifiestos</span>
                        <span class="text-[10px] text-slate-400">Emisión y despacho</span>
                      </div>
                      <select v-model="form.permisos.manifiestos" class="text-xs font-bold bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 focus:ring-1 focus:ring-blue-500 outline-none">
                        <option value="ESCRITURA">Escritura (Crear / Editar)</option>
                        <option value="LECTURA">Lectura (Solo ver)</option>
                      </select>
                    </div>

                  </div>
                </div>

                <!-- Footer Actions -->
                <div class="pt-4 border-t border-[var(--theme-border)] flex justify-end space-x-3">
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
        :message="userToToggle ? '¿Desea ' + (userToToggle.estado == 1 ? 'desactivar' : 'reactivar') + ' la cuenta del usuario ' + userToToggle.name + '?' : ''"
        :confirmText="userToToggle && userToToggle.estado == 1 ? 'Sí, Inhabilitar' : 'Sí, Reactivar'"
        :variant="userToToggle && userToToggle.estado == 1 ? 'danger' : 'success'"
        @close="showConfirmModal = false"
        @confirm="executeToggleEstado"
      />
    </div>
  </AppLayout>
</template>
