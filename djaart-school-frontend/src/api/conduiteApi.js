import axiosClient from './axiosClient'

export const fetchConduite = (classeId, sequenceId) =>
  axiosClient.get(`/api/classes/${classeId}/sequences/${sequenceId}/conduite`)

export const enregistrerConduite = (classeId, sequenceId, lignes) =>
  axiosClient.post(`/api/classes/${classeId}/sequences/${sequenceId}/conduite`, { lignes })
