import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState, type FormEvent, type ReactNode } from 'react';
import { useParams } from 'react-router';

import { Loading } from '../../components/ui/primitives';
import { api, ApiError, type Message } from '../../lib/api';
import { formatDate, rupiah } from '../../lib/format';
import { compressImage, formatBytes, MAX_UPLOAD_BYTES, proofKind, UNSUPPORTED_MESSAGE } from '../../lib/proofFile';
import type { InvoiceDetail, Shipment } from '../../lib/types';

type PaymentView = {
  view: 'show' | 'paid' | 'cancelled' | 'expired';
  invoice: InvoiceDetail;
  transfer_account: { account_name: string; bank: string; account_number: string };
};

export function PaymentPage() {
  const { token = '' } = useParams();
  const q = useQuery({
    queryKey: ['pay', token],
    queryFn: () => api.get<PaymentView>(`/api/pay/${token}`),
    retry: false,
  });

  return (
    <div className="min-h-dvh bg-paper">
      <header className="border-b border-frost-deep">
        <div className="mx-auto flex max-w-2xl items-center gap-3 px-4 py-5">
          <img src="/images/logo/logo.webp" alt="Vanilla Royal" width={120} height={40} className="h-10 w-auto" />
          <span className="ml-auto font-mono text-[0.7rem] tracking-[0.14em] text-clay uppercase">Pembayaran aman</span>
        </div>
      </header>
      <main className="mx-auto max-w-2xl px-4 py-8 sm:py-12">
        {q.isPending ? <Loading label="Memuat invoice…" /> : q.error ? (
          <Notice tone="flag" title="Invoice tidak ditemukan">
            {q.error instanceof ApiError && q.error.status === 404
              ? 'Link pembayaran ini tidak valid. Silakan hubungi Vanilla Royal untuk link yang benar.'
              : q.error.message}
          </Notice>
        ) : (
          <PaymentContent token={token} data={q.data} />
        )}
      </main>
      <footer className="mx-auto max-w-2xl px-4 pb-10 text-center font-mono text-[0.68rem] tracking-[0.12em] text-clay uppercase">
        Vanilla Royal · PT Coffee Nation Prosperity · info@vanillaroyal.id
      </footer>
    </div>
  );
}

function PaymentContent({ token, data }: { token: string; data: PaymentView }) {
  const { invoice, view } = data;
  return (
    <div className="space-y-6">
      {view === 'paid' && (
        <Notice tone="moss" title="Pembayaran lunas">
          Terima kasih! Pembayaran Anda telah dikonfirmasi{invoice.paid_at && ` pada ${formatDate(invoice.paid_at, true)}`}.
          <a href={`/pay/${token}/invoice.pdf`} className="btn-moss mt-4 flex w-full sm:w-auto sm:inline-flex">Unduh invoice lunas (PDF)</a>
        </Notice>
      )}
      {view === 'paid' && <ShipmentCard shipment={invoice.shipment} />}
      {view === 'cancelled' && (
        <Notice tone="flag" title="Invoice dibatalkan">
          Invoice ini telah dibatalkan. Hubungi Vanilla Royal jika menurut Anda ini keliru.
        </Notice>
      )}
      {view === 'expired' && (
        <Notice tone="clay" title="Invoice kadaluarsa">
          Batas waktu pembayaran{invoice.due_date && ` (${formatDate(invoice.due_date)})`} telah lewat. Silakan hubungi Vanilla Royal untuk invoice baru.
        </Notice>
      )}

      <InvoiceCard invoice={invoice} />

      {view === 'show' && <PaymentSteps token={token} data={data} />}
    </div>
  );
}

