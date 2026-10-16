import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router';

import { EmptyRow, ErrorState, Loading, PageHeader, StatusBadge } from '../../components/ui/primitives';
import { api } from '../../lib/api';
import { formatDate, rupiah } from '../../lib/format';
import type { InvoiceSummary } from '../../lib/types';

type Dashboard = {
  stats: { total: number; draft: number; sent: number; pending: number; paid: number; total_revenue: number };
  recent_invoices: InvoiceSummary[];
  pending_proofs: (InvoiceSummary & { proof_uploaded_at: string | null })[];
};

export function DashboardPage() {
  const q = useQuery({ queryKey: ['dashboard'], queryFn: () => api.get<Dashboard>('/api/dashboard') });

  return (
    <>
      <PageHeader
        eyebrow="Ringkasan"
        title="Dashboard"
        actions={<Link to="/admin/invoices/new" className="btn-primary">+ Invoice baru</Link>}
      />
      {q.isPending ? <Loading /> : q.error ? <ErrorState error={q.error} /> : (
        <div className="space-y-8">
          {/* Revenue leads; the status counts read as a ledger strip beneath it. */}
          <section className="grid gap-4 lg:grid-cols-[1.3fr_2fr]">
            <div className="animate-rise relative overflow-hidden rounded-lg bg-ink p-6 text-paper">
              <div aria-hidden className="absolute -top-20 -right-16 size-56 rounded-full bg-[radial-gradient(circle,rgba(232,163,61,0.25),transparent_65%)]" />
              <p className="font-mono text-[0.7rem] tracking-[0.14em] text-gold uppercase">Total pendapatan lunas</p>
              <p className="figure mt-3 text-4xl break-all sm:text-5xl">{rupiah(q.data.stats.total_revenue)}</p>
              <p className="mt-2 text-sm text-paper/60">dari {q.data.stats.paid} invoice lunas</p>
            </div>
            <dl className="grid grid-cols-2 overflow-hidden rounded-lg border border-frost-deep bg-white sm:grid-cols-4">
              {([
                ['Total invoice', q.data.stats.total, '/admin/invoices', 'text-ink'],
                ['Terkirim', q.data.stats.sent, '/admin/invoices?status=sent', 'text-sky'],
                ['Menunggu konfirmasi', q.data.stats.pending, '/admin/invoices?status=pending_confirmation', 'text-amber-deep'],
                ['Lunas', q.data.stats.paid, '/admin/invoices?status=paid', 'text-moss'],
              ] as const).map(([label, value, to, tone], i) => (
                <Link
                  key={label} to={to}
                  style={{ animationDelay: `${60 * (i + 1)}ms` }}
                  className="animate-rise group border-frost-deep p-5 transition-colors not-last:border-r hover:bg-frost/50 max-sm:[&:nth-child(-n+2)]:border-b max-sm:[&:nth-child(2)]:border-r-0"
                >
                  <dt className="eyebrow group-hover:text-amber">{label}</dt>
                  <dd className={`figure mt-3 text-4xl ${tone}`}>{value}</dd>
                </Link>
              ))}
            </dl>
          </section>

          {q.data.pending_proofs.length > 0 && (
            <section className="rounded-lg border border-amber/40 bg-[#fbf3e8] p-5 sm:p-6">
              <p className="eyebrow-accent">Perlu tindakan</p>
              <h2 className="mt-1 text-xl">Bukti transfer menunggu verifikasi</h2>
              <ul className="mt-4 divide-y divide-amber/20">
                {q.data.pending_proofs.map((inv) => (
                  <li key={inv.id} className="flex flex-wrap items-center justify-between gap-3 py-3">
                    <div>
                      <p className="font-mono text-sm">{inv.invoice_number}</p>
                      <p className="text-sm text-ink-soft">
                        {inv.customer_name} · diunggah {formatDate(inv.proof_uploaded_at, true)}
                      </p>
                    </div>
                    <div className="flex items-center gap-4">
                      <span className="figure text-lg">{rupiah(inv.total_amount)}</span>
                      <Link to={`/admin/invoices/${inv.id}`} className="btn-primary btn-sm">Periksa →</Link>
                    </div>
                  </li>
                ))}
              </ul>
            </section>
          )}

          <section className="panel overflow-hidden">
            <div className="flex items-baseline justify-between px-5 pt-5 pb-4">
              <h2 className="text-xl">Invoice terbaru</h2>
              <Link to="/admin/invoices" className="link text-sm">Lihat semua</Link>
            </div>
            <div className="scroll-x">
              <table className="ledger">
                <thead>
                  <tr><th>No. invoice</th><th>Customer</th><th className="text-right!">Total</th><th>Status</th><th>Dibuat</th></tr>
                </thead>
                <tbody>
                  {q.data.recent_invoices.length === 0 && <EmptyRow colSpan={5}>Belum ada invoice.</EmptyRow>}
                  {q.data.recent_invoices.map((inv) => (
                    <tr key={inv.id}>
                      <td><Link className="link font-mono" to={`/admin/invoices/${inv.id}`}>{inv.invoice_number}</Link></td>
                      <td>{inv.customer_name}</td>
                      <td className="text-right whitespace-nowrap">{rupiah(inv.total_amount)}</td>
                      <td><StatusBadge status={inv.status} label={inv.status_label} /></td>
                      <td className="whitespace-nowrap text-clay">{formatDate(inv.created_at)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </section>
        </div>
      )}
    </>
  );
}
