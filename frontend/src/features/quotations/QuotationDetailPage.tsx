import { useQuery } from '@tanstack/react-query';
import { Link, useNavigate, useParams } from 'react-router';

import { DefinitionList, ErrorState, Loading, PageHeader, Section, StatusBadge } from '../../components/ui/primitives';
import { api, type Message } from '../../lib/api';
import { formatDate, money, qty } from '../../lib/format';
import { useAction } from '../../lib/hooks';
import type { QuotationDetail, QuotationItem, QuotationStatus } from '../../lib/types';
import { useTradeMeta } from './TradeFields';

const LOCKED: QuotationStatus[] = ['accepted', 'converted_to_proforma', 'cancelled'];
const SETTABLE: QuotationStatus[] = ['draft', 'sent', 'accepted', 'rejected', 'expired', 'cancelled'];

const specs = (i: QuotationItem) =>
  [i.species, i.grade, i.size, i.weight, i.moisture, i.packaging, i.condition, i.aroma,
    i.country_of_origin && `Origin: ${i.country_of_origin}`].filter(Boolean).join(' · ');

export function QuotationDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const meta = useTradeMeta();
  const q = useQuery({ queryKey: ['quotation', id], queryFn: () => api.get<QuotationDetail>(`/api/quotations/${id}`) });
  const setStatus = useAction((status: QuotationStatus) => api.post<Message>(`/api/quotations/${id}/status`, { status }), {
    invalidate: [['quotation'], ['quotations']],
  });
  const remove = useAction(() => api.delete<Message>(`/api/quotations/${id}`), {
    invalidate: [['quotations']],
    onSuccess: () => navigate('/admin/quotations'),
  });

  if (q.isPending) return <Loading />;
  if (q.error) return <ErrorState error={q.error} />;
  const qt = q.data;
  const c = qt.currency;
  const charges: [string, number | null, string][] = [
    ['Discount', qt.discount_amount, '−'], ['Freight', qt.freight_amount, '+'], ['Insurance', qt.insurance_amount, '+'],
    [qt.other_charges_label || 'Other charges', qt.other_charges_amount, '+'],
    [`${qt.tax_label || 'Tax'}${qt.tax_rate ? ` (${qty(qt.tax_rate)}%)` : ''}`, qt.tax_amount, '+'],
  ];

  return (
    <>
      <PageHeader
        eyebrow="International export quotation"
        title={<span className="font-mono text-2xl sm:text-3xl">{qt.quotation_number}</span>}
        back={{ to: '/admin/quotations', label: 'Quotation' }}
        actions={
          <>
            <a className="btn-secondary" href={`/api/quotations/${qt.id}/pdf-preview`} target="_blank" rel="noreferrer">Preview PDF</a>
            <a className="btn-primary" href={`/api/quotations/${qt.id}/pdf`}
              onClick={() => setTimeout(() => q.refetch(), 1500)}>Unduh PDF</a>
            {!LOCKED.includes(qt.status) && <Link className="btn-secondary" to={`/admin/quotations/${qt.id}/edit`}>Edit</Link>}
          </>
        }
      >
        <div className="mt-3 flex flex-wrap items-center gap-3 text-sm text-clay">
          <StatusBadge status={qt.status} label={qt.status_label} />
          <span>{formatDate(qt.quotation_date)} → berlaku s/d <span className={qt.is_expired ? 'text-flag' : ''}>{formatDate(qt.valid_until)}</span></span>
          {qt.pdf_generated_at && <span>· PDF terbit {formatDate(qt.pdf_generated_at, true)}</span>}
        </div>
      </PageHeader>

      <div className="grid gap-6 lg:grid-cols-[1fr_20rem] lg:items-start">
        <div className="space-y-6">
          <Section title="Item">
            <div className="scroll-x -mx-5 sm:-mx-6">
              <table className="ledger">
                <thead><tr><th>Product</th><th className="text-right!">Qty</th><th className="text-right!">Unit price</th><th className="text-right!">Total</th></tr></thead>
                <tbody>
                  {qt.items.map((i) => (
                    <tr key={i.id}>
                      <td>
                        <p className="font-medium">{i.product_name}</p>
                        {specs(i) && <p className="text-xs text-clay">{specs(i)}</p>}
                        {i.hs_code && <p className="font-mono text-xs text-clay">HS {i.hs_code}</p>}
                        {i.description && <p className="mt-1 text-xs text-ink-soft">{i.description}</p>}
                      </td>
                      <td className="text-right whitespace-nowrap">{qty(i.quantity)} {i.unit}</td>
                      <td className="text-right whitespace-nowrap">{money(i.unit_price)}</td>
                      <td className="text-right whitespace-nowrap">{money(i.line_total)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <dl className="mt-5 ml-auto max-w-sm space-y-1.5 text-sm">
              <div className="flex justify-between"><dt className="text-ink-soft">Subtotal</dt><dd className="tabular">{c} {money(qt.subtotal)}</dd></div>
              {charges.filter(([, v]) => v && v > 0).map(([label, v, sign]) => (
                <div key={label} className="flex justify-between"><dt className="text-ink-soft">{label}</dt><dd className="tabular">{sign} {c} {money(v)}</dd></div>
              ))}
              <div className="flex items-baseline justify-between border-t border-frost-deep pt-3">
                <dt className="eyebrow">Grand total</dt><dd className="figure text-2xl">{c} {money(qt.grand_total)}</dd>
              </div>
            </dl>
            {qt.notes && <p className="mt-5 rounded-md bg-frost p-3 text-sm whitespace-pre-line text-ink-soft">{qt.notes}</p>}
          </Section>

          <Section title="Trade & shipping">
            <DefinitionList rows={[
              ['Incoterm', `${qt.incoterm}${qt.incoterm_place ? ` ${qt.incoterm_place}` : ''}`], ['Currency', qt.currency],
              ['Payment terms', qt.payment_terms], ['MOQ', qt.moq], ['Country of origin', qt.country_of_origin],
              ['Destination', qt.destination_country], ['Port of loading', qt.port_of_loading],
              ['Port of discharge', qt.port_of_discharge], ['Final destination', qt.final_destination],
              ['Shipping method', qt.shipping_method], ['Production lead time', qt.production_lead_time],
              ['Estimated shipment', qt.estimated_shipment],
            ]} />
          </Section>
        </div>

        <aside className="space-y-6">
          <Section title="Buyer">
            <p className="font-display text-lg">{qt.buyer_company_name}</p>
            <div className="mt-3">
              <DefinitionList rows={[
                ['Kontak', qt.buyer_contact_person], ['Email', qt.buyer_email], ['Telepon', qt.buyer_phone],
                ['Alamat', [qt.buyer_address, qt.buyer_city, qt.buyer_state_province, qt.buyer_postal_code].filter(Boolean).join(', ')],
                ['Negara', qt.buyer_country], ['Tax/VAT', qt.buyer_tax_vat_number],
              ]} />
            </div>
          </Section>

          <section className="panel-frost p-5">
            <p className="eyebrow">Ubah status</p>
            <div className="mt-3 grid grid-cols-2 gap-2">
              {SETTABLE.map((s) => (
                <button key={s} className="btn-secondary btn-sm" disabled={s === qt.status || setStatus.isPending}
                  onClick={() => setStatus.mutate(s)}>
                  {meta.data?.quotation_statuses[s] ?? s}
                </button>
              ))}
            </div>
            {!['accepted', 'converted_to_proforma'].includes(qt.status) && (
              <button className="btn-danger mt-4 w-full" disabled={remove.isPending}
                onClick={() => confirm('Hapus quotation ini?') && remove.mutate()}>Hapus quotation</button>
            )}
          </section>
        </aside>
      </div>
    </>
  );
}
