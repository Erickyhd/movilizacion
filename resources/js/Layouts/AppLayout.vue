<script setup>
import { ref, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useThemeStore } from '@/Stores/themeStore';
import { 
  LayoutDashboard, Users, Building2, UserCheck, MapPin, Bus, FileText, 
  LogOut, Menu, ChevronDown, User, ChevronRight, X, Bell,
  Sun, Moon, Palette, Check, PanelLeftClose, PanelLeftOpen
} from 'lucide-vue-next';

const page = usePage();
const user = page.props.auth?.user || { name: 'Admin Operaciones', email: 'admin@movilizacion.local' };

// Persist sidebar state in localStorage
const isSidebarOpen = ref(localStorage.getItem('movilizacion_sidebar_open') !== 'false');

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value;
  localStorage.setItem('movilizacion_sidebar_open', isSidebarOpen.value);
};

const showProfileMenu = ref(false);
const showThemePanel = ref(false);

const themeStore = useThemeStore();

onMounted(() => {
  themeStore.applyTheme();
});

const navItems = [
  { name: 'Panel Principal', route: 'dashboard', icon: LayoutDashboard },
  { name: 'Usuarios', route: 'usuarios.index', icon: Users },
  { name: 'Empresas / Áreas', route: 'empresas.index', icon: Building2 },
  { name: 'Trabajadores', route: 'trabajadores.index', icon: UserCheck },
  { name: 'Rutas / Puntos', route: 'rutas.index', icon: MapPin },
  { name: 'Flota / Choferes', route: 'flota.index', icon: Bus },
  { name: 'Manifiestos', route: 'manifiestos.index', icon: FileText },
];

const logout = () => { router.post(route('logout')); };

const getInitials = (name) => {
  if (!name) return 'AD';
  return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
};
</script>

