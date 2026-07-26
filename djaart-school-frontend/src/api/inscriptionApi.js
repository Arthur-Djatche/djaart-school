import axiosClient from './axiosClient'

export const searchApprenants = (search) => axiosClient.get('/api/apprenants', { params: { search } })

export const fetchInscriptions = (params) => axiosClient.get('/api/inscriptions', { params })
export const createInscription = (payload) => axiosClient.post('/api/inscriptions', payload)
export const cancelInscription = (id) => axiosClient.post(`/api/inscriptions/${id}/annuler`)
