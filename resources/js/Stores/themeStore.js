import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useThemeStore = defineStore('theme', () => {
  // Theme mode: 'dark' | 'light'
  const mode = ref(localStorage.getItem('movilizacion_theme_mode') || 'dark');
  
  // Theme palette accent: 'blue' | 'emerald' | 'indigo' | 'amber'
  const palette = ref(localStorage.getItem('movilizacion_theme_palette') || 'blue');

  const palettesInfo = [
    { id: 'blue', name: 'Azul Corporativo', primary: '#2563eb', secondary: '#3b82f6', bgGradient: 'from-blue-600 to-indigo-600' },
    { id: 'emerald', name: 'Esmeralda Ejecutivo', primary: '#059669', secondary: '#10b981', bgGradient: 'from-emerald-600 to-teal-600' },
    { id: 'indigo', name: 'Índigo Real', primary: '#4f46e5', secondary: '#6366f1', bgGradient: 'from-indigo-600 to-violet-600' },
    { id: 'amber', name: 'Ámbar Ejecutivo', primary: '#d97706', secondary: '#f59e0b', bgGradient: 'from-amber-600 to-orange-600' },
  ];

  const currentPalette = computed(() => {
    return palettesInfo.find(p => p.id === palette.value) || palettesInfo[0];
  });

  const setMode = (newMode) => {
    mode.value = newMode;
    localStorage.setItem('movilizacion_theme_mode', newMode);
    applyTheme();
  };

  const setPalette = (newPalette) => {
    palette.value = newPalette;
    localStorage.setItem('movilizacion_theme_palette', newPalette);
    applyTheme();
  };

  const toggleMode = () => {
    setMode(mode.value === 'dark' ? 'light' : 'dark');
  };

  const applyTheme = () => {
    if (typeof document === 'undefined') return;
    const root = document.documentElement;
    
    if (mode.value === 'dark') {
      root.classList.add('dark');
      root.classList.remove('light');
    } else {
      root.classList.add('light');
      root.classList.remove('dark');
    }

    root.setAttribute('data-palette', palette.value);
  };

  return {
    mode,
    palette,
    palettesInfo,
    currentPalette,
    setMode,
    setPalette,
    toggleMode,
    applyTheme,
  };
});