<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Bus, Lock, Mail, ShieldCheck, Eye, EyeOff } from 'lucide-vue-next';

const showPassword = ref(false);

const form = useForm({
  email: 'admin@movilizacion.local',
  password: 'admin1234',
  remember: true,
});

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <div class="min-h-screen bg-slate-900 flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-slate-800 rounded-2xl border border-slate-700 shadow-2xl overflow-hidden">
      <!-- Header banner -->
      <div class="bg-gradient-to-r from-blue-700 to-indigo-800 p-6 text-center text-white relative">
        <div class="w-14 h-14 bg-white/10 rounded-2xl flex items-center justify-center mx-auto mb-3 backdrop-blur-sm border border-white/20">
          <Bus class="w-8 h-8 text-white" />
        </div>
        <h2 class="text-xl font-bold uppercase tracking-wide">Sistema de Traslado</h2>
        <p class="text-xs text-blue-200 mt-1">Monitoreo y Control de Movilización de Personal</p>
      </div>

      <!-- Login Form -->
      <form @submit.prevent="submit" class="p-6 space-y-5">
        <div v-if="form.errors.email" class="bg-red-500/10 border border-red-500/50 text-red-300 text-sm p-3 rounded-lg flex items-center space-x-2">
          <span>{{ form.errors.email }}</span>
        </div>

        <!-- Email -->
        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Correo Electrónico</label>
          <div class="relative">
            <Mail class="w-5 h-5 text-slate-400 absolute left-3 top-3" />
            <input 
              v-model="form.email" 
              type="email" 
              required
              class="w-full bg-slate-900 border border-slate-700 rounded-lg pl-10 pr-4 py-2.5 text-white placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm outline-none transition"
              placeholder="correo@empresa.com"
            />
          </div>
        </div>

        <!-- Password with Eye Toggle -->
        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Contraseña</label>
          <div class="relative">
            <Lock class="w-5 h-5 text-slate-400 absolute left-3 top-3" />
            <input 
              v-model="form.password" 
              :type="showPassword ? 'text' : 'password'" 
              required
              class="w-full bg-slate-900 border border-slate-700 rounded-lg pl-10 pr-12 py-2.5 text-white placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm outline-none transition"
              placeholder="••••••••"
            />
            <button 
              type="button" 
              @click="showPassword = !showPassword" 
              class="absolute right-3 top-2.5 p-1 text-slate-400 hover:text-white rounded-md transition"
              title="Mostrar/Ocultar Contraseña"
            >
              <component :is="showPassword ? EyeOff : Eye" class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between">
          <label class="flex items-center text-sm text-slate-400 cursor-pointer">
            <input v-model="form.remember" type="checkbox" class="w-4 h-4 rounded bg-slate-900 border-slate-700 text-blue-600 focus:ring-blue-500" />
            <span class="ml-2">Recordar sesión</span>
          </label>
        </div>

        <!-- Submit Button -->
        <button 
          type="submit" 
          :disabled="form.processing"
          class="w-full bg-blue-600 hover:bg-blue-500 text-white font-semibold py-3 rounded-lg shadow-lg hover:shadow-blue-500/25 transition duration-200 flex items-center justify-center space-x-2 disabled:opacity-50"
        >
          <span>Iniciar Sesión</span>
        </button>

        <!-- Demo credentials info card -->
        <div class="mt-6 bg-slate-900/60 rounded-xl p-3.5 border border-slate-700/50 flex items-start space-x-3">
          <ShieldCheck class="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" />
          <div class="text-xs text-slate-400 leading-relaxed">
            <span class="text-slate-200 font-semibold block">Acceso por Defecto:</span>
            Email: <code class="text-blue-400 bg-slate-800 px-1 py-0.5 rounded">admin@movilizacion.local</code><br>
            Password: <code class="text-blue-400 bg-slate-800 px-1 py-0.5 rounded">admin1234</code>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>