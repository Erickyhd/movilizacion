import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useManifiestoStore = defineStore('manifiesto', () => {
  // State
  const activeFilter = ref('TODOS');
  const searchQuery = ref('');
  const isCreateDrawerOpen = ref(false);
  const isDetailModalOpen = ref(false);
  const isAddPassengerModalOpen = ref(false);
  const selectedManifiesto = ref(null);

  // Sound and scanner mode preferences (persisted in localStorage)
  const isSoundEnabled = ref(localStorage.getItem('movilizacion_sound_enabled') !== 'false');
  const isKeypadActive = ref(localStorage.getItem('movilizacion_keypad_active') !== 'false');

  // Actions
  const setFilter = (status) => {
    activeFilter.value = status;
  };

  const setSearch = (query) => {
    searchQuery.value = query;
  };

  const openCreate = () => {
    isCreateDrawerOpen.value = true;
  };

  const closeCreate = () => {
    isCreateDrawerOpen.value = false;
  };

  const openDetail = (manifiesto) => {
    selectedManifiesto.value = manifiesto;
    isDetailModalOpen.value = true;
  };

  const closeDetail = () => {
    isDetailModalOpen.value = false;
    selectedManifiesto.value = null;
  };

  const openAddPassengers = () => {
    isAddPassengerModalOpen.value = true;
  };

  const closeAddPassengers = () => {
    isAddPassengerModalOpen.value = false;
  };

  const toggleSound = () => {
    isSoundEnabled.value = !isSoundEnabled.value;
    localStorage.setItem('movilizacion_sound_enabled', String(isSoundEnabled.value));
  };

  const toggleKeypad = () => {
    isKeypadActive.value = !isKeypadActive.value;
    localStorage.setItem('movilizacion_keypad_active', String(isKeypadActive.value));
  };

  const setSelectedManifiesto = (manifiesto) => {
    selectedManifiesto.value = manifiesto;
  };

  // Helper method for filtering manifests uniformly across views
  const filterList = (manifiestos = []) => {
    if (!Array.isArray(manifiestos)) return [];
    
    return manifiestos.filter(m => {
      // Status filter
      if (activeFilter.value !== 'TODOS' && m.estado !== activeFilter.value) {
        return false;
      }

      // Search query filter
      if (!searchQuery.value || !searchQuery.value.trim()) {
        return true;
      }

      const q = searchQuery.value.trim().toLowerCase();
      const code = (m.codigo_manifiesto || '').toLowerCase();
      const plate = (m.vehiculo?.placa || '').toLowerCase();
      const driver = (m.conductor?.nombres || m.conductor?.trabajador?.nombres || '').toLowerCase();
      const driverLast = (m.conductor?.apellido_paterno || m.conductor?.trabajador?.apellidos || '').toLowerCase();
      const origin = (m.ruta?.origen || '').toLowerCase();
      const dest = (m.ruta?.destino || '').toLowerCase();

      return code.includes(q) || 
             plate.includes(q) || 
             driver.includes(q) || 
             driverLast.includes(q) || 
             origin.includes(q) || 
             dest.includes(q);
    });
  };

  return {
    activeFilter,
    searchQuery,
    isCreateDrawerOpen,
    isDetailModalOpen,
    isAddPassengerModalOpen,
    selectedManifiesto,
    isSoundEnabled,
    isKeypadActive,
    setFilter,
    setSearch,
    openCreate,
    closeCreate,
    openDetail,
    closeDetail,
    openAddPassengers,
    closeAddPassengers,
    toggleSound,
    toggleKeypad,
    setSelectedManifiesto,
    filterList,
  };
});
