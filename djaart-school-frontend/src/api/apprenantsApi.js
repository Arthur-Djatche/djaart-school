import axiosClient from './axiosClient'

export const searchApprenants = (search) => axiosClient.get('/api/apprenants', { params: { search } })
export const fetchApprenant = (id) => axiosClient.get(`/api/apprenants/${id}`)

export const uploadPhoto = (apprenantId, file) => {
  const formData = new FormData()
  formData.append('photo', file)
  return axiosClient.post(`/api/apprenants/${apprenantId}/photo`, formData)
}

export const fetchAttestations = (apprenantId) => axiosClient.get(`/api/apprenants/${apprenantId}/attestations`)
export const createAttestation = (apprenantId, type) =>
  axiosClient.post(`/api/apprenants/${apprenantId}/attestations`, { type })
export const attestationDownloadUrl = (id) => `${import.meta.env.VITE_API_URL}/api/attestations/${id}/telecharger`

export const fetchCartesScolaires = (apprenantId) => axiosClient.get(`/api/apprenants/${apprenantId}/cartes-scolaires`)
export const createCarteScolaire = (apprenantId) =>
  axiosClient.post(`/api/apprenants/${apprenantId}/cartes-scolaires`)
export const carteScolaireDownloadUrl = (id) => `${import.meta.env.VITE_API_URL}/api/cartes-scolaires/${id}/telecharger`
