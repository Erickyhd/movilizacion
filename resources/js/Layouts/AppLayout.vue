<script setup>
import { ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { 
  LayoutDashboard, 
  Users, 
  Building2, 
  UserCheck, 
  MapPin, 
  Bus, 
  FileText, 
  LogOut, 
  ShieldCheck,
  Menu,
  X
} from 'lucide-vue-next';

const page = usePage();
const user = page.props.auth?.user || { name: 'Admin Operaciones', email: 'admin@movilizacion.local' };
const isSidebarOpen = ref(true);

const navItems = [
  { name: 'Panel Principal', route: 'dashboard', icon: LayoutDashboard },
  { name: 'Usuarios', route: 'usuarios.index', icon: Users },
  { name: 'Empresas / Áreas', route: 'empresas.index', icon: Building2 },
  { name: 'Trabajadores', route: 'trabajadores.index', icon: UserCheck },
  { name: 'Rutas / Puntos', route: 'rutas.index', icon: MapPin },
  { name: 'Flota / Choferes', route: 'flota.index', icon: Bus },
  { name: 'Manifiestos', route: 'manifiestos.index', icon: FileText },
];

const logout = () => {
  router.post(route('logout'));
};

const getInitials = (name) => {
  if (!name) return 'AD';
  return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
};
</script>

<template>
  <div class="min-h-screen bg-slate-100 flex flex-col font-sans">
    <!-- Top Header -->
    <header class="bg-slate-900 text-white h-16 px-4 sm:px-6 flex items-center justify-between border-b border-slate-800 shadow-md sticky top-0 z-30">
      <div class="flex items-center space-x-4">
        <button @click="isSidebarOpen = !isSidebarOpen" class="text-slate-400 hover:text-white p-1 rounded-md focus:outline-none">
          <Menu class="w-6 h-6" />
        </button>
        <div class="flex items-center space-x-3">
          <div class="bg-blue-600 p-2 rounded-lg text-white shadow-inner">
            <Bus class="w-5 h-5" />
          </div>
          <h1 class="font-bold text-sm sm:text-base tracking-wide uppercase text-slate-100">
            SISTEMA DE TRASLADO DE PERSONAL
          </h1>
        </div>
      </div>

      <div class="flex items-center space-x-4">
        <div class="hidden sm:flex flex-col text-right">
          <span class="text-xs text-slate-400 font-medium">Usuario Activo</span>
          <span class="text-sm font-semibold text-slate-200">{{ user.name }}</span>
        </div>
        <div class="w-9 h-9 rounded-full bg-blue-700 text-white font-bold flex items-center justify-center text-xs shadow border border-blue-500">
          {{ getInitials(user.name) }}
        </div>
        <button 
          @click="logout" 
          title="Cerrar Sesión"
          class="p-2 text-slate-400 hover:text-red-400 hover:bg-slate-800 rounded-lg transition-colors"
        >
          <LogOut class="w-5 h-5" />
        </button>
      </div>
    </header>

    <div class="flex flex-1 overflow-hidden">
      <!-- Sidebar Navigation -->
      <aside 
        :class="[
          'bg-slate-900 text-slate-300 w-64 flex-shrink-0 border-r border-slate-800 transition-all duration-300 z-20',
          isSidebarOpen ? 'translate-x-0' : '-translate-x-full absolute h-full'
        ]"
      >
        <div class="p-4 uppercase text-xs font-semibold text-slate-500 tracking-wider">
          NAVEGACIÓN
        </div>
        <nav class="px-2 space-y-1">
          <Link
            v-for="item in navItems"
            :key="item.route"
            :href="route(item.route)"
            :class="[
              'flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors group',
              route().current(item.route) 
                ? 'bg-blue-600/90 text-white shadow-sm' 
                : 'text-slate-300 hover:bg-slate-800/80 hover:text-white'
            ]"
          >
            <component 
              :is="item.icon" 
              :class="[
                'w-5 h-5 mr-3 transition-colors',
                route().current(item.route) ? 'text-white' : 'text-slate-400 group-hover:text-white'
              ]" 
            />
            <span>{{ item.name }}</span>
          </Link>
        </nav>
      </aside>

      <!-- Main Content Container -->
      <main class="flex-1 overflow-y-auto bg-slate-100 p-4 sm:p-8">
        <slot />
      </main>
    </div>
  </div>
</template>