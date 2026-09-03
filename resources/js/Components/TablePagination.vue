<script setup>
import { computed } from 'vue';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-vue-next';

const props = defineProps({
  totalItems: {
    type: Number,
    required: true,
  },
  currentPage: {
    type: Number,
    required: true,
  },
  perPage: {
    type: Number,
    required: true,
  },
  perPageOptions: {
    type: Array,
    default: () => [10, 15, 25, 50, 100],
  },
});

const emit = defineEmits(['update:currentPage', 'update:perPage']);

const totalPages = computed(() => {
  if (props.perPage >= 9999) return 1;
  return Math.ceil(props.totalItems / props.perPage) || 1;
});

const fromIndex = computed(() => {
  if (props.totalItems === 0) return 0;
  return (props.currentPage - 1) * props.perPage + 1;
});

const toIndex = computed(() => {
  if (props.perPage >= 9999) return props.totalItems;
  return Math.min(props.currentPage * props.perPage, props.totalItems);
});

const visiblePages = computed(() => {
  const current = props.currentPage;
  const total = totalPages.value;
  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1);
  }

  const pages = [];
  if (current <= 4) {
    pages.push(1, 2, 3, 4, 5, '...', total);
  } else if (current >= total - 3) {
    pages.push(1, '...', total - 4, total - 3, total - 2, total - 1, total);
  } else {
    pages.push(1, '...', current - 1, current, current + 1, '...', total);
  }
  return pages;
});

const setPage = (page) => {
  if (typeof page !== 'number') return;
  if (page >= 1 && page <= totalPages.value && page !== props.currentPage) {
    emit('update:currentPage', page);
  }
};

const changePerPage = (event) => {
  const val = Number(event.target.value);
  emit('update:perPage', val);
  emit('update:currentPage', 1);
};
</script>

<template>
  <div v-if="totalItems > 0" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-6 py-3.5 border-t border-slate-200/80 bg-slate-50/70 text-xs select-none">
    
    <!-- Left: Info & Per Page Selector -->
    <div class="flex items-center space-x-3 text-slate-500 font-medium">
      <span>
        Mostrando <strong class="font-extrabold text-slate-900">{{ fromIndex }}</strong> a <strong class="font-extrabold text-slate-900">{{ toIndex }}</strong> de <strong class="font-extrabold text-slate-900">{{ totalItems }}</strong> registros
      </span>

      <div class="flex items-center space-x-1.5 pl-2 border-l border-slate-200">
        <label class="text-slate-400 font-bold text-[11px]">Filas:</label>
        <select 
          :value="perPage" 
          @change="changePerPage"
          class="bg-white border border-slate-300 rounded-lg px-2 py-1 text-xs font-extrabold text-slate-800 focus:ring-2 focus:ring-blue-500 outline-none cursor-pointer shadow-xs"
        >
          <option v-for="opt in perPageOptions" :key="opt" :value="opt">{{ opt }}</option>
          <option :value="99999">Todos</option>
        </select>
      </div>
    </div>

    <!-- Right: Page Controls -->
    <div v-if="totalPages > 1" class="flex items-center space-x-1">
      <button 
        type="button"
        @click="setPage(1)" 
        :disabled="currentPage === 1"
        title="Primera página"
        class="p-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 hover:text-blue-600 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer transition shadow-xs"
      >
        <ChevronsLeft class="w-3.5 h-3.5" />
      </button>

      <button 
        type="button"
        @click="setPage(currentPage - 1)" 
        :disabled="currentPage === 1"
        title="Página anterior"
        class="p-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 hover:text-blue-600 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer transition shadow-xs mr-1"
      >
        <ChevronLeft class="w-3.5 h-3.5" />
      </button>

      <template v-for="(p, idx) in visiblePages" :key="idx">
        <span v-if="p === '...'" class="px-1.5 text-slate-400 font-bold">...</span>
        <button 
          v-else
          type="button"
          @click="setPage(p)"
          :class="[
            'min-w-[28px] h-7 px-2 rounded-lg text-xs font-extrabold transition cursor-pointer shadow-xs',
            p === currentPage ? 'bg-blue-600 text-white shadow-blue-500/20' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 hover:text-blue-600'
          ]"
        >
          {{ p }}
        </button>
      </template>

      <button 
        type="button"
        @click="setPage(currentPage + 1)" 
        :disabled="currentPage >= totalPages"
        title="Página siguiente"
        class="p-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 hover:text-blue-600 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer transition shadow-xs ml-1"
      >
        <ChevronRight class="w-3.5 h-3.5" />
      </button>

      <button 
        type="button"
        @click="setPage(totalPages)" 
        :disabled="currentPage >= totalPages"
        title="Última página"
        class="p-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 hover:text-blue-600 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer transition shadow-xs"
      >
        <ChevronsRight class="w-3.5 h-3.5" />
      </button>
    </div>

  </div>
</template>
