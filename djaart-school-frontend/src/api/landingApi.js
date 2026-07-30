import axiosClient from './axiosClient'

export const demanderDemo = (payload) => axiosClient.post('/api/demandes-demo', payload)
export const fetchDemandesDemo = (params) => axiosClient.get('/api/demandes-demo', { params })

export const commander = (payload) => axiosClient.post('/api/commandes', payload)
export const fetchCommandes = (params) => axiosClient.get('/api/commandes', { params })
export const validerCommande = (id, payload) => axiosClient.post(`/api/commandes/${id}/valider`, payload)
