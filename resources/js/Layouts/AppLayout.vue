<script setup>
import { ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { 
  LayoutDashboard, Users, Building2, UserCheck, MapPin, Bus, FileText, 
  LogOut, Menu, ChevronDown, User, ChevronRight, X, Bell
} from 'lucide-vue-next';

const page = usePage();
const user = page.props.auth?.user || { name: 'Admin Operaciones', email: 'admin@movilizacion.local' };
const isSidebarOpen = ref(true);
const showProfileMenu = ref(false);

const navItems = [
  { name: 'Panel Principal', route: 'dashboard', icon: LayoutDashboard, color: 'from-blue-500 to-cyan-500' },
  { name: 'Usuarios', route: 'usuarios.index', icon: Users, color: 'from-violet-500 to-purple-500' },
  { name: 'Empresas / Áreas', route: 'empresas.index', icon: Building2, color: 'from-amber-500 to-orange-500' },
  { name: 'Trabajadores', route: 'trabajadores.index', icon: UserCheck, color: 'from-emerald-500 to-green-500' },
  { name: 'Rutas / Puntos', route: 'rutas.index', icon: MapPin, color: 'from-rose-500 to-pink-500' },
  { name: 'Flota / Choferes', route: 'flota.index', icon: Bus, color: 'from-sky-500 to-blue-500' },
  { name: 'Manifiestos', route: 'manifiestos.index', icon: FileText, color: 'from-teal-500 to-cyan-500' },
];

const logout = () => { router.post(route('logout')); };

const getInitials = (name) => {
  if (!name) return 'AD';
  return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
};
</script>

<template>
  <div class="app-shell">
    <!-- ═══ TOP HEADER ═══ -->
    <header class="top-bar">
      <div class="top-bar-glow"></div>
      <div class="top-bar-inner">
        <div class="top-left">
          <button @click="isSidebarOpen = !isSidebarOpen" class="hamburger">
            <Menu class="w-5 h-5" />
          </button>
          <div class="brand">
            <span class="brand-dot"></span>
            <h1 class="brand-title">SERVICIOS GENERALES MAGORI E.I.R.L.</h1>
          </div>
        </div>

        <div class="top-right">
          <!-- Notification bell -->
          <button class="icon-btn">
            <Bell class="w-[18px] h-[18px]" />
            <span class="notif-dot"></span>
          </button>

          <!-- Profile -->
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

            <!-- Dropdown -->
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

    <!-- ═══ BODY ═══ -->
    <div class="app-body">
      <!-- SIDEBAR -->
      <aside :class="['sidebar', isSidebarOpen ? 'open' : 'closed']">
        <!-- Sidebar glow accent -->
        <div class="sidebar-glow"></div>

        <div class="sidebar-label">MENÚ PRINCIPAL</div>
        <nav class="sidebar-nav">
          <Link
            v-for="item in navItems"
            :key="item.route"
            :href="route(item.route)"
            :class="['nav-link', route().current(item.route) ? 'nav-active' : '']"
          >
            <div :class="['nav-icon', route().current(item.route) ? 'bg-gradient-to-br ' + item.color + ' text-white shadow-lg' : '']">
              <component :is="item.icon" class="w-[18px] h-[18px]" />
            </div>
            <span class="nav-text">{{ item.name }}</span>
            <ChevronRight v-if="route().current(item.route)" class="w-3.5 h-3.5 ml-auto opacity-50" />
          </Link>
        </nav>

        <div class="sidebar-bottom">
          <div class="sidebar-version">
            <Bus class="w-4 h-4" />
            <span>Movilización v1.0</span>
          </div>
        </div>
      </aside>

      <!-- MAIN -->
      <main class="main-area" @click="showProfileMenu = false">
        <slot />
      </main>
    </div>
  </div>
</template>

<style scoped>
/* ═══════════ SHELL ═══════════ */
.app-shell {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f0f4f8;
  font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
}

/* ═══════════ TOP BAR ═══════════ */
.top-bar {
  height: 58px;
  position: sticky;
  top: 0;
  z-index: 40;
  background: linear-gradient(135deg, #0c1222 0%, #162032 50%, #0f1729 100%);
  border-bottom: 1px solid rgba(59, 130, 246, 0.1);
  box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
  position: relative;
}
.top-bar-glow {
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent 5%, #3b82f6 25%, #8b5cf6 50%, #06b6d4 75%, transparent 95%);
  opacity: 0.6;
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
  color: #64748b;
  padding: 8px;
  border-radius: 10px;
  border: none;
  background: none;
  cursor: pointer;
  transition: all 0.2s;
}
.hamburger:hover { color: #e2e8f0; background: rgba(71, 85, 105, 0.25); }

.brand { display: flex; align-items: center; gap: 10px; }
.brand-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #8b5cf6);
  box-shadow: 0 0 10px rgba(59, 130, 246, 0.5);
  animation: pulse-dot 3s ease-in-out infinite;
}
@keyframes pulse-dot {
  0%, 100% { box-shadow: 0 0 10px rgba(59, 130, 246, 0.5); }
  50% { box-shadow: 0 0 20px rgba(59, 130, 246, 0.8), 0 0 40px rgba(139, 92, 246, 0.3); }
}
.brand-title {
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  background: linear-gradient(135deg, #f1f5f9, #94a3b8);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.top-right { display: flex; align-items: center; gap: 8px; }

.icon-btn {
  position: relative;
  padding: 8px;
  color: #64748b;
  border: none;
  background: none;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s;
}
.icon-btn:hover { color: #e2e8f0; background: rgba(71, 85, 105, 0.25); }
.notif-dot {
  position: absolute;
  top: 6px; right: 6px;
  width: 7px; height: 7px;
  border-radius: 50%;
  background: #ef4444;
  border: 2px solid #0c1222;
}

.profile-area { position: relative; }
.profile-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 5px 8px 5px 12px;
  border-radius: 14px;
  border: 1px solid rgba(71, 85, 105, 0.2);
  background: rgba(30, 41, 59, 0.4);
  cursor: pointer;
  transition: all 0.2s;
}
.profile-btn:hover { background: rgba(51, 65, 85, 0.35); border-color: rgba(71, 85, 105, 0.35); }

.profile-text { display: none; flex-direction: column; text-align: right; }
@media (min-width: 640px) { .profile-text { display: flex; } }

.profile-role { font-size: 0.55rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; color: #60a5fa; }
.profile-name { font-size: 0.78rem; font-weight: 700; color: #e2e8f0; }

.avatar-pill { padding: 2px; border-radius: 10px; background: linear-gradient(135deg, #2563eb, #7c3aed); }
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
.chev-icon { color: #475569; transition: transform 0.2s; }

/* ═══ DROPDOWN ═══ */
.profile-dd {
  position: absolute;
  right: 0;
  top: calc(100% + 10px);
  width: 260px;
  background: linear-gradient(180deg, #1a2744, #0f172a);
  border: 1px solid rgba(71, 85, 105, 0.4);
  border-radius: 18px;
  box-shadow: 0 25px 50px rgba(0,0,0,0.5), 0 0 40px rgba(59, 130, 246, 0.06);
  overflow: hidden;
  z-index: 50;
}
.dd-header { display: flex; align-items: center; gap: 12px; padding: 16px; border-bottom: 1px solid rgba(71, 85, 105, 0.25); }
.dd-avatar { width: 38px; height: 38px; border-radius: 12px; background: linear-gradient(135deg, #2563eb, #7c3aed); color: white; font-weight: 800; font-size: 0.75rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.dd-meta { overflow: hidden; }
.dd-name { display: block; font-weight: 700; font-size: 0.82rem; color: #f1f5f9; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dd-email { display: block; font-size: 0.68rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dd-body { padding: 6px; }
.dd-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px; font-size: 0.78rem; font-weight: 600; color: #94a3b8; text-decoration: none; transition: all 0.2s; }
.dd-item:hover { background: rgba(71, 85, 105, 0.25); color: #f1f5f9; }
.dd-footer { padding: 6px; border-top: 1px solid rgba(71, 85, 105, 0.25); }
.dd-logout { width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px; font-size: 0.73rem; font-weight: 700; color: #f87171; background: rgba(239, 68, 68, 0.06); border: 1px solid rgba(239, 68, 68, 0.12); border-radius: 10px; cursor: pointer; transition: all 0.2s; }
.dd-logout:hover { background: #dc2626; color: white; border-color: transparent; }

.pop-enter-active, .pop-leave-active { transition: all 0.2s ease; }
.pop-enter-from, .pop-leave-to { opacity: 0; transform: translateY(-8px) scale(0.96); }

/* ═══════════ BODY ═══════════ */
.app-body { display: flex; flex: 1; overflow: hidden; }

/* ═══════════ SIDEBAR ═══════════ */
.sidebar {
  width: 254px;
  flex-shrink: 0;
  background: linear-gradient(180deg, #0c1222 0%, #111827 60%, #0f172a 100%);
  border-right: 1px solid rgba(59, 130, 246, 0.06);
  display: flex;
  flex-direction: column;
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 20;
  overflow-y: auto;
  position: relative;
}
.open { transform: translateX(0); }
.closed { transform: translateX(-100%); position: absolute; height: 100%; }

.sidebar-glow {
  position: absolute;
  top: 0; right: 0;
  width: 1px; height: 100%;
  background: linear-gradient(180deg, rgba(59, 130, 246, 0.15) 0%, transparent 30%, transparent 70%, rgba(139, 92, 246, 0.1) 100%);
}

.sidebar-label {
  padding: 22px 22px 8px;
  font-size: 0.58rem;
  font-weight: 800;
  letter-spacing: 0.18em;
  color: rgba(100, 116, 139, 0.4);
}

.sidebar-nav { padding: 4px 10px; flex: 1; display: flex; flex-direction: column; gap: 2px; }

.nav-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 9px 12px;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 600;
  color: #64748b;
  text-decoration: none;
  transition: all 0.2s ease;
  position: relative;
}
.nav-link:hover { color: #cbd5e1; background: rgba(51, 65, 85, 0.2); }

.nav-active {
  color: #f1f5f9 !important;
  background: rgba(59, 130, 246, 0.08) !important;
}
.nav-active::before {
  content: '';
  position: absolute;
  left: 0; top: 50%;
  transform: translateY(-50%);
  width: 3px; height: 55%;
  background: linear-gradient(180deg, #3b82f6, #8b5cf6);
  border-radius: 0 4px 4px 0;
}

.nav-icon {
  width: 34px; height: 34px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(51, 65, 85, 0.15);
  color: inherit;
  transition: all 0.2s;
  flex-shrink: 0;
}
.nav-link:hover .nav-icon { background: rgba(71, 85, 105, 0.25); color: #94a3b8; }

.nav-text { white-space: nowrap; }

.sidebar-bottom { padding: 14px 18px; border-top: 1px solid rgba(51, 65, 85, 0.15); margin-top: auto; }
.sidebar-version { display: flex; align-items: center; gap: 8px; font-size: 0.62rem; color: rgba(100, 116, 139, 0.35); font-weight: 600; }

/* ═══════════ MAIN ═══════════ */
.main-area {
  flex: 1;
  overflow-y: auto;
  background: linear-gradient(180deg, #f0f4f8 0%, #e8edf3 100%);
  padding: 24px;
}
@media (min-width: 640px) { .main-area { padding: 28px 32px; } }
</style>