import axiosClient, { ensureCsrfCookie } from './axiosClient'

export const login = async (email, password) => {
  await ensureCsrfCookie()
  const { data } = await axiosClient.post('/api/login', { email, password })
  return data
}

export const logout = () => axiosClient.post('/api/logout')

export const fetchMe = () => axiosClient.get('/api/me')
