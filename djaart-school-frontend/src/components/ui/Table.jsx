import Input from './Input'
import Button from './Button'

export default function Table({
  columns,
  rows,
  rowKey = 'id',
  searchValue,
  onSearchChange,
  searchPlaceholder = 'Rechercher…',
  page = 1,
  totalPages = 1,
  onPageChange,
  sortKey,
  sortDir = 'asc',
  onSortChange,
  emptyMessage = 'Aucun résultat.',
}) {
  return (
    <div className="flex flex-col gap-4">
      {onSearchChange && (
        <Input
          id="table-search"
          placeholder={searchPlaceholder}
          value={searchValue}
          onChange={(event) => onSearchChange(event.target.value)}
          className="max-w-xs"
        />
      )}

      <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table className="w-full text-left text-sm">
          <thead className="bg-slate-50 text-brand-navy">
            <tr>
              {columns.map((column) => (
                <th
                  key={column.key}
                  className={`px-4 py-3 font-semibold ${column.sortable ? 'cursor-pointer select-none' : ''}`}
                  onClick={() => column.sortable && onSortChange?.(column.key)}
                >
                  {column.label}
                  {column.sortable && sortKey === column.key && (sortDir === 'asc' ? ' ▲' : ' ▼')}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {rows.length === 0 && (
              <tr>
                <td colSpan={columns.length} className="px-4 py-6 text-center text-slate-500">
                  {emptyMessage}
                </td>
              </tr>
            )}
            {rows.map((row) => (
              <tr key={row[rowKey]} className="hover:bg-slate-50">
                {columns.map((column) => (
                  <td key={column.key} className="px-4 py-3">
                    {column.render ? column.render(row) : row[column.key]}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {onPageChange && totalPages > 1 && (
        <div className="flex items-center justify-between text-sm text-slate-600">
          <span>
            Page {page} / {totalPages}
          </span>
          <div className="flex gap-2">
            <Button variant="ghost" disabled={page <= 1} onClick={() => onPageChange(page - 1)}>
              Précédent
            </Button>
            <Button variant="ghost" disabled={page >= totalPages} onClick={() => onPageChange(page + 1)}>
              Suivant
            </Button>
          </div>
        </div>
      )}
    </div>
  )
}
