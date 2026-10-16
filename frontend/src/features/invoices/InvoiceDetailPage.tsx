import { useQuery } from '@tanstack/react-query';
import { useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router';

import { useToast } from '../../components/ui/Toast';
import { DefinitionList, ErrorState, Loading, PageHeader, Section, StatusBadge } from '../../components/ui/primitives';
import { api, type Message } from '../../lib/api';
import { formatDate, rupiah } from '../../lib/format';
import { useAction } from '../../lib/hooks';
import type { InvoiceDetail, PaymentProof } from '../../lib/types';

const INVALIDATE = [['invoice'], ['invoices'], ['dashboard']];

export function InvoiceDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const notify = useToast();
  const q = useQuery({ queryKey: ['invoice', id], queryFn: () => api.get<InvoiceDetail>(`/api/invoices/${id}`) });

  const post = (path: string, body?: Record<string, unknown>) => api.post<Message>(`/api/invoices/${id}/${path}`, body);
  const cancel = useAction(() => post('cancel'), { invalidate: INVALIDATE });
  const markSent = useAction(() => post('mark-sent'), { invalidate: INVALIDATE });
  const remove = useAction(() => api.delete<Message>(`/api/invoices/${id}`), {
    invalidate: [['invoices'], ['dashboard']],
    onSuccess: () => navigate('/admin/invoices'),
  });
  const notifyCustomer = useAction((channel: 'whatsapp' | 'email') => post('send-notification', { channel }), {
    invalidate: INVALIDATE,
    onSuccess: (res) => {
      // Same-tab navigation like the old redirect; a post-await window.open gets popup-blocked.
      if (res.redirect_url) window.location.assign(res.redirect_url);
    },
  });

  if (q.isPending) return <Loading />;
  if (q.error) return <ErrorState error={q.error} />;
  const inv = q.data;
  const editable = !['paid', 'cancelled'].includes(inv.status);

  const copyLink = async () => {
    try {
      await navigator.clipboard.writeText(inv.payment_url ?? '');
      notify('Link pembayaran disalin.');
    } catch {
      notify('Gagal menyalin link.', 'error');
    }
  };

  return (
    <>
      <PageHeader
        eyebrow="Invoice"
        title={<span className="font-mono text-2xl sm:text-3xl">{inv.invoice_number}</span>}
        back={{ to: '/admin/invoices', label: 'Invoice' }}
        actions={
          <>
            <a href={`/api/invoices/${inv.id}/pdf`} className="btn-secondary">Unduh PDF</a>
            {editable && <Link to={`/admin/invoices/${inv.id}/edit`} className="btn-secondary">Edit</Link>}
          </>
        }
      >
        <div className="mt-3 flex flex-wrap items-center gap-3 text-sm text-clay">
          <StatusBadge status={inv.status} label={inv.status_label} />
          <span>Dibuat {formatDate(inv.created_at, true)}{inv.creator && ` oleh ${inv.creator.name}`}</span>
        </div>
      </PageHeader>

      <div className="grid gap-6 lg:grid-cols-[1fr_22rem] lg:items-start">
        <div className="space-y-6">
          {/* Any unreviewed proof stays actionable, even on an invoice that lapsed meanwhile. */}
          {editable && inv.payment_proofs?.[0]?.status === 'pending' && (
            <ProofReview invoiceId={inv.id} proof={inv.payment_proofs[0]} />
          )}

          <Section title="Rincian tagihan">
            <div className="scroll-x -mx-5 sm:-mx-6">
              <table className="ledger">
                <thead><tr><th>Produk</th><th className="text-center!">Qty</th><th className="text-right!">Harga</th><th className="text-right!">Total</th></tr></thead>
                <tbody>
                  {inv.items.map((i) => (
                    <tr key={i.id}>
                      <td><p className="font-medium">{i.product_name}</p>{i.description && <p className="text-xs text-clay">{i.description}</p>}</td>
                      <td className="text-center">{i.quantity}</td>
                      <td className="text-right whitespace-nowrap">{rupiah(i.unit_price)}</td>
                      <td className="text-right whitespace-nowrap">{rupiah(i.total_price)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <dl className="mt-5 ml-auto max-w-xs space-y-1.5 text-sm">
              <div className="flex justify-between"><dt className="text-ink-soft">Subtotal</dt><dd className="tabular">{rupiah(inv.subtotal)}</dd></div>
              {inv.extras.map((e) => (
                <div key={e.id} className="flex justify-between"><dt className="text-ink-soft">{e.label}</dt><dd className="tabular">+ {rupiah(e.amount)}</dd></div>
              ))}
              {inv.discount > 0 && <div className="flex justify-between"><dt className="text-ink-soft">Diskon</dt><dd className="tabular">− {rupiah(inv.discount)}</dd></div>}
              <div className="flex items-baseline justify-between border-t border-frost-deep pt-3">
                <dt className="eyebrow">Total</dt><dd className="figure text-2xl">{rupiah(inv.total_amount)}</dd>
              </div>
            </dl>
            {inv.notes && <p className="mt-5 rounded-md bg-frost p-3 text-sm whitespace-pre-line text-ink-soft">{inv.notes}</p>}
          </Section>

          {inv.payment_proofs && inv.payment_proofs.length > 0 && (
            <Section title="Riwayat bukti transfer">
              <ul className="divide-y divide-frost-deep">
                {inv.payment_proofs.map((p) => (
                  <li key={p.id} className="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
                    <div>
                      <a href={p.file_url ?? '#'} download={p.file_name} className="link">{p.file_name}</a>
                      <p className="text-xs text-clay">
                        Diunggah {formatDate(p.created_at, true)}
                        {p.confirmed_by && ` · dikonfirmasi ${p.confirmed_by.name}`}
                      </p>
                      {p.admin_notes && <p className="mt-1 text-xs text-ink-soft">Catatan: {p.admin_notes}</p>}
                    </div>
                    <StatusBadge status={p.status} label={{ pending: 'Menunggu', confirmed: 'Dikonfirmasi', rejected: 'Ditolak' }[p.status]} />
                  </li>
                ))}
              </ul>
            </Section>
          )}
        </div>

        <aside className="space-y-6">
          <Section title="Customer">
            <DefinitionList rows={[
              ['Nama', inv.customer_name], ['Telepon', inv.customer_phone], ['Email', inv.customer_email],
              ['Alamat', inv.customer_address], ['Jatuh tempo', inv.due_date && formatDate(inv.due_date)],
              ['Metode', inv.payment_method && (inv.payment_method === 'transfer' ? 'Transfer Bank' : 'QRIS')],
              ['Lunas', inv.paid_at && formatDate(inv.paid_at, true)],
            ]} />
          </Section>

          <section className="panel-frost p-5">
            <p className="eyebrow">Link pembayaran customer</p>
            <p className="mt-2 font-mono text-xs break-all text-ink-soft">{inv.payment_url}</p>
            <div className="mt-4 grid gap-2">
              <button className="btn-secondary" onClick={copyLink}>Salin link</button>
              <button className="btn-moss" disabled={!inv.customer_phone || notifyCustomer.isPending}
                title={inv.customer_phone ? undefined : 'Nomor telepon customer belum diisi'}
                onClick={() => notifyCustomer.mutate('whatsapp')}>Kirim via WhatsApp</button>
              <button className="btn-secondary" disabled={!inv.customer_email || notifyCustomer.isPending}
                title={inv.customer_email ? undefined : 'Email customer belum diisi'}
                onClick={() => notifyCustomer.mutate('email')}>Kirim via email</button>
            </div>
          </section>

          {(editable || inv.status !== 'paid') && (
            <section className="panel p-5">
              <p className="eyebrow">Tindakan</p>
              <div className="mt-3 grid gap-2">
                {inv.status === 'draft' && (
                  <button className="btn-primary" disabled={markSent.isPending} onClick={() => markSent.mutate()}>Tandai terkirim</button>
                )}
                {inv.status !== 'cancelled' && (
                  <button className="btn-danger" disabled={cancel.isPending}
                    onClick={() => confirm('Batalkan invoice ini?') && cancel.mutate()}>Batalkan invoice</button>
                )}
                <button className="btn-danger" disabled={remove.isPending}
                  onClick={() => confirm('Hapus invoice ini secara permanen?') && remove.mutate()}>Hapus invoice</button>
              </div>
            </section>
          )}
        </aside>
      </div>
    </>
  );
}

function ProofReview({ invoiceId, proof }: { invoiceId: number; proof: PaymentProof }) {
  const [notes, setNotes] = useState('');
  const decide = useAction(
    (action: 'confirm' | 'reject') =>
      api.post<Message>(`/api/invoices/${invoiceId}/confirm-payment`, { action, admin_notes: notes || null }),
    { invalidate: INVALIDATE },
  );

  return (
    <section className="animate-rise rounded-lg border border-amber/40 bg-[#fbf3e8] p-5 sm:p-6">
      <p className="eyebrow-accent">Perlu verifikasi</p>
      <h2 className="mt-1 text-xl">Bukti transfer dari customer</h2>
      <p className="mt-1 text-sm text-ink-soft">Diunggah {formatDate(proof.created_at, true)} · {proof.file_name}</p>

      <div className="mt-4 overflow-hidden rounded-md border border-frost-deep bg-white">
        {proof.is_pdf ? (
          <div className="flex items-center justify-between gap-3 p-4 text-sm">
            <span>Dokumen PDF</span>
            <a className="btn-secondary btn-sm" href={proof.file_url ?? '#'} download={proof.file_name}>Unduh PDF</a>
          </div>
        ) : (
          <a href={proof.file_url ?? '#'} target="_blank" rel="noreferrer">
            <img src={proof.file_url ?? ''} alt="Bukti transfer" className="max-h-[28rem] w-full object-contain" />
          </a>
        )}
      </div>

      <label className="mt-4 block">
        <span className="label">Catatan admin (opsional)</span>
        <textarea className="input" rows={2} value={notes} onChange={(e) => setNotes(e.target.value)}
          placeholder="Wajib diisi jika menolak, agar customer tahu alasannya" />
      </label>
      <div className="mt-4 flex flex-wrap gap-2">
        <button className="btn-moss" disabled={decide.isPending} onClick={() => decide.mutate('confirm')}>✓ Konfirmasi lunas</button>
        <button className="btn-danger" disabled={decide.isPending}
          onClick={() => confirm('Tolak bukti ini? Customer perlu upload ulang.') && decide.mutate('reject')}>Tolak bukti</button>
      </div>
    </section>
  );
}
