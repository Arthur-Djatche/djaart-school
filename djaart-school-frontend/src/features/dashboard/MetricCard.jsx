export default function MetricCard({ label, value, accent = false }) {
  return (
    <div className="rounded-lg border border-slate-200 bg-white p-4">
      <p className="text-sm text-slate-500">{label}</p>
      <p className={`mt-1 text-2xl font-semibold ${accent ? 'text-brand-orange' : 'text-brand-navy'}`}>{value}</p>
    </div>
  )
}
