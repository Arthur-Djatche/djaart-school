import axiosClient from './axiosClient'

export const fetchEtablissements = (params) => axiosClient.get('/api/etablissements', { params })
export const createEtablissement = (payload) => axiosClient.post('/api/etablissements', payload)
export const updateEtablissement = (id, payload) => axiosClient.put(`/api/etablissements/${id}`, payload)
export const deleteEtablissement = (id) => axiosClient.delete(`/api/etablissements/${id}`)

export const uploadEtablissementLogo = (id, file) => {
  const formData = new FormData()
  formData.append('logo', file)
  return axiosClient.post(`/api/etablissements/${id}/logo`, formData)
}

export const uploadEtablissementSignature = (id, file) => {
  const formData = new FormData()
  formData.append('signature', file)
  return axiosClient.post(`/api/etablissements/${id}/signature`, formData)
}

export const fetchAnneesAcademiques = (params) => axiosClient.get('/api/annees-academiques', { params })
export const createAnneeAcademique = (payload) => axiosClient.post('/api/annees-academiques', payload)
export const updateAnneeAcademique = (id, payload) => axiosClient.put(`/api/annees-academiques/${id}`, payload)
export const deleteAnneeAcademique = (id) => axiosClient.delete(`/api/annees-academiques/${id}`)

export const fetchFilieres = (params) => axiosClient.get('/api/filieres', { params })
export const createFiliere = (payload) => axiosClient.post('/api/filieres', payload)
export const updateFiliere = (id, payload) => axiosClient.put(`/api/filieres/${id}`, payload)
export const deleteFiliere = (id) => axiosClient.delete(`/api/filieres/${id}`)

export const fetchNiveauxByFiliere = (filiereId) => axiosClient.get(`/api/filieres/${filiereId}/niveaux`)
export const createNiveau = (payload) => axiosClient.post('/api/niveaux', payload)
export const updateNiveau = (id, payload) => axiosClient.put(`/api/niveaux/${id}`, payload)
export const deleteNiveau = (id) => axiosClient.delete(`/api/niveaux/${id}`)

export const fetchClasses = (params) => axiosClient.get('/api/classes', { params })
export const createClasse = (payload) => axiosClient.post('/api/classes', payload)
export const updateClasse = (id, payload) => axiosClient.put(`/api/classes/${id}`, payload)
export const deleteClasse = (id) => axiosClient.delete(`/api/classes/${id}`)

export const fetchMatieres = (params) => axiosClient.get('/api/matieres', { params })
export const createMatiere = (payload) => axiosClient.post('/api/matieres', payload)
export const updateMatiere = (id, payload) => axiosClient.put(`/api/matieres/${id}`, payload)
export const deleteMatiere = (id) => axiosClient.delete(`/api/matieres/${id}`)
