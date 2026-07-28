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

export const listePhotosSeanceUrl = (classeId) => `${import.meta.env.VITE_API_URL}/api/classes/${classeId}/photos/liste`

export const importerPhotosMasse = (classeId, files) => {
  const formData = new FormData()
  files.forEach((file) => formData.append('photos[]', file))
  return axiosClient.post(`/api/classes/${classeId}/apprenants/photos-masse`, formData)
}

export const genererAttestationsMasse = (classeId, type, apprenantIds) =>
  axiosClient.post(`/api/classes/${classeId}/attestations/masse`, { type, apprenant_ids: apprenantIds })
export const genererCartesScolairesMasse = (classeId, apprenantIds) =>
  axiosClient.post(`/api/classes/${classeId}/cartes-scolaires/masse`, { apprenant_ids: apprenantIds })
export const attestationsZipUrl = (ids) => `${import.meta.env.VITE_API_URL}/api/attestations/zip?ids=${ids.join(',')}`
export const cartesScolairesZipUrl = (ids) => `${import.meta.env.VITE_API_URL}/api/cartes-scolaires/zip?ids=${ids.join(',')}`
