import client from './client';

export const appointmentsApi = {
  async list(params?: any) {
    const query = new URLSearchParams();
    if (params) {
      Object.entries(params).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
          query.append(key, String(value));
        }
      });
    }

    const { data } = await client.get(`/appointments?${query.toString()}`);
    return data;
  },

  async get(id: number) {
    const { data } = await client.get(`/appointments/${id}`);
    return data;
  },

  async create(payload: any) {
    const { data } = await client.post('/appointments', payload);
    return data;
  },

  async update(id: number, payload: any) {
    const { data } = await client.patch(`/appointments/${id}`, payload);
    return data;
  },

  async delete(id: number) {
    const { data } = await client.delete(`/appointments/${id}`);
    return data;
  },

  async getByDate(date: string) {
    const { data } = await client.get(`/appointments?date=${date}`);
    return data;
  },

  async getByWeek(fromDate: string, toDate: string) {
    const { data } = await client.get(`/appointments?from_date=${fromDate}&to_date=${toDate}`);
    return data;
  },

  async getByEmployee(employeeId: number, fromDate?: string, toDate?: string) {
    const query = new URLSearchParams();
    query.append('employee_id', String(employeeId));
    if (fromDate) query.append('from_date', fromDate);
    if (toDate) query.append('to_date', toDate);

    const { data } = await client.get(`/appointments?${query.toString()}`);
    return data;
  },
};
