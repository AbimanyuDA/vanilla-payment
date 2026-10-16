import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { useEffect, useState } from 'react';
import { Link } from 'react-router';

import { EmptyRow, ErrorState, Loading, PageHeader, Pagination, StatusBadge } from '../../components/ui/primitives';
import { api, withQuery } from '../../lib/api';
import { formatDate, rupiah } from '../../lib/format';
import { useDebounce, useUrlFilters } from '../../lib/hooks';
import type { InvoiceSummary, Paginated } from '../../lib/types';

export const INVOICE_STATUSES = [
  ['draft', 'Draft'],
  ['sent', 'Terkirim'],
  ['pending_confirmation', 'Menunggu Konfirmasi'],
  ['paid', 'Lunas'],
  ['cancelled', 'Dibatalkan'],
  ['expired', 'Kadaluarsa'],
] as const;

export function InvoiceListPage() {
  const { values, page, set } = useUrlFilters(['status', 'search'] as const);
  const [search, setSearch] = useState(values.search);
  const debounced = useDebounce(search);
  useEffect(() => {
    if (debounced !== values.search) set({ search: debounced });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [debounced]);

  const q = useQuery({
    queryKey: ['invoices', values, page],
    queryFn: () => api.get<Paginated<InvoiceSummary>>(withQuery('/api/invoices', { ...values, page })),
    placeholderData: keepPreviousData,
  });

  return (
    <>
      <PageHeader
        eyebrow="Pembayaran domestik"
        title="Invoice"
        actions={<Link to="/admin/invoices/new" className="btn-primary">+ Invoice baru</Link>}
      />

      <div className="mb-4 flex flex-wrap gap-3">
        <input
          type="search" className="input max-w-xs" placeholder="Cari no. invoice, nama, telepon…"
          aria-label="Cari invoice" value={search} onChange={(e) => setSearch(e.target.value)}
        />
        <select className="input w-auto" aria-label="Filter status" value={values.status}
          onChange={(e) => set({ status: e.target.value })}>
          <option value="">Semua status</option>
          {INVOICE_STATUSES.map(([v, l]) => <option key={v} value={v}>{l}</option>)}
        </select>
      </div>

      {q.isPending ? <Loading /> : q.error ? <ErrorState error={q.error} /> : (
        <div className="panel overflow-hidden">
          <div className="scroll-x">
            <table className="ledger">
              <thead>
                <tr>
                  <th>No. invoice</th><th>Customer</th><th className="text-right!">Total</th>
                  <th>Status</th><th>Jatuh tempo</th><th>Dibuat</th>
                </tr>
              </thead>
              <tbody className={q.isPlaceholderData ? 'opacity-60' : ''}>
                {q.data.data.length === 0 && <EmptyRow colSpan={6}>Tidak ada invoice yang cocok.</EmptyRow>}
                {q.data.data.map((inv) => (
                  <tr key={inv.id}>
                    <td><Link className="link font-mono" to={`/admin/invoices/${inv.id}`}>{inv.invoice_number}</Link></td>
                    <td>
                      <p>{inv.customer_name}</p>
                      {inv.customer_phone && <p className="font-mono text-xs text-clay">{inv.customer_phone}</p>}
                    </td>
                    <td className="text-right whitespace-nowrap">{rupiah(inv.total_amount)}</td>
                    <td><StatusBadge status={inv.status} label={inv.status_label} /></td>
                    <td className="whitespace-nowrap text-clay">{formatDate(inv.due_date)}</td>
                    <td className="whitespace-nowrap text-clay">
                      {formatDate(inv.created_at)}
                      {inv.creator && <p className="text-xs">{inv.creator.name}</p>}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <Pagination page={q.data.meta.page} pages={q.data.meta.pages} onPage={(p) => set({ page: p })} />
        </div>
      )}
    </>
  );
}
