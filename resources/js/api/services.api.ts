import { api as client } from './client';

export const servicesApi = {
  async list(page = 1, categoryId?: number, active?: boolean) {
    const params = new URLSearchParams();
    if (page) params.append('page', page.toString());
    if (categoryId) params.append('category_id', categoryId.toString());
    if (active !== undefined) params.append('active', active ? '1' : '0');

    const { data } = await client.get(`/services?${params.toString()}`);
    return data;
  },

  async get(id: number) {
    const { data } = await client.get(`/services/${id}`);
    return data;
  },

  async create(payload: any) {
    const { data } = await client.post('/services', payload);
    return data;
  },

  async update(id: number, payload: any) {
    const { data } = await client.patch(`/services/${id}`, payload);
    return data;
  },

  async delete(id: number) {
    const { data } = await client.delete(`/services/${id}`);
    return data;
  },

  /** Foto para la web. El header explícito evita que axios convierta el FormData a JSON. */
  async uploadImage(id: number, file: File) {
    const body = new FormData();
    body.append('image', file);
    const { data } = await client.post(`/services/${id}/image`, body, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return data;
  },

  async removeImage(id: number) {
    const { data } = await client.delete(`/services/${id}/image`);
    return data;
  },

  /** Publica o retira el servicio del catálogo de la web. */
  async setPublished(id: number, isPublished: boolean) {
    const { data } = await client.patch(`/services/${id}/publish`, { is_published: isPublished });
    return data;
  },
};

export const serviceCategoriesApi = {
  async list() {
    const { data } = await client.get('/service-categories');
    return data;
  },

  async get(id: number) {
    const { data } = await client.get(`/service-categories/${id}`);
    return data;
  },

  async create(payload: any) {
    const { data } = await client.post('/service-categories', payload);
    return data;
  },

  async update(id: number, payload: any) {
    const { data } = await client.patch(`/service-categories/${id}`, payload);
    return data;
  },

  async delete(id: number) {
    const { data } = await client.delete(`/service-categories/${id}`);
    return data;
  },
};
