import { useQuery } from '@tanstack/react-query';
import { useState, type FormEvent } from 'react';
import { useNavigate, useParams } from 'react-router';

import { ErrorState, Field, Loading, PageHeader, Section } from '../../components/ui/primitives';
import { api, type Message } from '../../lib/api';
import { blankToNull, rupiah } from '../../lib/format';
import { fieldErrors, useAction } from '../../lib/hooks';
import type { InvoiceDetail } from '../../lib/types';

type Item = { product_name: string; description: string; quantity: string; unit_price: string };
type Extra = { label: string; amount: string };
type FormState = {
  customer_name: string; customer_phone: string; customer_email: string; customer_address: string;
  due_date: string; notes: string; discount: string; items: Item[]; extras: Extra[];
};

const blankItem = (): Item => ({ product_name: '', description: '', quantity: '1', unit_price: '0' });

function fromInvoice(inv?: InvoiceDetail): FormState {
  return {
    customer_name: inv?.customer_name ?? '',
    customer_phone: inv?.customer_phone ?? '',
    customer_email: inv?.customer_email ?? '',
    customer_address: inv?.customer_address ?? '',
    due_date: inv?.due_date ?? '',
    notes: inv?.notes ?? '',
    discount: String(inv?.discount ?? 0),
    items: inv?.items.length
      ? inv.items.map((i) => ({ product_name: i.product_name, description: i.description ?? '', quantity: String(i.quantity), unit_price: String(i.unit_price) }))
      : [blankItem()],
    extras: inv?.extras.map((e) => ({ label: e.label, amount: String(e.amount) })) ?? [],
  };
}

export function InvoiceFormPage() {
  const { id } = useParams();
  const existing = useQuery({
    queryKey: ['invoice', id],
    queryFn: () => api.get<InvoiceDetail>(`/api/invoices/${id}`),
    enabled: !!id,
  });
  if (id && existing.isPending) return <Loading />;
  if (id && existing.error) return <ErrorState error={existing.error} />;
  return <InvoiceForm key={id ?? 'new'} invoice={existing.data} />;
}

