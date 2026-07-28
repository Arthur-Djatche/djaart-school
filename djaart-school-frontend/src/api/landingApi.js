import axiosClient from './axiosClient'

export const demanderDemo = (payload) => axiosClient.post('/api/demandes-demo', payload)
export const fetchDemandesDemo = (params) => axiosClient.get('/api/demandes-demo', { params })