<template>
  <div :class="['app-shell', themeStore.mode, `palette-${themeStore.palette}`]">
    <!-- Ambient glowing background shapes for PRIME Glass Palette -->
    <div v-if="themeStore.palette === 'prime'" class="prime-bg-decor">
      <div class="prime-orb prime-orb-1"></div>
      <div class="prime-orb prime-orb-2"></div>
      <div class="prime-orb prime-orb-3"></div>
    </div>

    <!-- ═══ TOP HEADER ═══ -->
    <header class="top-bar">
      <div class="top-bar-glow"></div>
      <div class="top-bar-inner">
        <div class="top-left">
          <!-- Sidebar Toggle Button -->
          <button @click="toggleSidebar" class="hamburger" :title="isSidebarOpen ? 'Colapsar menú (Modo Compacto)' : 'Expandir menú'">
            <PanelLeftClose v-if="isSidebarOpen" class="w-5 h-5" />
            <PanelLeftOpen v-else class="w-5 h-5 text-blue-400" />
          </button>
          
          <div class="brand">
            <span class="brand-dot"></span>
            <h1 class="brand-title">SERVICIOS GENERALES MAGORI E.I.R.L.</h1>
          </div>
        </div>

        <div class="top-right">
          <!-- Dark / Light Mode Toggle Quick Button -->
          <button 
            @click="themeStore.toggleMode()" 
            class="icon-btn"
            :title="themeStore.mode === 'dark' ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro'"
          >
            <Sun v-if="themeStore.mode === 'dark'" class="w-[18px] h-[18px] text-amber-400" />
            <Moon v-else class="w-[18px] h-[18px] text-indigo-600" />
          </button>

          <!-- Palette Theme Customizer Button -->
          <button 
            @click="showThemePanel = !showThemePanel" 
            class="icon-btn" 
            title="Personalizar paleta de colores"
          >
            <Palette class="w-[18px] h-[18px]" />
          </button>

          <!-- Notification bell -->
          <button class="icon-btn" title="Notificaciones">
            <Bell class="w-[18px] h-[18px]" />
            <span class="notif-dot"></span>
          </button>

          <!-- User Profile Trigger -->
          <div class="profile-area">
            <button @click="showProfileMenu = !showProfileMenu" class="profile-btn">
              <div class="profile-text">
                <span class="profile-role">Administrador</span>
                <span class="profile-name">{{ user.name }}</span>
              </div>
              <div class="avatar-pill">
                <div class="avatar-circle">{{ getInitials(user.name) }}</div>
              </div>
              <ChevronDown :class="['w-3.5 h-3.5 chev-icon', showProfileMenu ? 'rotate-180' : '']" />
            </button>

            <!-- Profile Dropdown -->
            <Transition name="pop">
              <div v-if="showProfileMenu" class="profile-dd">
                <div class="dd-header">
                  <div class="dd-avatar">{{ getInitials(user.name) }}</div>
                  <div class="dd-meta">
                    <span class="dd-name">{{ user.name }}</span>
                    <span class="dd-email">{{ user.email }}</span>
                  </div>
                </div>
                <div class="dd-body">
                  <Link :href="route('usuarios.index')" class="dd-item" @click="showProfileMenu = false">
                    <User class="w-4 h-4 text-blue-400" />
                    <span>Gestionar Perfiles</span>
                  </Link>
                </div>
                <div class="dd-footer">
                  <button @click="logout" class="dd-logout">
                    <LogOut class="w-4 h-4" />
                    <span>Cerrar Sesión</span>
                  </button>
                </div>
              </div>
            </Transition>
          </div>
        </div>
      </div>
    </header>

    <!-- Theme Customizer Panel Drawer -->
    <Transition name="pop">
      <div v-if="showThemePanel" class="theme-panel-overlay" @click.self="showThemePanel = false">
        <div class="theme-panel-card">
          <div class="theme-panel-header">
            <div class="flex items-center space-x-2">
              <Palette class="w-5 h-5 text-blue-400" />
              <h3 class="font-bold text-sm text-slate-100">Personalización de Apariencia</h3>
            </div>
            <button @click="showThemePanel = false" class="close-btn"><X class="w-4 h-4" /></button>
          </div>

          <div class="theme-panel-body">
            <!-- Mode Switcher -->
            <div class="mb-5">
              <label class="section-title">Modo de Visualización</label>
              <div class="grid grid-cols-2 gap-2">
                <button 
                  @click="themeStore.setMode('dark')"
                  :class="['mode-card', themeStore.mode === 'dark' ? 'mode-active' : '']"
                >
                  <Moon class="w-4 h-4 mr-2 text-indigo-400" />
                  <span>Modo Oscuro</span>
                </button>
                <button 
                  @click="themeStore.setMode('light')"
                  :class="['mode-card', themeStore.mode === 'light' ? 'mode-active' : '']"
                >
                  <Sun class="w-4 h-4 mr-2 text-amber-400" />
                  <span>Modo Claro</span>
                </button>
              </div>
            </div>

            <!-- Palettes Picker (8 Curated Palettes including PRIME Glass) -->
            <div>
              <label class="section-title">Paleta Corporativa</label>
              <div class="grid grid-cols-1 gap-2">
                <button 
                  v-for="p in themeStore.palettesInfo" 
                  :key="p.id"
                  @click="themeStore.setPalette(p.id)"
                  :class="['palette-option', themeStore.palette === p.id ? 'palette-selected' : '']"
                >
                  <div class="flex items-center space-x-3">
                    <span class="w-4 h-4 rounded-full shadow-md" :style="{ backgroundColor: p.primary }"></span>
                    <span class="text-xs font-bold text-slate-200">{{ p.name }}</span>
                  </div>
                  <Check v-if="themeStore.palette === p.id" class="w-4 h-4 text-blue-400" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ═══ BODY ═══ -->
    <div class="app-body">
      <!-- SIDEBAR (Dynamic palette icons) -->
      <aside :class="['sidebar', isSidebarOpen ? 'sidebar-expanded' : 'sidebar-compact']">
        <div class="sidebar-glow"></div>

        <div v-if="isSidebarOpen" class="sidebar-label">MENÚ PRINCIPAL</div>

        <nav class="sidebar-nav">
          <Link
            v-for="item in navItems"
            :key="item.route"
            :href="route(item.route)"
            :class="['nav-link', route().current(item.route) ? 'nav-active' : '']"
            :title="!isSidebarOpen ? item.name : ''"
          >
            <div class="nav-icon">
              <component :is="item.icon" class="w-5 h-5" />
            </div>
            <span v-if="isSidebarOpen" class="nav-text">{{ item.name }}</span>
            <ChevronRight v-if="isSidebarOpen && route().current(item.route)" class="w-3.5 h-3.5 ml-auto opacity-50 flex-shrink-0" />
          </Link>
        </nav>

        <div class="sidebar-bottom">
          <div class="sidebar-version" :class="!isSidebarOpen ? 'justify-center' : ''">
            <Bus class="w-4 h-4 flex-shrink-0" />
            <span v-if="isSidebarOpen">Movilización v1.0</span>
          </div>
        </div>
      </aside>

      <!-- MAIN AREA -->
      <main class="main-area" @click="showProfileMenu = false">
        <slot />
      </main>
    </div>
  </div>
