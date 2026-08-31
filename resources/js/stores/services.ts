import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { servicesApi, serviceCategoriesApi } from '@/api/services.api';

export const useServicesStore = defineStore('services', () => {
  const services = ref<any[]>([]);
  const categories = ref<any[]>([]);
  const selectedService = ref<any | null>(null);
  const currentPage = ref(1);
  const lastPage = ref(1);
  const total = ref(0);
  const loading = ref(false);
  const error = ref<string | null>(null);

  const loadServices = async (page: number = 1, categoryId?: number, active?: boolean) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await servicesApi.list(page, categoryId, active);
      services.value = response.data;
      currentPage.value = response.meta.current_page;
      lastPage.value = response.meta.last_page;
      total.value = response.meta.total;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error loading services';
    } finally {
      loading.value = false;
    }
  };

  const loadCategories = async () => {
    try {
      const response = await serviceCategoriesApi.list();
      categories.value = response.data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error loading categories';
    }
  };

  const createService = async (payload: any) => {
    try {
      const response = await servicesApi.create(payload);
      services.value.unshift(response.data);
      return response;
    } catch (err: any) {
      throw err.response?.data || err;
    }
  };

  const updateService = async (id: number, payload: any) => {
    try {
      const response = await servicesApi.update(id, payload);
      const index = services.value.findIndex((s) => s.id === id);
      if (index !== -1) {
        services.value[index] = response.data;
      }
      return response;
    } catch (err: any) {
      throw err.response?.data || err;
    }
  };

  const deleteService = async (id: number) => {
    try {
      await servicesApi.delete(id);
      services.value = services.value.filter((s) => s.id !== id);
    } catch (err: any) {
      throw err.response?.data || err;
    }
  };

  const createCategory = async (payload: any) => {
    try {
      const response = await serviceCategoriesApi.create(payload);
      categories.value.unshift(response.data);
      return response;
    } catch (err: any) {
      throw err.response?.data || err;
    }
  };

  const updateCategory = async (id: number, payload: any) => {
    try {
      const response = await serviceCategoriesApi.update(id, payload);
      const index = categories.value.findIndex((c) => c.id === id);
      if (index !== -1) {
        categories.value[index] = response.data;
      }
      return response;
    } catch (err: any) {
      throw err.response?.data || err;
    }
  };

  const deleteCategory = async (id: number) => {
    try {
      await serviceCategoriesApi.delete(id);
      categories.value = categories.value.filter((c) => c.id !== id);
    } catch (err: any) {
      throw err.response?.data || err;
    }
  };

  return {
    services,
    categories,
    selectedService,
    currentPage,
    lastPage,
    total,
    loading,
    error,
    loadServices,
    loadCategories,
    createService,
    updateService,
    deleteService,
    createCategory,
    updateCategory,
    deleteCategory,
  };
});
