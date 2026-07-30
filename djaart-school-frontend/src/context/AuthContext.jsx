import { createContext, useCallback, useEffect, useRef, useState } from 'react'
import * as authApi from '../api/authApi'

export const AuthContext = createContext(null)

const PUBLIC_ONLY_ROUTES = ['/login', '/forgot-password', '/reset-password']

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [loading, setLoading] = useState(true)
  const hasCheckedAuth = useRef(false)

  const checkAuth = useCallback(async () => {
    try {
      const { data } = await authApi.fetchMe()
      setUser(data)
    } catch {
      setUser(null)
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    // Ne pas interroger /api/me sur les pages publiques : cet appel touche la
    // session et peut entrer en concurrence avec la requête de connexion sur
    // la même session, invalidant le jeton CSRF (pilote de session database).
    if (PUBLIC_ONLY_ROUTES.includes(window.location.pathname)) {
      setLoading(false)
      return
    }
    // Le Strict Mode de React invoque les effets deux fois en dev : deux
    // requetes /api/me simultanees peuvent saturer le serveur de dev PHP
    // (mono-thread) et faire echouer l'une des deux. On garde un verrou.
    if (hasCheckedAuth.current) return
    hasCheckedAuth.current = true
    checkAuth()
  }, [checkAuth])

  const login = async (email, password) => {
    const { data } = await authApi.login(email, password)
    setUser(data)
    return data
  }

  const logout = async () => {
    await authApi.logout()
    setUser(null)
  }

  return (
    <AuthContext.Provider value={{ user, loading, login, logout, updateUser: setUser }}>
      {children}
    </AuthContext.Provider>
  )
}