function InvoiceForm({ invoice }: { invoice?: InvoiceDetail }) {
  const navigate = useNavigate();
  const [form, setForm] = useState<FormState>(() => fromInvoice(invoice));
  const set = <K extends keyof FormState>(key: K, value: FormState[K]) => setForm((f) => ({ ...f, [key]: value }));
  const setItem = (i: number, patch: Partial<Item>) =>
    set('items', form.items.map((it, idx) => (idx === i ? { ...it, ...patch } : it)));
  const setExtra = (i: number, patch: Partial<Extra>) =>
    set('extras', form.extras.map((it, idx) => (idx === i ? { ...it, ...patch } : it)));

  const subtotal = form.items.reduce((s, i) => s + (Number(i.quantity) || 0) * (Number(i.unit_price) || 0), 0);
  const extrasTotal = form.extras.reduce((s, e) => s + (Number(e.amount) || 0), 0);
  const total = subtotal - (Number(form.discount) || 0) + extrasTotal;

  const save = useAction(
    () => {
      const body = {
        customer_name: form.customer_name,
        customer_phone: blankToNull(form.customer_phone),
        customer_email: blankToNull(form.customer_email),
        customer_address: blankToNull(form.customer_address),
        due_date: blankToNull(form.due_date),
        notes: blankToNull(form.notes),
        discount: Number(form.discount) || 0,
        items: form.items.map((i) => ({ ...i, description: blankToNull(i.description), quantity: Number(i.quantity), unit_price: Number(i.unit_price) })),
        extras: form.extras.filter((e) => e.label.trim()).map((e) => ({ label: e.label, amount: Number(e.amount) || 0 })),
      };
      return invoice ? api.put<Message>(`/api/invoices/${invoice.id}`, body) : api.post<Message>('/api/invoices', body);
    },
    {
      invalidate: [['invoices'], ['invoice'], ['dashboard']],
      onSuccess: () => navigate(invoice ? `/admin/invoices/${invoice.id}` : '/admin/invoices'),
    },
  );
  const errors = fieldErrors(save.error);
  const submit = (e: FormEvent) => {
    e.preventDefault();
    save.mutate();
  };

  return (
    <form onSubmit={submit} noValidate>
      <PageHeader
        eyebrow={invoice ? `Ubah ${invoice.invoice_number}` : 'Invoice baru'}
        title={invoice ? 'Edit invoice' : 'Buat invoice'}
        back={invoice ? { to: `/admin/invoices/${invoice.id}`, label: 'Detail invoice' } : { to: '/admin/invoices', label: 'Invoice' }}
      />

      <div className="grid gap-6 lg:grid-cols-[1fr_20rem] lg:items-start">
        <div className="space-y-6">
          <Section eyebrow="01" title="Customer">
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label="Nama customer *" error={errors.customer_name} className="sm:col-span-2">
                <input className="input" required value={form.customer_name} aria-invalid={!!errors.customer_name}
                  onChange={(e) => set('customer_name', e.target.value)} />
              </Field>
              <Field label="No. WhatsApp / telepon" error={errors.customer_phone} hint="Dipakai untuk kirim link via WhatsApp">
                <input className="input" inputMode="tel" value={form.customer_phone} onChange={(e) => set('customer_phone', e.target.value)} />
              </Field>
              <Field label="Email" error={errors.customer_email}>
                <input className="input" type="email" value={form.customer_email} onChange={(e) => set('customer_email', e.target.value)} />
              </Field>
              <Field label="Alamat" className="sm:col-span-2">
                <textarea className="input" rows={2} value={form.customer_address} onChange={(e) => set('customer_address', e.target.value)} />
              </Field>
            </div>
          </Section>

          <Section eyebrow="02" title="Item" aside={
            <button type="button" className="btn-secondary btn-sm" onClick={() => set('items', [...form.items, blankItem()])}>+ Item</button>
          }>
            <div className="space-y-3">
              {form.items.map((item, i) => (
                <div key={i} className="grid gap-3 rounded-md bg-frost/60 p-3 sm:grid-cols-[1fr_5rem_9rem_auto]">
                  <div className="space-y-2">
                    <input className="input" placeholder="Nama produk *" aria-label={`Produk ${i + 1}`} value={item.product_name}
                      aria-invalid={!!errors[`items.${i}.product_name`]} onChange={(e) => setItem(i, { product_name: e.target.value })} />
                    <input className="input text-sm" placeholder="Deskripsi (opsional)" aria-label={`Deskripsi ${i + 1}`} value={item.description}
                      onChange={(e) => setItem(i, { description: e.target.value })} />
                  </div>
                  <Field label="Qty"><input className="input-figure" type="number" min={1} value={item.quantity} onChange={(e) => setItem(i, { quantity: e.target.value })} /></Field>
                  <Field label="Harga satuan"><input className="input-figure" type="number" min={0} value={item.unit_price} onChange={(e) => setItem(i, { unit_price: e.target.value })} /></Field>
                  <div className="flex flex-col items-end justify-between gap-2">
                    <span className="figure pt-6 text-sm whitespace-nowrap">{rupiah((Number(item.quantity) || 0) * (Number(item.unit_price) || 0))}</span>
                    <button type="button" className="text-xs text-flag hover:underline disabled:opacity-30" disabled={form.items.length === 1}
                      onClick={() => set('items', form.items.filter((_, idx) => idx !== i))}>Hapus</button>
                  </div>
                </div>
              ))}
            </div>
          </Section>

          <Section eyebrow="03" title="Biaya tambahan & catatan" aside={
            <button type="button" className="btn-secondary btn-sm" onClick={() => set('extras', [...form.extras, { label: '', amount: '0' }])}>+ Biaya</button>
          }>
            <div className="space-y-3">
              {form.extras.length === 0 && <p className="text-sm text-clay">Misalnya ongkos kirim atau biaya packing.</p>}
              {form.extras.map((extra, i) => (
                <div key={i} className="grid grid-cols-[1fr_9rem_auto] items-center gap-3">
                  <input className="input" placeholder="Label (mis. Ongkir)" aria-label={`Label biaya ${i + 1}`} maxLength={100} value={extra.label} onChange={(e) => setExtra(i, { label: e.target.value })} />
                  <input className="input-figure" type="number" min={0} aria-label={`Jumlah biaya ${i + 1}`} value={extra.amount} onChange={(e) => setExtra(i, { amount: e.target.value })} />
                  <button type="button" className="text-xs text-flag hover:underline" onClick={() => set('extras', form.extras.filter((_, idx) => idx !== i))}>Hapus</button>
                </div>
              ))}
              <div className="grid gap-4 pt-3 sm:grid-cols-2">
                <Field label="Jatuh tempo" error={errors.due_date}>
                  <input className="input" type="date" value={form.due_date} onChange={(e) => set('due_date', e.target.value)} />
                </Field>
                <Field label="Diskon (Rp)">
                  <input className="input-figure" type="number" min={0} value={form.discount} onChange={(e) => set('discount', e.target.value)} />
                </Field>
                <Field label="Catatan" className="sm:col-span-2">
                  <textarea className="input" rows={3} value={form.notes} onChange={(e) => set('notes', e.target.value)} />
                </Field>
              </div>
            </div>
          </Section>
        </div>

        <aside className="panel-frost sticky top-6 p-5">
          <p className="eyebrow">Ringkasan</p>
          <dl className="mt-4 space-y-2 text-sm">
            <div className="flex justify-between"><dt className="text-ink-soft">Subtotal</dt><dd className="tabular">{rupiah(subtotal)}</dd></div>
            {extrasTotal > 0 && <div className="flex justify-between"><dt className="text-ink-soft">Biaya tambahan</dt><dd className="tabular">+ {rupiah(extrasTotal)}</dd></div>}
            {Number(form.discount) > 0 && <div className="flex justify-between"><dt className="text-ink-soft">Diskon</dt><dd className="tabular">− {rupiah(Number(form.discount))}</dd></div>}
          </dl>
          <div className="mt-4 border-t border-frost-deep pt-4">
            <p className="eyebrow">Total</p>
            <p className="figure mt-1 text-3xl break-all">{rupiah(total)}</p>
          </div>
          <button className="btn-primary mt-6 w-full py-3" disabled={save.isPending}>
            {save.isPending ? 'Menyimpan…' : invoice ? 'Simpan perubahan' : 'Buat invoice'}
          </button>
          <p className="mt-3 text-xs text-clay">Total dihitung ulang di server saat disimpan.</p>
        </aside>
      </div>
    </form>
  );
}
