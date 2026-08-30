import client from './client';

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
