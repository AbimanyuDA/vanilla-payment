import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { useEffect, useState } from 'react';
import { Link } from 'react-router';

import { EmptyRow, ErrorState, Loading, PageHeader, Pagination, StatusBadge } from '../../components/ui/primitives';
import { api, withQuery } from '../../lib/api';
import { formatDate, money } from '../../lib/format';
import { useDebounce, useUrlFilters } from '../../lib/hooks';
import type { Paginated, QuotationSummary } from '../../lib/types';
import { useTradeMeta } from './TradeFields';

export function QuotationListPage() {
  const meta = useTradeMeta();
  const { values, page, set } = useUrlFilters(['status', 'search'] as const);
  const [search, setSearch] = useState(values.search);
  const debounced = useDebounce(search);
  useEffect(() => {
    if (debounced !== values.search) set({ search: debounced });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [debounced]);

  const q = useQuery({
    queryKey: ['quotations', values, page],
    queryFn: () => api.get<Paginated<QuotationSummary>>(withQuery('/api/quotations', { ...values, page })),
    placeholderData: keepPreviousData,
  });

  return (
    <>
      <PageHeader eyebrow="Ekspor internasional" title="Quotation"
        actions={<Link to="/admin/quotations/new" className="btn-primary">+ Quotation baru</Link>} />
      <div className="mb-4 flex flex-wrap gap-3">
        <input type="search" className="input max-w-xs" placeholder="Cari nomor atau buyer…" aria-label="Cari quotation"
          value={search} onChange={(e) => setSearch(e.target.value)} />
        <select className="input w-auto" aria-label="Filter status" value={values.status} onChange={(e) => set({ status: e.target.value })}>
          <option value="">Semua status</option>
          {meta.data && Object.entries(meta.data.quotation_statuses).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
        </select>
      </div>

      {q.isPending ? <Loading /> : q.error ? <ErrorState error={q.error} /> : (
        <div className="panel overflow-hidden">
          <div className="scroll-x">
            <table className="ledger">
              <thead>
                <tr><th>Nomor</th><th>Buyer</th><th>Terms</th><th className="text-right!">Grand total</th><th>Status</th><th>Berlaku s/d</th></tr>
              </thead>
              <tbody className={q.isPlaceholderData ? 'opacity-60' : ''}>
                {q.data.data.length === 0 && <EmptyRow colSpan={6}>Belum ada quotation.</EmptyRow>}
                {q.data.data.map((qt) => (
                  <tr key={qt.id}>
                    <td><Link className="link font-mono" to={`/admin/quotations/${qt.id}`}>{qt.quotation_number}</Link></td>
                    <td>{qt.buyer_company_name}{qt.buyer_country && <p className="text-xs text-clay">{qt.buyer_country}</p>}</td>
                    <td className="font-mono text-xs">{qt.incoterm} · {qt.currency}</td>
                    <td className="text-right whitespace-nowrap"><span className="text-xs text-clay">{qt.currency}</span> {money(qt.grand_total)}</td>
                    <td><StatusBadge status={qt.status} label={qt.status_label} /></td>
                    <td className={`whitespace-nowrap ${qt.is_expired ? 'text-flag' : 'text-clay'}`}>
                      {formatDate(qt.valid_until)}{qt.is_expired && <p className="text-xs">lewat masa berlaku</p>}
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
