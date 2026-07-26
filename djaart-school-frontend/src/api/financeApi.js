import axiosClient from './axiosClient'

export const fetchFraisScolarite = (params) => axiosClient.get('/api/frais-scolarite', { params })
export const createFraisScolarite = (payload) => axiosClient.post('/api/frais-scolarite', payload)
export const updateFraisScolarite = (id, payload) => axiosClient.put(`/api/frais-scolarite/${id}`, payload)
export const deleteFraisScolarite = (id) => axiosClient.delete(`/api/frais-scolarite/${id}`)
