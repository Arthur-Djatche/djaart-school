import axiosClient from './axiosClient'

export const changerMotDePasse = (payload) => axiosClient.put('/api/moi/mot-de-passe', payload)

export const basculerEtablissement = (etablissementId) =>
  axiosClient.put('/api/moi/etablissement-actif', { etablissement_id: etablissementId })