function InvoiceCard({ invoice }: { invoice: InvoiceDetail }) {
  return (
    <article className="animate-rise panel overflow-hidden">
      <div className="flex flex-wrap items-start justify-between gap-4 bg-ink p-6 text-paper">
        <div>
          <p className="font-mono text-[0.7rem] tracking-[0.14em] text-gold uppercase">Invoice</p>
          <p className="mt-1 font-mono text-lg">{invoice.invoice_number}</p>
          <p className="mt-3 text-sm text-paper/70">Untuk <span className="text-paper">{invoice.customer_name}</span></p>
        </div>
        <div className="text-right">
          <p className="font-mono text-[0.7rem] tracking-[0.14em] text-paper/55 uppercase">Total tagihan</p>
          <p className="figure mt-1 text-3xl sm:text-4xl">{rupiah(invoice.total_amount)}</p>
          {invoice.due_date && <p className="mt-1 text-xs text-paper/60">Jatuh tempo {formatDate(invoice.due_date)}</p>}
        </div>
      </div>
      <div className="p-5 sm:p-6">
        <ul className="divide-y divide-frost-deep text-sm">
          {invoice.items.map((i) => (
            <li key={i.id} className="flex justify-between gap-4 py-2.5">
              <div>
                <p className="font-medium">{i.product_name}</p>
                <p className="text-xs text-clay">{i.quantity} × {rupiah(i.unit_price)}{i.description && ` · ${i.description}`}</p>
              </div>
              <span className="tabular whitespace-nowrap">{rupiah(i.total_price)}</span>
            </li>
          ))}
        </ul>
        <dl className="mt-3 space-y-1.5 border-t border-frost-deep pt-3 text-sm">
          <div className="flex justify-between"><dt className="text-ink-soft">Subtotal</dt><dd className="tabular">{rupiah(invoice.subtotal)}</dd></div>
          {invoice.extras.map((e) => (
            <div key={e.id} className="flex justify-between"><dt className="text-ink-soft">{e.label}</dt><dd className="tabular">+ {rupiah(e.amount)}</dd></div>
          ))}
          {invoice.discount > 0 && <div className="flex justify-between"><dt className="text-ink-soft">Diskon</dt><dd className="tabular">− {rupiah(invoice.discount)}</dd></div>}
          <div className="flex justify-between pt-1 font-semibold"><dt>Total</dt><dd className="tabular">{rupiah(invoice.total_amount)}</dd></div>
        </dl>
        {invoice.notes && <p className="mt-4 rounded-md bg-frost p-3 text-sm whitespace-pre-line text-ink-soft">{invoice.notes}</p>}
      </div>
    </article>
  );
}

