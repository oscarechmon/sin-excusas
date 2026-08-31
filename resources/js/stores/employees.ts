import { defineStore } from 'pinia'
import { employeesApi } from '@/api/employees.api'
import { usePaginatedList } from '@/composables/usePaginatedList'

export const useEmployeesStore = defineStore('employees', () => {
  const list = usePaginatedList<any>((params) => employeesApi.list(params))

  const createEmployee = async (payload: Record<string, unknown>) => {
    const response = await employeesApi.create(payload)
    await list.reload()
    return response
  }

  const updateEmployee = async (id: number, payload: Record<string, unknown>) => {
    const response = await employeesApi.update(id, payload)
    await list.reload()
    return response
  }

  const deleteEmployee = async (id: number) => {
    const response = await employeesApi.remove(id)
    await list.reload()
    return response
  }

  return { ...list, createEmployee, updateEmployee, deleteEmployee }
})
