export default function Select({ label, error, id, options, placeholder, className = '', ...props }) {
  return (
    <div className="flex flex-col gap-1">
      {label && (
        <label htmlFor={id} className="text-sm font-medium text-brand-navy">
          {label}
        </label>
      )}
      <select
        id={id}
        className={`rounded-lg border px-3 py-2 outline-none transition focus:border-brand-blue focus:ring-2 focus:ring-brand-blue-light/40 ${
          error ? 'border-red-400' : 'border-slate-300'
        } ${className}`}
        {...props}
      >
        {placeholder && <option value="">{placeholder}</option>}
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
      {error && <span className="text-sm text-red-600">{error}</span>}
    </div>
  )
}