</template>

<style scoped>
/* ═══════════ THEME COLOR ENGINE TOKENS (8 PALETTES) ═══════════ */
.app-shell.palette-blue {
  --theme-primary: #2563eb;
  --theme-secondary: #3b82f6;
  --theme-gradient-from: #1e3a8a;
  --theme-accent-glow: rgba(59, 130, 246, 0.4);
}
.app-shell.palette-emerald {
  --theme-primary: #059669;
  --theme-secondary: #10b981;
  --theme-gradient-from: #064e3b;
  --theme-accent-glow: rgba(16, 185, 129, 0.4);
}
.app-shell.palette-indigo {
  --theme-primary: #4f46e5;
  --theme-secondary: #6366f1;
  --theme-gradient-from: #312e81;
  --theme-accent-glow: rgba(99, 102, 241, 0.4);
}
.app-shell.palette-amber {
  --theme-primary: #d97706;
  --theme-secondary: #f59e0b;
  --theme-gradient-from: #78350f;
  --theme-accent-glow: rgba(245, 158, 11, 0.4);
}
.app-shell.palette-purple {
  --theme-primary: #9333ea;
  --theme-secondary: #a855f7;
  --theme-gradient-from: #581c87;
  --theme-accent-glow: rgba(168, 85, 247, 0.4);
}
.app-shell.palette-ruby {
  --theme-primary: #dc2626;
  --theme-secondary: #ef4444;
  --theme-gradient-from: #7f1d1d;
  --theme-accent-glow: rgba(239, 68, 68, 0.4);
}
.app-shell.palette-cyan {
  --theme-primary: #0891b2;
  --theme-secondary: #06b6d4;
  --theme-gradient-from: #164e63;
  --theme-accent-glow: rgba(6, 182, 212, 0.4);
}
.app-shell.palette-prime {
  --theme-primary: #0284c7;
  --theme-secondary: #38bdf8;
  --theme-gradient-from: #07152e;
  --theme-accent-glow: rgba(56, 189, 248, 0.5);
}

/* ═══════════ SHELL BASE ═══════════ */
.app-shell {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
  position: relative;
  overflow: hidden;
  transition: background-color 0.3s ease, color 0.3s ease;
}

.app-shell.dark {
  background: #0f172a;
  color: #f8fafc;
}

.app-shell.light {
  background: #f8fafc;
  color: #0f172a;
}

