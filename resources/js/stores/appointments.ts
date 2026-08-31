import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { appointmentsApi } from '@/api/appointments.api';

export const useAppointmentsStore = defineStore('appointments', () => {
  const appointments = ref<any[]>([]);
  const selectedAppointment = ref<any | null>(null);
  const currentPage = ref(1);
  const lastPage = ref(1);
  const total = ref(0);
  const loading = ref(false);
  const error = ref<string | null>(null);

  const loadAppointments = async (params?: any) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await appointmentsApi.list(params);
      appointments.value = response.data;
      currentPage.value = response.meta.current_page;
      lastPage.value = response.meta.last_page;
      total.value = response.meta.total;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error loading appointments';
    } finally {
      loading.value = false;
    }
  };

  const loadByDate = async (date: string) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await appointmentsApi.getByDate(date);
      appointments.value = response.data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error loading appointments';
    } finally {
      loading.value = false;
    }
  };

  const loadByWeek = async (fromDate: string, toDate: string) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await appointmentsApi.getByWeek(fromDate, toDate);
      appointments.value = response.data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error loading appointments';
    } finally {
      loading.value = false;
    }
  };

  const createAppointment = async (payload: any) => {
    try {
      const response = await appointmentsApi.create(payload);
      appointments.value.unshift(response.data);
      return response;
    } catch (err: any) {
      throw err.response?.data || err;
    }
  };

  const updateAppointment = async (id: number, payload: any) => {
    try {
      const response = await appointmentsApi.update(id, payload);
      const index = appointments.value.findIndex((a) => a.id === id);
      if (index !== -1) {
        appointments.value[index] = response.data;
      }
      return response;
    } catch (err: any) {
      throw err.response?.data || err;
    }
  };

  const deleteAppointment = async (id: number) => {
    try {
      await appointmentsApi.delete(id);
      appointments.value = appointments.value.filter((a) => a.id !== id);
    } catch (err: any) {
      throw err.response?.data || err;
    }
  };

  return {
    appointments,
    selectedAppointment,
    currentPage,
    lastPage,
    total,
    loading,
    error,
    loadAppointments,
    loadByDate,
    loadByWeek,
    createAppointment,
    updateAppointment,
    deleteAppointment,
  };
});
