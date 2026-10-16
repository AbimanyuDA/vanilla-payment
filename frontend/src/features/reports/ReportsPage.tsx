import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { Link } from 'react-router';

import { EmptyRow, ErrorState, Field, Loading, PageHeader, StatusBadge } from '../../components/ui/primitives';
import { api, withQuery } from '../../lib/api';
import { formatDate, rupiah } from '../../lib/format';
import { useUrlFilters } from '../../lib/hooks';
import type { InvoiceSummary } from '../../lib/types';
import { INVOICE_STATUSES } from '../invoices/InvoiceListPage';

export function ReportsPage() {
  const { values, set } = useUrlFilters(['status', 'from', 'to'] as const);
  const q = useQuery({
    queryKey: ['reports', values],
    queryFn: () => api.get<{ invoices: InvoiceSummary[]; total_revenue: number }>(withQuery('/api/reports', values)),
    placeholderData: keepPreviousData,
  });

  return (
    <>
      <PageHeader
        eyebrow="Pembayaran domestik"
        title="Laporan"
        actions={
          <>
            <a className="btn-secondary" href={withQuery('/api/reports/export-pdf', values)}>Ekspor PDF</a>
            <a className="btn-primary" href={withQuery('/api/reports/export-excel', values)}>Ekspor Excel</a>
          </>
        }
      />

      <div className="mb-6 grid gap-4 sm:grid-cols-3 lg:max-w-3xl">
        <Field label="Status">
          <select className="input" value={values.status} onChange={(e) => set({ status: e.target.value })}>
            <option value="">Semua status</option>
            {INVOICE_STATUSES.map(([v, l]) => <option key={v} value={v}>{l}</option>)}
          </select>
        </Field>
        <Field label="Dari tanggal">
          <input type="date" className="input" value={values.from} onChange={(e) => set({ from: e.target.value })} />
        </Field>
        <Field label="Sampai tanggal">
          <input type="date" className="input" value={values.to} onChange={(e) => set({ to: e.target.value })} />
        </Field>
      </div>

      {q.isPending ? <Loading /> : q.error ? <ErrorState error={q.error} /> : (
        <>
          <div className="mb-6 grid gap-4 sm:grid-cols-2">
            <div className="rounded-lg bg-ink p-5 text-paper">
              <p className="font-mono text-[0.7rem] tracking-[0.14em] text-gold uppercase">Pendapatan lunas (periode ini)</p>
              <p className="figure mt-2 text-4xl break-all">{rupiah(q.data.total_revenue)}</p>
            </div>
            <div className="panel-frost p-5">
              <p className="eyebrow">Jumlah invoice</p>
              <p className="figure mt-2 text-4xl">{q.data.invoices.length}</p>
            </div>
          </div>

          <div className="panel overflow-hidden">
            <div className="scroll-x">
              <table className="ledger">
                <thead>
                  <tr><th>#</th><th>No. invoice</th><th>Customer</th><th className="text-right!">Total</th><th>Metode</th><th>Status</th><th>Tanggal</th><th>Lunas</th></tr>
                </thead>
                <tbody className={q.isPlaceholderData ? 'opacity-60' : ''}>
                  {q.data.invoices.length === 0 && <EmptyRow colSpan={8}>Tidak ada data untuk filter ini.</EmptyRow>}
                  {q.data.invoices.map((inv, i) => (
                    <tr key={inv.id}>
                      <td className="text-clay">{i + 1}</td>
                      <td><Link className="link font-mono" to={`/admin/invoices/${inv.id}`}>{inv.invoice_number}</Link></td>
                      <td>{inv.customer_name}</td>
                      <td className="text-right whitespace-nowrap">{rupiah(inv.total_amount)}</td>
                      <td>{inv.payment_method === 'transfer' ? 'Transfer' : inv.payment_method === 'qris' ? 'QRIS' : '—'}</td>
                      <td><StatusBadge status={inv.status} label={inv.status_label} /></td>
                      <td className="whitespace-nowrap text-clay">{formatDate(inv.created_at)}</td>
                      <td className="whitespace-nowrap text-clay">{formatDate(inv.paid_at)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </>
      )}
    </>
  );
}