function PaymentSteps({ token, data }: { token: string; data: PaymentView }) {
  const qc = useQueryClient();
  const [flash, setFlash] = useState<{ tone: 'moss' | 'flag' | 'sky'; text: string } | null>(null);
  const { invoice, transfer_account: acct } = data;
  const rejected = invoice.latest_proof?.status === 'rejected' ? invoice.latest_proof : null;

  const run = useMutation({
    mutationFn: (fn: () => Promise<Message>) => fn(),
    onSuccess: (res) => {
      setFlash({ tone: 'moss', text: res.message });
      qc.invalidateQueries({ queryKey: ['pay', token] });
    },
    onError: (err) => setFlash({ tone: 'flag', text: err instanceof Error ? err.message : 'Terjadi kesalahan.' }),
  });

  const flashBox = flash && (
    <p role={flash.tone === 'flag' ? 'alert' : 'status'}
      className={`rounded-md border-l-4 px-4 py-3 text-sm ${flash.tone === 'flag' ? 'border-flag bg-flag-soft text-flag' : 'border-moss bg-moss-soft text-moss'}`}>
      {flash.text}
    </p>
  );

  if (invoice.status === 'pending_confirmation') {
    return (
      <>
        {flashBox}
        <Notice tone="amber" title="Menunggu konfirmasi">
          Bukti transfer Anda sedang diperiksa admin. Kami akan segera mengkonfirmasi.
        </Notice>
      </>
    );
  }

  if (!invoice.payment_method) {
    return (
      <section className="panel p-5 sm:p-6">
        {flashBox}
        <p className="eyebrow-accent">Langkah 1</p>
        <h2 className="mt-1 text-xl">Pilih metode pembayaran</h2>
        <button
          className="group mt-5 flex w-full items-center gap-4 rounded-lg border-2 border-frost-deep p-4 text-left transition-colors hover:border-amber focus-visible:border-amber"
          disabled={run.isPending}
          onClick={() => run.mutate(() => api.post<Message>(`/api/pay/${token}/method`, { payment_method: 'transfer' }))}
        >
          <span aria-hidden className="grid size-11 place-items-center rounded-md bg-frost font-display text-lg text-amber-deep">⇄</span>
          <span className="flex-1">
            <span className="block font-medium">Transfer Bank</span>
            <span className="block text-xs text-clay">Transfer ke rekening kami, lalu upload bukti transfer</span>
          </span>
          <span className="text-clay transition-transform group-hover:translate-x-1 group-hover:text-amber">→</span>
        </button>
      </section>
    );
  }

  return (
    <section className="panel space-y-6 p-5 sm:p-6">
      {flashBox}
      <div className="flex items-start justify-between gap-3">
        <div>
          <p className="eyebrow-accent">Langkah 2</p>
          <h2 className="mt-1 text-xl">Detail transfer bank</h2>
        </div>
        <button className="btn-secondary btn-sm" disabled={run.isPending}
          onClick={() => run.mutate(() => api.post<Message>(`/api/pay/${token}/reset-method`))}>
          ← Ganti metode
        </button>
      </div>

      <dl className="divide-y divide-frost-deep rounded-md bg-frost px-4 text-sm">
        {([['Atas nama', acct.account_name], ['Bank', acct.bank], ['No. rekening', acct.account_number]] as const).map(([k, v]) => (
          <div key={k} className="flex items-center justify-between gap-3 py-3">
            <dt className="eyebrow">{k}</dt>
            <dd className={k === 'No. rekening' ? 'flex items-center gap-2 font-mono text-base' : 'font-medium'}>
              {v}
              {k === 'No. rekening' && <CopyButton value={v} />}
            </dd>
          </div>
        ))}
        <div className="flex items-center justify-between gap-3 py-3">
          <dt className="eyebrow text-ink">Jumlah transfer</dt>
          <dd className="figure text-xl text-amber-deep">{rupiah(invoice.total_amount)}</dd>
        </div>
      </dl>

      {rejected && (
        <Notice tone="flag" title="Bukti transfer sebelumnya ditolak">
          {rejected.admin_notes ? `Alasan: ${rejected.admin_notes}` : 'Silakan upload ulang bukti transfer yang valid.'}
        </Notice>
      )}

      <ProofUpload token={token} busy={run.isPending}
        onSubmit={(fd) => run.mutate(() => api.post<Message>(`/api/pay/${token}/proof`, fd))}
        onInvalid={(text) => setFlash({ tone: 'flag', text })} />
    </section>
  );
}

type Picked = { file: File; originalSize: number };

function ProofUpload({ token, busy, onSubmit, onInvalid }: {
  token: string; busy: boolean; onSubmit: (fd: FormData) => void; onInvalid: (text: string) => void;
}) {
  const [picked, setPicked] = useState<Picked | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [preparing, setPreparing] = useState(false);

  const choose = async (input: HTMLInputElement) => {
    const file = input.files?.[0];
    setPicked(null);
    setError(null);
    if (!file) return;
    const kind = proofKind(file);
    if (kind === 'unsupported') {
      input.value = ''; // let the customer pick again straight away
      return setError(UNSUPPORTED_MESSAGE);
    }
    setPreparing(true);
    const ready = kind === 'image' ? await compressImage(file) : file;
    setPreparing(false);
    if (ready.size > MAX_UPLOAD_BYTES) {
      input.value = '';
      return setError(
        kind === 'pdf'
          ? `Ukuran PDF terlalu besar (${formatBytes(ready.size)}). Maksimal 4 MB — mohon kirim screenshot bukti transfer sebagai gambar.`
          : `Ukuran file terlalu besar (${formatBytes(ready.size)}). Maksimal 4 MB.`,
      );
    }
    setPicked({ file: ready, originalSize: file.size });
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (!picked) return onInvalid('Pilih file bukti transfer terlebih dahulu.');
    const fd = new FormData();
    fd.append('proof', picked.file);
    onSubmit(fd);
  };

  const shrunk = picked && picked.file.size < picked.originalSize;

  return (
    <form onSubmit={submit} className="border-t border-frost-deep pt-6">
      <h3 className="text-lg">Upload bukti transfer</h3>
      <label htmlFor={`proof-${token}`}
        className={`mt-3 flex cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed bg-white px-4 py-8 text-center transition-colors hover:border-amber ${error ? 'border-flag' : 'border-frost-deep'}`}>
        <span className="font-medium break-all">
          {preparing ? 'Mengoptimalkan ukuran file…' : picked ? picked.file.name : 'Pilih foto atau PDF'}
        </span>
        <span className="text-xs text-clay">
          {picked
            ? shrunk
              ? `Dikompres otomatis: ${formatBytes(picked.originalSize)} → ${formatBytes(picked.file.size)}`
              : formatBytes(picked.file.size)
            : 'Gambar (JPG, PNG, HEIC, WebP, dll.) atau PDF · otomatis dikompres'}
        </span>
        <input id={`proof-${token}`} type="file" accept="image/*,.heic,.heif,application/pdf,.pdf" className="sr-only"
          aria-invalid={!!error} aria-describedby={error ? `proof-error-${token}` : undefined}
          onChange={(e) => void choose(e.currentTarget)} />
      </label>
      {error && (
        <p id={`proof-error-${token}`} role="alert" className="mt-3 rounded-md border-l-4 border-flag bg-flag-soft px-4 py-3 text-sm text-flag">
          {error}
        </p>
      )}
      <button className="btn-primary mt-4 w-full py-3" disabled={busy || preparing || !picked}>
        {busy ? 'Mengirim…' : 'Kirim bukti transfer'}
      </button>
    </form>
  );
}

