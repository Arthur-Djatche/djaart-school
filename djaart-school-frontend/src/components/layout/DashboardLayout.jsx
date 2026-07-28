import { useEffect, useState } from 'react'
import { useLocation } from 'react-router-dom'
import Sidebar from './Sidebar'
import Topbar from './Topbar'

export default function DashboardLayout({ children }) {
  const [mobileNavOpen, setMobileNavOpen] = useState(false)
  const location = useLocation()

  useEffect(() => {
    setMobileNavOpen(false)
  }, [location.pathname])

  return (
    <div className="flex h-screen flex-col">
      <Topbar onMenuClick={() => setMobileNavOpen(true)} />
      <div className="flex flex-1 overflow-hidden">
        <Sidebar open={mobileNavOpen} onClose={() => setMobileNavOpen(false)} />
        <main className="flex-1 overflow-y-auto bg-slate-50 p-4 sm:p-6">{children}</main>
      </div>
    </div>
  )
}
