import { useQuery } from '@tanstack/react-query';
import { useState, type FormEvent } from 'react';
import { useNavigate, useParams } from 'react-router';

import { ErrorState, Field, Loading, PageHeader, Section } from '../../components/ui/primitives';
import { api, type Message } from '../../lib/api';
import { addDays, blankToNull, formatDate, money, todayIso } from '../../lib/format';
import { fieldErrors, useAction } from '../../lib/hooks';
import type { Buyer, Product, QuotationDetail } from '../../lib/types';
import { BuyerPicker, ProductPicker } from './Pickers';
import { CountrySelect, useTradeMeta } from './TradeFields';

const BUYER_KEYS = ['company_name', 'contact_person', 'email', 'phone', 'address', 'city', 'state_province',
  'postal_code', 'country_code', 'tax_vat_number'] as const;
const TERM_KEYS = ['quotation_date', 'validity_days', 'currency', 'incoterm', 'incoterm_place', 'destination_country_code',
  'port_of_loading', 'port_of_discharge', 'final_destination', 'shipping_method', 'production_lead_time',
  'estimated_shipment', 'payment_terms', 'moq', 'country_of_origin', 'discount_amount', 'freight_amount',
  'insurance_amount', 'other_charges_label', 'other_charges_amount', 'tax_label', 'tax_rate', 'tax_amount', 'notes'] as const;
const ITEM_KEYS = ['product_name', 'species', 'grade', 'size', 'weight', 'moisture', 'packaging', 'condition', 'aroma',
  'description', 'hs_code', 'country_of_origin', 'quantity', 'unit', 'unit_price'] as const;
const SPEC_FIELDS = [['species', 'Species'], ['grade', 'Grade'], ['size', 'Size'], ['weight', 'Weight'], ['moisture', 'Moisture'],
  ['packaging', 'Packaging'], ['condition', 'Condition'], ['aroma', 'Aroma'], ['hs_code', 'HS code'], ['country_of_origin', 'Origin']] as const;

type BuyerState = Record<(typeof BUYER_KEYS)[number], string>;
type Terms = Record<(typeof TERM_KEYS)[number], string>;
type Item = Record<(typeof ITEM_KEYS)[number], string> & { product_id: number | null };

const str = (v: unknown) => (v === null || v === undefined ? '' : String(v));
const num = (v: string) => Number(v) || 0;
const blankItem = (): Item => ({
  ...(Object.fromEntries(ITEM_KEYS.map((k) => [k, ''])) as Record<(typeof ITEM_KEYS)[number], string>),
  product_id: null, quantity: '1', unit: 'KG', unit_price: '0',
});

function initial(q?: QuotationDetail) {
  const buyer = Object.fromEntries(BUYER_KEYS.map((k) => [k, str(q?.[`buyer_${k}`])])) as BuyerState;
  const terms = Object.fromEntries(TERM_KEYS.map((k) => [k, str(q?.[k])])) as Terms;
  if (!q) {
    Object.assign(terms, { quotation_date: todayIso(), validity_days: '14', currency: 'USD', incoterm: 'FOB', country_of_origin: 'Indonesia' });
  }
  const items: Item[] = q?.items.length
    ? q.items.map((i) => ({
      ...(Object.fromEntries(ITEM_KEYS.map((k) => [k, str(i[k])])) as Record<(typeof ITEM_KEYS)[number], string>),
      product_id: i.product_id,
    }))
    : [blankItem()];
  return { buyerId: q?.buyer_id ?? null, buyer, terms, items };
}

export function QuotationFormPage() {
  const { id } = useParams();
  const q = useQuery({ queryKey: ['quotation', id], queryFn: () => api.get<QuotationDetail>(`/api/quotations/${id}`), enabled: !!id });
  if (id && q.isPending) return <Loading />;
  if (id && q.error) return <ErrorState error={q.error} />;
  return <QuotationForm key={id ?? 'new'} quotation={q.data} />;
}

