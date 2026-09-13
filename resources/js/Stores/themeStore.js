import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useThemeStore = defineStore('theme', () => {
  // Theme mode: 'dark' | 'light'
  const mode = ref(localStorage.getItem('movilizacion_theme_mode') || 'dark');
  
  // Theme palette accent: 'cyan' | 'blue' | 'emerald' | 'indigo' | 'purple' | 'amber' | 'ruby' | 'prime'
  const palette = ref(localStorage.getItem('movilizacion_theme_palette') || 'cyan');

  // Multi-shade palettes (50-950) with 200, 400, 600 swatches for UI preview
  const palettesInfo = [
    { 
      id: 'cyan', 
      name: 'Cian Océano', 
      desc: 'Cyber Telemetría', 
      primary: '#0891b2', 
      secondary: '#06b6d4', 
      swatches: ['#a5f3fc', '#22d3ee', '#0891b2'], 
      chart: { border: '#06b6d4', gradStart: 'rgba(6, 182, 212, 0.85)', gradEnd: 'rgba(8, 145, 178, 0.15)', line: '#38bdf8' } 
    },
    { 
      id: 'blue', 
      name: 'Azul Corporativo', 
      desc: 'Enterprise AWS', 
      primary: '#2563eb', 
      secondary: '#3b82f6', 
      swatches: ['#bfdbfe', '#60a5fa', '#2563eb'], 
      chart: { border: '#3b82f6', gradStart: 'rgba(37, 99, 235, 0.85)', gradEnd: 'rgba(59, 130, 246, 0.15)', line: '#818cf8' } 
    },
    { 
      id: 'emerald', 
      name: 'Esmeralda Ejecutivo', 
      desc: 'FinTech Velocity', 
      primary: '#059669', 
      secondary: '#10b981', 
      swatches: ['#a7f3d0', '#34d399', '#059669'], 
      chart: { border: '#10b981', gradStart: 'rgba(5, 150, 105, 0.85)', gradEnd: 'rgba(16, 185, 129, 0.15)', line: '#34d399' } 
    },
    { 
      id: 'indigo', 
      name: 'Índigo Real', 
      desc: 'Deep Cloudscape', 
      primary: '#4f46e5', 
      secondary: '#6366f1', 
      swatches: ['#c7d2fe', '#818cf8', '#4f46e5'], 
      chart: { border: '#6366f1', gradStart: 'rgba(79, 70, 229, 0.85)', gradEnd: 'rgba(99, 102, 241, 0.15)', line: '#a5b4fc' } 
    },
    { 
      id: 'purple', 
      name: 'Púrpura Cyber', 
      desc: 'Futuristic AI Ops', 
      primary: '#9333ea', 
      secondary: '#a855f7', 
      swatches: ['#e9d5ff', '#c084fc', '#9333ea'], 
      chart: { border: '#a855f7', gradStart: 'rgba(147, 51, 234, 0.85)', gradEnd: 'rgba(168, 85, 247, 0.15)', line: '#d8b4fe' } 
    },
    { 
      id: 'amber', 
      name: 'Ámbar Ejecutivo', 
      desc: 'Industrial Gold', 
      primary: '#d97706', 
      secondary: '#f59e0b', 
      swatches: ['#fde68a', '#fbbf24', '#d97706'], 
      chart: { border: '#f59e0b', gradStart: 'rgba(217, 119, 6, 0.85)', gradEnd: 'rgba(245, 158, 11, 0.15)', line: '#fde68a' } 
    },
    { 
      id: 'ruby', 
      name: 'Rojo Rubí', 
      desc: 'Mission-Critical', 
      primary: '#dc2626', 
      secondary: '#ef4444', 
      swatches: ['#fecaca', '#f87171', '#dc2626'], 
      chart: { border: '#ef4444', gradStart: 'rgba(220, 38, 38, 0.85)', gradEnd: 'rgba(239, 68, 68, 0.15)', line: '#fca5a5' } 
    },
    { 
      id: 'prime', 
      name: 'PRIME Vidrio Glass', 
      desc: 'Aero Translucent', 
      primary: '#0284c7', 
      secondary: '#38bdf8', 
      swatches: ['#bae6fd', '#38bdf8', '#0284c7'], 
      chart: { border: '#38bdf8', gradStart: 'rgba(2, 132, 199, 0.85)', gradEnd: 'rgba(56, 189, 248, 0.2)', line: '#67e8f9' } 
    },
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
