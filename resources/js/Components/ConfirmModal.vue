<script setup>
import { AlertTriangle, X, ShieldAlert, CheckCircle2 } from 'lucide-vue-next';

defineProps({
  show: Boolean,
  title: {
    type: String,
    default: 'Confirmar Acción'
  },
  message: {
    type: String,
    default: '¿Estás seguro de realizar esta acción?'
  },
  confirmText: {
    type: String,
    default: 'Confirmar'
  },
  cancelText: {
    type: String,
    default: 'Cancelar'
  },
  variant: {
    type: String,
    default: 'danger' // 'danger' | 'warning' | 'success'
  },
  processing: Boolean
});

const emit = defineEmits(['confirm', 'close']);
</script>

<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" @click="emit('close')"></div>

      <!-- Modal Card -->
      <div class="relative bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 z-10 space-y-4 animate-in fade-in zoom-in duration-200">
        
        <div class="flex items-start space-x-4">
          <div :class="[
            'w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-md',
            variant === 'danger' ? 'bg-red-100 text-red-600 border border-red-200' :
            variant === 'warning' ? 'bg-amber-100 text-amber-600 border border-amber-200' :
            'bg-emerald-100 text-emerald-600 border border-emerald-200'
          ]">
            <AlertTriangle v-if="variant === 'danger' || variant === 'warning'" class="w-6 h-6" />
            <CheckCircle2 v-else class="w-6 h-6" />
          </div>

          <div class="flex-1 min-w-0">
            <h3 class="text-base font-extrabold text-slate-900 leading-tight">
              {{ title }}
            </h3>
            <p class="text-xs text-slate-600 mt-1.5 leading-relaxed">
              {{ message }}
            </p>
          </div>

          <button @click="emit('close')" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 cursor-pointer">
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-3 pt-3 border-t border-slate-100">
          <button 
            type="button" 
            @click="emit('close')" 
            class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer"
          >
            {{ cancelText }}
          </button>

          <button 
            type="button" 
            @click="emit('confirm')" 
            :disabled="processing"
            :class="[
              'px-5 py-2 text-xs font-bold text-white rounded-xl shadow-md transition disabled:opacity-50 cursor-pointer',
              variant === 'danger' ? 'bg-red-600 hover:bg-red-500 shadow-red-500/20' :
              variant === 'warning' ? 'bg-amber-600 hover:bg-amber-500 shadow-amber-500/20' :
              'bg-emerald-600 hover:bg-emerald-500 shadow-emerald-500/20'
            ]"
          >
            {{ confirmText }}
          </button>
        </div>

      </div>
    </div>
  </Teleport>
</template>