import axios from 'axios'

const apiUrl = import.meta.env.VITE_API_URL

const axiosClient = axios.create({
  baseURL: apiUrl,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
  },
})

export const ensureCsrfCookie = () => axios.get(`${apiUrl}/sanctum/csrf-cookie`, { withCredentials: true })

axiosClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && window.location.pathname !== '/login') {
      window.location.href = '/login'
    }
    return Promise.reject(error)
  },
)

export default axiosClient
