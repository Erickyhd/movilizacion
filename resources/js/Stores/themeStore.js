import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useThemeStore = defineStore('theme', () => {
  // Theme mode: 'dark' | 'light'
  const mode = ref(localStorage.getItem('movilizacion_theme_mode') || 'dark');
  
  // Theme palette accent: 'blue' | 'emerald' | 'indigo' | 'amber' | 'purple' | 'ruby' | 'cyan' | 'prime'
  const palette = ref(localStorage.getItem('movilizacion_theme_palette') || 'blue');

  const palettesInfo = [
    { id: 'blue', name: 'Azul Corporativo', primary: '#2563eb', secondary: '#3b82f6' },
    { id: 'emerald', name: 'Esmeralda Ejecutivo', primary: '#059669', secondary: '#10b981' },
    { id: 'indigo', name: 'Índigo Real', primary: '#4f46e5', secondary: '#6366f1' },
    { id: 'amber', name: 'Ámbar Ejecutivo', primary: '#d97706', secondary: '#f59e0b' },
    { id: 'purple', name: 'Púrpura Cyber', primary: '#9333ea', secondary: '#a855f7' },
    { id: 'ruby', name: 'Rojo Rubí', primary: '#dc2626', secondary: '#ef4444' },
    { id: 'cyan', name: 'Cian Océano', primary: '#0891b2', secondary: '#06b6d4' },
    { id: 'prime', name: 'PRIME Vidrio (Glass)', primary: '#38bdf8', secondary: '#818cf8' },
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
    
    root.classList.remove('dark', 'light');
    root.classList.add(mode.value);

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