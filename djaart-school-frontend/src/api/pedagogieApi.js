import axiosClient from './axiosClient'

export const fetchSequences = (params) => axiosClient.get('/api/sequences', { params })
export const createSequence = (payload) => axiosClient.post('/api/sequences', payload)
export const updateSequence = (id, payload) => axiosClient.put(`/api/sequences/${id}`, payload)
export const deleteSequence = (id) => axiosClient.delete(`/api/sequences/${id}`)

export const fetchSemestres = (params) => axiosClient.get('/api/semestres', { params })
export const createSemestre = (payload) => axiosClient.post('/api/semestres', payload)
export const updateSemestre = (id, payload) => axiosClient.put(`/api/semestres/${id}`, payload)
export const deleteSemestre = (id) => axiosClient.delete(`/api/semestres/${id}`)

export const fetchAffectations = (params) => axiosClient.get('/api/affectations', { params })
export const createAffectation = (payload) => axiosClient.post('/api/affectations', payload)
export const deleteAffectation = (id) => axiosClient.delete(`/api/affectations/${id}`)

export const fetchNotes = (affectationId, params) =>
  axiosClient.get(`/api/affectations/${affectationId}/notes`, { params })
export const soumettreNotes = (affectationId, payload) =>
  axiosClient.post(`/api/affectations/${affectationId}/notes`, payload)
export const deverrouillerNotes = (affectationId, payload) =>
  axiosClient.post(`/api/affectations/${affectationId}/notes/deverrouiller`, payload)