function ShipmentCard({ shipment }: { shipment: Shipment | null }) {
  if (!shipment) {
    return (
      <section className="panel flex items-center gap-4 p-5">
        <span aria-hidden className="grid size-11 shrink-0 place-items-center rounded-md bg-frost text-xl">📦</span>
        <div>
          <p className="eyebrow">Pengiriman</p>
          <p className="mt-0.5 text-sm text-ink-soft">Pesanan Anda sedang kami siapkan. Nomor resi akan muncul di halaman ini setelah paket dikirim.</p>
        </div>
      </section>
    );
  }
  return (
    <section className="animate-rise panel overflow-hidden">
      <div className="flex items-center justify-between gap-3 border-b border-frost-deep bg-frost px-5 py-3">
        <p className="eyebrow-accent">Pesanan dikirim</p>
        {shipment.shipped_at && <span className="text-xs text-clay">{formatDate(shipment.shipped_at, true)}</span>}
      </div>
      <div className="p-5">
        <dl className="grid grid-cols-[auto_1fr] items-center gap-x-6 gap-y-3 text-sm">
          <dt className="eyebrow">Kurir</dt>
          <dd className="font-medium">{shipment.courier_name}</dd>
          <dt className="eyebrow">No. resi</dt>
          <dd className="flex flex-wrap items-center gap-3 font-mono text-base">
            {shipment.tracking_number}
            <CopyButton value={shipment.tracking_number} />
          </dd>
        </dl>
        <a href={shipment.tracking_url} target="_blank" rel="noreferrer" className="btn-primary mt-5 w-full py-3">
          Lacak paket di {shipment.courier_name} ↗
        </a>
        <p className="mt-2 text-center text-xs text-clay">Salin nomor resi, lalu tempel di halaman pelacakan {shipment.courier_name}.</p>
      </div>
    </section>
  );
}

function CopyButton({ value }: { value: string }) {
  const [copied, setCopied] = useState(false);
  return (
    <button type="button" className="font-mono text-[0.68rem] tracking-wider text-amber-deep uppercase hover:underline"
      onClick={() => navigator.clipboard.writeText(value).then(() => { setCopied(true); setTimeout(() => setCopied(false), 1500); })}>
      {copied ? 'Disalin' : 'Salin'}
    </button>
  );
}

const NOTICE_TONE = {
  moss: 'border-moss bg-moss-soft text-moss',
  flag: 'border-flag bg-flag-soft text-flag',
  amber: 'border-amber bg-[#fbf3e8] text-amber-deep',
  clay: 'border-clay bg-frost text-ink-soft',
  sky: 'border-sky bg-sky-soft text-sky',
};

function Notice({ tone, title, children }: { tone: keyof typeof NOTICE_TONE; title: string; children: ReactNode }) {
  return (
    <section className={`animate-rise rounded-lg border-l-4 p-5 ${NOTICE_TONE[tone]}`}>
      <h2 className="text-lg">{title}</h2>
      <div className="mt-1 text-sm text-ink-soft">{children}</div>
    </section>
  );
}
