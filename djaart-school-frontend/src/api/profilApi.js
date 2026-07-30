import axiosClient from './axiosClient'

export const changerMotDePasse = (payload) => axiosClient.put('/api/moi/mot-de-passe', payload)

export const basculerEtablissement = (etablissementId) =>
  axiosClient.put('/api/moi/etablissement-actif', { etablissement_id: etablissementId })

export const mettreAJourProfil = (payload) => axiosClient.put('/api/moi/profil', payload)

export const mettreAJourPhoto = (file) => {
  const formData = new FormData()
  formData.append('photo', file)
  return axiosClient.post('/api/moi/photo', formData)
}