/* PRIME Glassmorphism Orbs Background */
.prime-bg-decor {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 0;
  overflow: hidden;
}
.prime-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(100px);
  opacity: 0.3;
}
.prime-orb-1 { width: 500px; height: 500px; background: #0284c7; top: -100px; left: -100px; }
.prime-orb-2 { width: 450px; height: 450px; background: #6366f1; bottom: -100px; right: -100px; }
.prime-orb-3 { width: 350px; height: 350px; background: #06b6d4; top: 40%; left: 50%; }

/* ═══════════ TOP BAR ═══════════ */
.top-bar {
  height: 58px;
  position: sticky;
  top: 0;
  z-index: 40;
  transition: all 0.3s ease;
  position: relative;
}

.app-shell.dark .top-bar {
  background: linear-gradient(135deg, var(--theme-gradient-from) 0%, #0f172a 100%);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
}

.app-shell.light .top-bar {
  background: #ffffff;
  border-bottom: 1px solid #e2e8f0;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

/* PRIME Glassmorphism Palette Top Bar with High Contrast Text */
.app-shell.palette-prime .top-bar {
  background: rgba(15, 23, 42, 0.82) !important;
  backdrop-filter: blur(24px) saturate(180%) !important;
  -webkit-backdrop-filter: blur(24px) saturate(180%) !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.18) !important;
  box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.4) !important;
}

.top-bar-glow {
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent 5%, var(--theme-secondary) 50%, transparent 95%);
  opacity: 0.8;
}
.top-bar-inner {
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 16px;
}

.top-left { display: flex; align-items: center; gap: 12px; }

.hamburger {
  color: #94a3b8;
  padding: 8px;
  border-radius: 10px;
  border: none;
  background: none;
  cursor: pointer;
  transition: all 0.2s;
}
.hamburger:hover { color: #ffffff; background: rgba(255, 255, 255, 0.1); }
.app-shell.palette-prime .hamburger { color: #f8fafc !important; }

.brand { display: flex; align-items: center; gap: 10px; }
.brand-dot {
  width: 9px; height: 9px;
  border-radius: 50%;
  background: var(--theme-secondary);
  box-shadow: 0 0 12px var(--theme-accent-glow);
  animation: pulse-dot 3s ease-in-out infinite;
}
@keyframes pulse-dot {
  0%, 100% { transform: scale(1); opacity: 0.8; }
  50% { transform: scale(1.3); opacity: 1; }
}
.brand-title {
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0.1em;
}

.app-shell.dark .brand-title { color: #ffffff; }
.app-shell.light .brand-title { color: #0f172a; }
.app-shell.palette-prime .brand-title { color: #ffffff !important; font-weight: 800; }

.top-right { display: flex; align-items: center; gap: 8px; }

.icon-btn {
  position: relative;
  padding: 8px;
  color: #94a3b8;
  border: none;
  background: none;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s;
}
.icon-btn:hover { color: #ffffff; background: rgba(255, 255, 255, 0.1); }
.app-shell.palette-prime .icon-btn { color: #f8fafc !important; }

.notif-dot {
  position: absolute;
  top: 6px; right: 6px;
  width: 7px; height: 7px;
  border-radius: 50%;
  background: #ef4444;
  border: 2px solid #0f172a;
}

.profile-area { position: relative; }
.profile-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 5px 8px 5px 12px;
  border-radius: 14px;
  border: 1px solid rgba(255, 255, 255, 0.15);
  background: rgba(255, 255, 255, 0.05);
  cursor: pointer;
  transition: all 0.2s;
}
.profile-btn:hover { background: rgba(255, 255, 255, 0.12); }

.profile-text { display: none; flex-direction: column; text-align: right; }
@media (min-width: 640px) { .profile-text { display: flex; } }

.profile-role { font-size: 0.55rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; color: var(--theme-secondary); }
.profile-name { font-size: 0.78rem; font-weight: 700; }
.app-shell.dark .profile-name { color: #ffffff; }
.app-shell.light .profile-name { color: #0f172a; }
.app-shell.palette-prime .profile-name { color: #ffffff !important; font-weight: 700; }

.avatar-pill { padding: 2px; border-radius: 10px; background: var(--theme-primary); }
.avatar-circle {
  width: 30px; height: 30px;
  border-radius: 8px;
  background: rgba(0,0,0,0.2);
  color: white;
  font-weight: 800;
  font-size: 0.65rem;
  display: flex;
  align-items: center;
  justify-content: center;
}
.chev-icon { color: #94a3b8; transition: transform 0.2s; }
.app-shell.palette-prime .chev-icon { color: #f8fafc !important; }

/* ═══ DROPDOWN ═══ */
.profile-dd {
  position: absolute;
  right: 0;
  top: calc(100% + 10px);
  width: 260px;
  background: #1e293b;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 18px;
  box-shadow: 0 25px 50px rgba(0,0,0,0.5);
  overflow: hidden;
  z-index: 50;
}
.app-shell.palette-prime .profile-dd {
  background: rgba(15, 23, 42, 0.92) !important;
  backdrop-filter: blur(24px) !important;
  border: 1px solid rgba(255, 255, 255, 0.2) !important;
}

.dd-header { display: flex; align-items: center; gap: 12px; padding: 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
.dd-avatar { width: 38px; height: 38px; border-radius: 12px; background: var(--theme-primary); color: white; font-weight: 800; font-size: 0.75rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.dd-meta { overflow: hidden; }
.dd-name { display: block; font-weight: 700; font-size: 0.82rem; color: #f8fafc; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dd-email { display: block; font-size: 0.68rem; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dd-body { padding: 6px; }
.dd-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px; font-size: 0.78rem; font-weight: 600; color: #cbd5e1; text-decoration: none; transition: all 0.2s; }
.dd-item:hover { background: rgba(255, 255, 255, 0.08); color: #ffffff; }
.dd-footer { padding: 6px; border-top: 1px solid rgba(255, 255, 255, 0.1); }
.dd-logout { width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px; font-size: 0.73rem; font-weight: 700; color: #f87171; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 10px; cursor: pointer; transition: all 0.2s; }
.dd-logout:hover { background: #dc2626; color: white; border-color: transparent; }

/* ═══ THEME CUSTOMIZER DRAWER PANEL ═══ */
.theme-panel-overlay {
  position: fixed;
  inset: 0;
  z-index: 60;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  justify-content: flex-end;
}
.theme-panel-card {
  width: 100%;
  max-width: 360px;
  height: 100%;
  background: #1e293b;
  border-left: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: -10px 0 30px rgba(0,0,0,0.5);
  display: flex;
  flex-direction: column;
}
.app-shell.palette-prime .theme-panel-card {
  background: rgba(15, 23, 42, 0.92) !important;
  backdrop-filter: blur(24px) !important;
  border-left: 1px solid rgba(255, 255, 255, 0.2) !important;
}

.theme-panel-header {
  padding: 18px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #0f172a;
}
.close-btn { color: #94a3b8; padding: 4px; border-radius: 8px; border: none; background: none; cursor: pointer; }
.close-btn:hover { color: #ffffff; background: rgba(255, 255, 255, 0.1); }
.theme-panel-body { padding: 20px; flex: 1; overflow-y: auto; }
.section-title { display: block; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em; color: #94a3b8; margin-bottom: 10px; }

.mode-card {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 12px;
  border-radius: 12px;
  border: 2px solid rgba(255, 255, 255, 0.1);
  background: rgba(15, 23, 42, 0.5);
  color: #cbd5e1;
  font-size: 0.78rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}
.mode-card:hover { border-color: rgba(255, 255, 255, 0.2); }
.mode-active { border-color: var(--theme-secondary) !important; background: rgba(59, 130, 246, 0.15) !important; color: #ffffff !important; }

.palette-option {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(15, 23, 42, 0.5);
  cursor: pointer;
  transition: all 0.2s;
}
.palette-option:hover { background: rgba(255, 255, 255, 0.05); }
.palette-selected { border-color: var(--theme-secondary) !important; background: rgba(255, 255, 255, 0.08) !important; }

.pop-enter-active, .pop-leave-active { transition: all 0.2s ease; }
.pop-enter-from, .pop-leave-to { opacity: 0; transform: translateY(-8px) scale(0.96); }

/* ═══════════ BODY ═══════════ */
.app-body { display: flex; flex: 1; overflow: hidden; position: relative; z-index: 1; }

/* ═══════════ SIDEBAR (HIGH CONTRAST & DYNAMIC PALETTE ICONS) ═══════════ */
.sidebar {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  transition: width 0.3s cubic-bezier(0.16, 1, 0.3, 1), background-color 0.3s ease, border-color 0.3s ease;
  z-index: 20;
  overflow-x: hidden;
  overflow-y: auto;
  position: relative;
}

.sidebar-expanded { width: 254px; }
.sidebar-compact { width: 80px; }

/* Sidebar Background Styles per Mode */
.app-shell.dark .sidebar {
  background: #0f172a;
  border-right: 1px solid rgba(255, 255, 255, 0.08);
}
.app-shell.light .sidebar {
  background: #ffffff;
  border-right: 1px solid #e2e8f0;
}
/* PRIME Glassmorphism Palette Sidebar */
.app-shell.palette-prime .sidebar {
  background: rgba(15, 23, 42, 0.82) !important;
  backdrop-filter: blur(24px) saturate(180%) !important;
  -webkit-backdrop-filter: blur(24px) saturate(180%) !important;
  border-right: 1px solid rgba(255, 255, 255, 0.18) !important;
  box-shadow: 4px 0 24px rgba(0, 0, 0, 0.3) !important;
}

.sidebar-glow {
  position: absolute;
  top: 0; right: 0;
  width: 1px; height: 100%;
  background: linear-gradient(180deg, var(--theme-secondary) 0%, transparent 40%);
  opacity: 0.3;
}

.sidebar-label {
  padding: 22px 22px 8px;
  font-size: 0.58rem;
  font-weight: 800;
  letter-spacing: 0.18em;
  color: #94a3b8;
  white-space: nowrap;
}
.app-shell.palette-prime .sidebar-label { color: #38bdf8 !important; font-weight: 800; }

.sidebar-nav { padding: 4px 10px; flex: 1; display: flex; flex-direction: column; gap: 4px; }

.nav-link {
  display: flex;
  align-items: center;
  padding: 10px;
  border-radius: 12px;
  font-size: 0.82rem;
  font-weight: 600;
  text-decoration: none;
  transition: background-color 0.2s ease;
  position: relative;
  overflow: hidden;
}

.sidebar-expanded .nav-link { gap: 12px; justify-content: flex-start; }
.sidebar-compact .nav-link { justify-content: center; padding: 10px 0; width: 100%; }

.app-shell.dark .nav-link { color: #94a3b8; }
.app-shell.dark .nav-link:hover { color: #ffffff; background: rgba(255, 255, 255, 0.08); }

.app-shell.light .nav-link { color: #475569; }
.app-shell.light .nav-link:hover { color: #0f172a; background: #f1f5f9; }

/* PRIME GLASS HIGH CONTRAST NAV LINK TEXT */
.app-shell.palette-prime .nav-link {
  color: #f8fafc !important;
  font-weight: 700 !important;
}
.app-shell.palette-prime .nav-link:hover {
  background: rgba(255, 255, 255, 0.12) !important;
  color: #ffffff !important;
}

.nav-active {
  color: #ffffff !important;
  background: var(--theme-primary) !important;
  box-shadow: 0 4px 14px var(--theme-accent-glow);
}

/* DYNAMIC ACCENT COLOR FOR SIDEBAR ICONS MATCHING ACTIVE PALETTE */
.nav-icon {
  width: 40px; height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.06);
  color: var(--theme-secondary) !important;
  transition: all 0.25s ease;
  flex-shrink: 0;
  margin: 0 auto;
}

.nav-link:hover .nav-icon {
  background: rgba(255, 255, 255, 0.15);
  color: var(--theme-secondary) !important;
  box-shadow: 0 0 12px var(--theme-accent-glow);
}

.nav-active .nav-icon {
  background: rgba(255, 255, 255, 0.25) !important;
  color: #ffffff !important;
  box-shadow: 0 2px 10px var(--theme-accent-glow) !important;
}

.sidebar-expanded .nav-icon { margin: 0; }

.nav-text { white-space: nowrap; }

.sidebar-bottom { padding: 14px 18px; border-top: 1px solid rgba(148, 163, 184, 0.15); margin-top: auto; }
.sidebar-version { display: flex; align-items: center; gap: 8px; font-size: 0.62rem; color: #94a3b8; font-weight: 600; white-space: nowrap; }
.app-shell.palette-prime .sidebar-version { color: #cbd5e1 !important; }

/* ═══════════ MAIN AREA ═══════════ */
.main-area {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
  transition: background-color 0.3s;
}

.app-shell.dark .main-area { background: #0b1329; }
.app-shell.light .main-area { background: #f1f5f9; }

/* PRIME Glassmorphism Main Area */
.app-shell.palette-prime .main-area {
  background: rgba(11, 19, 41, 0.5) !important;
  backdrop-filter: blur(12px) !important;
  -webkit-backdrop-filter: blur(12px) !important;
}

@media (min-width: 640px) { .main-area { padding: 28px 32px; } }
</style>