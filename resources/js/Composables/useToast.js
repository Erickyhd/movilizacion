import { ref } from 'vue';

const toasts = ref([]);
let counter = 0;

export function useToast() {
  const addToast = (type, message, title = '') => {
    const id = ++counter;
    const toast = {
      id,
      type, // 'success' | 'warning' | 'error' | 'info'
      title,
      message,
    };

    toasts.value.push(toast);

    setTimeout(() => {
      removeToast(id);
    }, 3500);
  };

  const removeToast = (id) => {
    const index = toasts.value.findIndex(t => t.id === id);
    if (index > -1) {
      toasts.value.splice(index, 1);
    }
  };

  return {
    toasts,
    removeToast,
    success: (msg, title = 'Operación Exitosa') => addToast('success', msg, title),
    warning: (msg, title = 'Atención') => addToast('warning', msg, title),
    error: (msg, title = 'Error') => addToast('error', msg, title),
    info: (msg, title = 'Información') => addToast('info', msg, title),
  };
}