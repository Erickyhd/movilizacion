<script setup>
import { useToast } from '@/Composables/useToast';
import { CheckCircle2, AlertTriangle, AlertCircle, Info, X } from 'lucide-vue-next';

const { toasts, removeToast } = useToast();
</script>

<template>
  <Teleport to="body">
    <div class="fixed top-5 right-5 z-[20000] flex flex-col space-y-2.5 max-w-sm w-full pointer-events-none px-4 sm:px-0">
      <TransitionGroup 
        enter-active-class="transform transition duration-300 ease-out"
        enter-from-class="translate-x-full opacity-0 scale-95"
        enter-to-class="translate-x-0 opacity-100 scale-100"
        leave-active-class="transform transition duration-200 ease-in"
        leave-from-class="translate-x-0 opacity-100 scale-100"
        leave-to-class="translate-x-full opacity-0 scale-95"
      >
        <div 
          v-for="t in toasts" 
          :key="t.id"
          :class="[
            'pointer-events-auto p-3.5 rounded-2xl shadow-xl border backdrop-blur-md flex items-start space-x-3 transition-all',
            t.type === 'success' ? 'bg-slate-900/95 text-white border-emerald-500/40 shadow-emerald-950/20' :
            t.type === 'warning' ? 'bg-slate-900/95 text-white border-amber-500/40 shadow-amber-950/20' :
            t.type === 'error' ? 'bg-slate-900/95 text-white border-red-500/40 shadow-red-950/20' :
            'bg-slate-900/95 text-white border-blue-500/40 shadow-blue-950/20'
          ]"
        >
          <!-- Icon -->
          <div :class="[
            'w-7 h-7 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5',
            t.type === 'success' ? 'bg-emerald-500/20 text-emerald-400' :
            t.type === 'warning' ? 'bg-amber-500/20 text-amber-400' :
            t.type === 'error' ? 'bg-red-500/20 text-red-400' :
            'bg-blue-500/20 text-blue-400'
          ]">
            <CheckCircle2 v-if="t.type === 'success'" class="w-4 h-4" />
            <AlertTriangle v-else-if="t.type === 'warning'" class="w-4 h-4" />
            <AlertCircle v-else-if="t.type === 'error'" class="w-4 h-4" />
            <Info v-else class="w-4 h-4" />
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <h5 v-if="t.title" class="text-xs font-extrabold text-slate-100 leading-tight">
              {{ t.title }}
            </h5>
            <p class="text-[11px] text-slate-300 mt-0.5 leading-snug font-medium">
              {{ t.message }}
            </p>
          </div>

          <!-- Close -->
          <button 
            @click="removeToast(t.id)" 
            class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition cursor-pointer"
          >
            <X class="w-3.5 h-3.5" />
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>