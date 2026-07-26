import axiosClient, { ensureCsrfCookie } from './axiosClient'

export const login = async (email, password) => {
  await ensureCsrfCookie()
  const { data } = await axiosClient.post('/api/login', { email, password })
  return data
}

export const logout = () => axiosClient.post('/api/logout')

export const fetchMe = async () => {
  const { data } = await axiosClient.get('/api/me')
  return data
}

export const forgotPassword = async (email) => {
  await ensureCsrfCookie()
  return axiosClient.post('/api/forgot-password', { email })
}

export const resetPassword = async ({ token, email, password, password_confirmation }) => {
  await ensureCsrfCookie()
  return axiosClient.post('/api/reset-password', { token, email, password, password_confirmation })
}
