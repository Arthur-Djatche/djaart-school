import axiosClient from './axiosClient'

export const fetchUsers = (params) => axiosClient.get('/api/users', { params })

export const createUser = (payload) => axiosClient.post('/api/users', payload)

export const updateUser = (id, payload) => axiosClient.put(`/api/users/${id}`, payload)

export const deleteUser = (id) => axiosClient.delete(`/api/users/${id}`)

export const updateUserPermissions = (id, permissions) =>
  axiosClient.put(`/api/users/${id}/permissions`, { permissions })
