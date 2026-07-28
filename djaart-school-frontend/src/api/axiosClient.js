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

// Pages accessibles sans session : y recevoir un 401 (ex. l'appel /api/me
// silencieux d'AuthContext sur la landing page, pour savoir si un visiteur
// est deja connecte) est un etat normal, pas une session expiree — forcer une
// redirection vers /login y renverrait a tort un visiteur non connecte qui
// n'a jamais quitte une page publique.
const PAGES_PUBLIQUES = ['/', '/login', '/forgot-password', '/reset-password']

axiosClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && !PAGES_PUBLIQUES.includes(window.location.pathname)) {
      window.location.href = '/login'
    }
    return Promise.reject(error)
  },
)

export default axiosClient