function QuotationForm({ quotation }: { quotation?: QuotationDetail }) {
  const navigate = useNavigate();
  const meta = useTradeMeta();
  const [state] = useState(() => initial(quotation));
  const [buyerId, setBuyerId] = useState<number | null>(state.buyerId);
  const [buyer, setBuyer] = useState(state.buyer);
  const [terms, setTerms] = useState(state.terms);
  const [items, setItems] = useState<Item[]>(state.items);

  const setTerm = (k: keyof Terms, v: string) => setTerms((t) => ({ ...t, [k]: v }));
  const setItem = (i: number, patch: Partial<Item>) => setItems((list) => list.map((it, j) => (j === i ? { ...it, ...patch } : it)));

  const subtotal = items.reduce((s, i) => s + num(i.quantity) * num(i.unit_price), 0);
  const rate = num(terms.tax_rate);
  // A tax rate drives the tax amount; with no rate the amount is entered by hand.
  const taxAmount = rate > 0 ? Math.round(subtotal * rate) / 100 : num(terms.tax_amount);
  const grandTotal = subtotal - num(terms.discount_amount) + num(terms.freight_amount) + num(terms.insurance_amount)
    + num(terms.other_charges_amount) + taxAmount;

  const pickBuyer = (b: Buyer) => {
    setBuyerId(b.id);
    setBuyer(Object.fromEntries(BUYER_KEYS.map((k) => [k, str(b[k])])) as BuyerState);
  };
  const pickProduct = (i: number, p: Product) => setItem(i, {
    product_id: p.id, product_name: p.name, species: str(p.species),
    grade: str(p.attributes.grade), size: str(p.attributes.size), weight: str(p.attributes.weight),
    moisture: str(p.attributes.moisture), packaging: str(p.attributes.packaging), condition: str(p.attributes.condition),
    aroma: str(p.attributes.aroma), description: str(p.attributes.description), hs_code: str(p.hs_code),
    country_of_origin: str(p.default_country_of_origin), unit: p.default_unit || 'KG',
  });

  const save = useAction(() => {
    const money2 = (v: string) => (v.trim() === '' ? null : num(v));
    const body = {
      buyer_id: buyerId,
      ...Object.fromEntries(BUYER_KEYS.map((k) => [`buyer_${k}`, blankToNull(buyer[k])])),
      ...Object.fromEntries(TERM_KEYS.map((k) => [k, blankToNull(terms[k])])),
      validity_days: num(terms.validity_days),
      discount_amount: money2(terms.discount_amount), freight_amount: money2(terms.freight_amount),
      insurance_amount: money2(terms.insurance_amount), other_charges_amount: money2(terms.other_charges_amount),
      tax_rate: money2(terms.tax_rate), tax_amount: rate > 0 ? taxAmount : money2(terms.tax_amount),
      items: items.map((it) => ({
        ...Object.fromEntries(ITEM_KEYS.map((k) => [k, blankToNull(it[k])])),
        product_id: it.product_id, quantity: num(it.quantity), unit_price: num(it.unit_price),
      })),
    };
    return quotation
      ? api.put<Message>(`/api/quotations/${quotation.id}`, body)
      : api.post<Message>('/api/quotations', body);
  }, {
    invalidate: [['quotations'], ['quotation']],
    onSuccess: (res) => navigate(`/admin/quotations/${quotation?.id ?? res.id}`),
  });
  const errors = fieldErrors(save.error);
  const submit = (e: FormEvent) => {
    e.preventDefault();
    save.mutate();
  };

  const termInput = (k: keyof Terms, label: string, props: Record<string, unknown> = {}, wide = false) => (
    <Field label={label} error={errors[k]} className={wide ? 'sm:col-span-2' : ''}>
      <input className={props.type === 'number' ? 'input-figure' : 'input'} value={terms[k]} aria-invalid={!!errors[k]}
        onChange={(e) => setTerm(k, e.target.value)} {...props} />
    </Field>
  );
  const buyerInput = (k: keyof BuyerState, label: string, props: Record<string, unknown> = {}) => (
    <Field label={label} error={errors[`buyer_${k}`]}>
      <input className="input" value={buyer[k]} aria-invalid={!!errors[`buyer_${k}`]} onChange={(e) => setBuyer({ ...buyer, [k]: e.target.value })} {...props} />
    </Field>
  );

  return (
    <form onSubmit={submit} noValidate>
      <PageHeader
        eyebrow={quotation ? `Ubah ${quotation.quotation_number}` : 'Quotation baru'}
        title={quotation ? 'Edit quotation' : 'Buat quotation ekspor'}
        back={quotation ? { to: `/admin/quotations/${quotation.id}`, label: 'Detail quotation' } : { to: '/admin/quotations', label: 'Quotation' }}
      />
      <div className="grid gap-6 xl:grid-cols-[1fr_20rem] xl:items-start">
        <div className="space-y-6">
          <Section eyebrow="01" title="Buyer" aside={buyerId && (
            <button type="button" className="text-xs text-flag hover:underline" onClick={() => setBuyerId(null)}>Lepas tautan buyer</button>
          )}>
            <BuyerPicker onPick={pickBuyer} />
            {buyerId && <p className="mt-2 text-xs text-moss">✓ Terhubung ke buyer tersimpan — negara mengikuti data buyer.</p>}
            <div className="mt-5 grid gap-4 sm:grid-cols-2">
              <div className="sm:col-span-2">{buyerInput('company_name', 'Nama perusahaan *', { required: true })}</div>
              {buyerInput('contact_person', 'Contact person')}
              {buyerInput('email', 'Email', { type: 'email' })}
              {buyerInput('phone', 'Telepon')}
              {buyerInput('tax_vat_number', 'Tax / VAT number')}
              <Field label="Alamat" className="sm:col-span-2">
                <textarea className="input" rows={2} value={buyer.address} onChange={(e) => setBuyer({ ...buyer, address: e.target.value })} />
              </Field>
              {buyerInput('city', 'Kota')}
              {buyerInput('state_province', 'Provinsi / state')}
              {buyerInput('postal_code', 'Kode pos')}
              <Field label="Negara buyer" error={errors.buyer_country_code}>
                <CountrySelect value={buyer.country_code} onChange={(v) => setBuyer({ ...buyer, country_code: v })} />
              </Field>
            </div>
          </Section>

          <Section eyebrow="02" title="Trade terms">
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              {termInput('quotation_date', 'Tanggal *', { type: 'date' })}
              <Field label="Berlaku (hari) *" error={errors.validity_days}
                hint={terms.quotation_date ? `s/d ${formatDate(addDays(terms.quotation_date, num(terms.validity_days)))}` : undefined}>
                <input className="input-figure" type="number" min={1} max={365} value={terms.validity_days} onChange={(e) => setTerm('validity_days', e.target.value)} />
              </Field>
              <Field label="Currency *" error={errors.currency}>
                <select className="input" value={terms.currency} onChange={(e) => setTerm('currency', e.target.value)}>
                  {meta.data?.currencies.map((c) => <option key={c.code} value={c.code}>{c.code} — {c.name}</option>)}
                </select>
              </Field>
              <Field label="Incoterm *" error={errors.incoterm}>
                <select className="input" value={terms.incoterm} onChange={(e) => setTerm('incoterm', e.target.value)}>
                  {meta.data?.incoterms.map((t) => <option key={t}>{t}</option>)}
                </select>
              </Field>
              {termInput('incoterm_place', 'Tempat incoterm', { placeholder: 'mis. Surabaya' })}
              <Field label="Negara tujuan" error={errors.destination_country_code}>
                <CountrySelect value={terms.destination_country_code} onChange={(v) => setTerm('destination_country_code', v)} />
              </Field>
              {termInput('country_of_origin', 'Country of origin')}
              {termInput('moq', 'MOQ')}
              {termInput('payment_terms', 'Payment terms', { placeholder: 'mis. 50% T/T in advance, 50% against B/L' }, true)}
            </div>
          </Section>

          <Section eyebrow="03" title="Shipping">
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              {termInput('port_of_loading', 'Port of loading')}
              {termInput('port_of_discharge', 'Port of discharge')}
              {termInput('final_destination', 'Final destination')}
              {termInput('shipping_method', 'Shipping method', { placeholder: 'Sea / Air / Courier' })}
              {termInput('production_lead_time', 'Production lead time')}
              {termInput('estimated_shipment', 'Estimated shipment')}
            </div>
          </Section>

          <Section eyebrow="04" title="Item" aside={
            <button type="button" className="btn-secondary btn-sm" onClick={() => setItems([...items, blankItem()])}>+ Item</button>
          }>
            <div className="space-y-4">
              {items.map((it, i) => (
                <div key={i} className="rounded-md border border-frost-deep bg-frost/40 p-4">
                  <div className="mb-3 flex items-center justify-between">
                    <span className="eyebrow">Item {String(i + 1).padStart(2, '0')}</span>
                    <div className="flex gap-3 text-xs">
                      <button type="button" className="link" onClick={() => setItems([...items.slice(0, i + 1), { ...it }, ...items.slice(i + 1)])}>Duplikat</button>
                      <button type="button" className="text-flag hover:underline disabled:opacity-30" disabled={items.length === 1}
                        onClick={() => setItems(items.filter((_, j) => j !== i))}>Hapus</button>
                    </div>
                  </div>
                  <ProductPicker value={it.product_name} onInput={(v) => setItem(i, { product_name: v, product_id: null })} onPick={(p) => pickProduct(i, p)} />
                  {errors[`items.${i}.product_name`] && <p className="mt-1 text-xs text-flag">{errors[`items.${i}.product_name`]}</p>}
                  <div className="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    {SPEC_FIELDS.map(([k, label]) => (
                      <Field key={k} label={label}>
                        <input className="input text-sm" value={it[k]} onChange={(e) => setItem(i, { [k]: e.target.value })} />
                      </Field>
                    ))}
                  </div>
                  <Field label="Deskripsi" className="mt-3">
                    <textarea className="input text-sm" rows={2} value={it.description} onChange={(e) => setItem(i, { description: e.target.value })} />
                  </Field>
                  <div className="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-[8rem_6rem_10rem_1fr] sm:items-end">
                    <Field label="Qty *"><input className="input-figure" type="number" step="0.01" min={0.01} value={it.quantity} onChange={(e) => setItem(i, { quantity: e.target.value })} /></Field>
                    <Field label="Unit"><input className="input" value={it.unit} onChange={(e) => setItem(i, { unit: e.target.value })} /></Field>
                    <Field label={`Unit price (${terms.currency})`}><input className="input-figure" type="number" step="0.01" min={0} value={it.unit_price} onChange={(e) => setItem(i, { unit_price: e.target.value })} /></Field>
                    <p className="figure pb-2 text-right text-lg">{terms.currency} {money(num(it.quantity) * num(it.unit_price))}</p>
                  </div>
                </div>
              ))}
            </div>
          </Section>

          <Section eyebrow="05" title="Biaya, pajak & catatan">
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              {termInput('discount_amount', 'Discount', { type: 'number', min: 0, step: '0.01' })}
              {termInput('freight_amount', 'Freight', { type: 'number', min: 0, step: '0.01' })}
              {termInput('insurance_amount', 'Insurance', { type: 'number', min: 0, step: '0.01' })}
              {termInput('other_charges_label', 'Label biaya lain', { placeholder: 'Other charges' })}
              {termInput('other_charges_amount', 'Jumlah biaya lain', { type: 'number', min: 0, step: '0.01' })}
              <span className="hidden lg:block" />
              {termInput('tax_label', 'Label pajak', { placeholder: 'Tax / VAT' })}
              {termInput('tax_rate', 'Tarif pajak (%)', { type: 'number', min: 0, max: 100, step: '0.01' })}
              <Field label="Jumlah pajak" hint={rate > 0 ? 'Dihitung otomatis dari tarif' : undefined}>
                <input className="input-figure" type="number" min={0} step="0.01" disabled={rate > 0}
                  value={rate > 0 ? taxAmount.toFixed(2) : terms.tax_amount} onChange={(e) => setTerm('tax_amount', e.target.value)} />
              </Field>
              <Field label="Catatan" className="sm:col-span-2 lg:col-span-3">
                <textarea className="input" rows={3} value={terms.notes} onChange={(e) => setTerm('notes', e.target.value)} />
              </Field>
            </div>
          </Section>
        </div>

        <aside className="sticky top-6 rounded-lg bg-ink p-5 text-paper">
          <p className="font-mono text-[0.7rem] tracking-[0.14em] text-gold uppercase">Ringkasan · {terms.incoterm} {terms.currency}</p>
          <dl className="mt-4 space-y-2 text-sm">
            <div className="flex justify-between"><dt className="text-paper/60">Subtotal</dt><dd className="tabular">{money(subtotal)}</dd></div>
            {num(terms.discount_amount) > 0 && <div className="flex justify-between"><dt className="text-paper/60">Discount</dt><dd className="tabular">− {money(num(terms.discount_amount))}</dd></div>}
            {num(terms.freight_amount) + num(terms.insurance_amount) + num(terms.other_charges_amount) > 0 && (
              <div className="flex justify-between"><dt className="text-paper/60">Biaya kirim & lain</dt>
                <dd className="tabular">+ {money(num(terms.freight_amount) + num(terms.insurance_amount) + num(terms.other_charges_amount))}</dd></div>
            )}
            {taxAmount > 0 && <div className="flex justify-between"><dt className="text-paper/60">Pajak</dt><dd className="tabular">+ {money(taxAmount)}</dd></div>}
          </dl>
          <div className="mt-4 border-t border-paper/15 pt-4">
            <p className="font-mono text-[0.7rem] tracking-[0.14em] text-paper/55 uppercase">Grand total</p>
            <p className="figure mt-1 text-3xl break-all">{terms.currency} {money(grandTotal)}</p>
          </div>
          <button className="btn mt-6 w-full bg-gold py-3 text-ink hover:bg-paper" disabled={save.isPending}>
            {save.isPending ? 'Menyimpan…' : quotation ? 'Simpan perubahan' : 'Buat quotation'}
          </button>
          <p className="mt-3 text-xs text-paper/50">Total dihitung ulang di server. Quotation baru berstatus Draft.</p>
        </aside>
      </div>
    </form>
  );
}
