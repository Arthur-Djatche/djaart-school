import axiosClient from './axiosClient'

export const fetchBulletins = (params) => axiosClient.get('/api/bulletins', { params })
export const cloturerSequence = (classeId, sequenceId) =>
  axiosClient.post(`/api/classes/${classeId}/sequences/${sequenceId}/cloturer`)
export const bulletinDownloadUrl = (bulletinId) => `${import.meta.env.VITE_API_URL}/api/bulletins/${bulletinId}/telecharger`

export const fetchReleves = (params) => axiosClient.get('/api/releves', { params })
export const genererRelevesAnnuels = (classeId) => axiosClient.post(`/api/classes/${classeId}/releves/annuel`)
export const releveDownloadUrl = (releveId) => `${import.meta.env.VITE_API_URL}/api/releves/${releveId}/telecharger`
