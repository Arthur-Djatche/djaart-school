import axiosClient from './axiosClient'

export const fetchDashboard = () => axiosClient.get('/api/dashboard')

export const rapportImpayesUrl = () => `${import.meta.env.VITE_API_URL}/api/rapports/impayes`
export const rapportStatistiquesReussiteUrl = () => `${import.meta.env.VITE_API_URL}/api/rapports/statistiques-reussite`
