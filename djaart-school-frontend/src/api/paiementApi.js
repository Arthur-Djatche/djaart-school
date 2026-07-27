import axiosClient from './axiosClient'

export const fetchEcheancier = (apprenantId) => axiosClient.get(`/api/apprenants/${apprenantId}/echeancier`)
export const fetchPaiements = (params) => axiosClient.get('/api/paiements', { params })
export const createPaiement = (payload) => axiosClient.post('/api/paiements', payload)

export const recuDownloadUrl = (recuId) => `${import.meta.env.VITE_API_URL}/api/recus/${recuId}/telecharger`
export const recuInlineUrl = (recuId) => `${import.meta.env.VITE_API_URL}/api/recus/${recuId}/telecharger?inline=1`
