import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { useEffect, useState, type FormEvent } from 'react';
import { Link, useNavigate, useParams } from 'react-router';

import { EmptyRow, ErrorState, Field, Loading, PageHeader, Pagination, Section } from '../../components/ui/primitives';
import { api, withQuery, type Message } from '../../lib/api';
import { blankToNull } from '../../lib/format';
import { fieldErrors, useAction, useDebounce, useUrlFilters } from '../../lib/hooks';
import type { Buyer, Paginated } from '../../lib/types';
import { CountrySelect } from '../quotations/TradeFields';

export function BuyerListPage() {
  const { values, page, set } = useUrlFilters(['search'] as const);
  const [search, setSearch] = useState(values.search);
  const debounced = useDebounce(search);
  useEffect(() => {
    if (debounced !== values.search) set({ search: debounced });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [debounced]);

  const q = useQuery({
    queryKey: ['buyers', values, page],
    queryFn: () => api.get<Paginated<Buyer>>(withQuery('/api/buyers', { ...values, page })),
    placeholderData: keepPreviousData,
  });
  const remove = useAction((id: number) => api.delete<Message>(`/api/buyers/${id}`), { invalidate: [['buyers']] });

  return (
    <>
      <PageHeader eyebrow="Ekspor internasional" title="Buyer"
        actions={<Link to="/admin/buyers/new" className="btn-primary">+ Buyer baru</Link>} />
      <input type="search" className="input mb-4 max-w-xs" placeholder="Cari perusahaan, kontak, negara…"
        aria-label="Cari buyer" value={search} onChange={(e) => setSearch(e.target.value)} />

      {q.isPending ? <Loading /> : q.error ? <ErrorState error={q.error} /> : (
        <div className="panel overflow-hidden">
          <div className="scroll-x">
            <table className="ledger">
              <thead><tr><th>Perusahaan</th><th>Kontak</th><th>Negara</th><th className="text-center!">Quotation</th><th /></tr></thead>
              <tbody>
                {q.data.data.length === 0 && <EmptyRow colSpan={5}>Belum ada buyer.</EmptyRow>}
                {q.data.data.map((b) => (
                  <tr key={b.id}>
                    <td className="font-medium">{b.company_name}</td>
                    <td>{b.contact_person ?? '—'}{b.email && <p className="text-xs text-clay">{b.email}</p>}</td>
                    <td>{b.country ?? '—'}{b.country_code && <span className="ml-2 font-mono text-xs text-clay">{b.country_code}</span>}</td>
                    <td className="text-center">{b.quotations_count}</td>
                    <td className="text-right whitespace-nowrap">
                      <Link to={`/admin/buyers/${b.id}/edit`} className="link text-sm">Edit</Link>
                      <button className="ml-4 text-sm text-flag hover:underline"
                        onClick={() => confirm(`Hapus buyer ${b.company_name}?`) && remove.mutate(b.id)}>Hapus</button>
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

const FIELDS = ['company_name', 'contact_person', 'email', 'phone', 'address', 'city', 'state_province',
  'postal_code', 'country_code', 'tax_vat_number', 'notes'] as const;
type BuyerForm = Record<(typeof FIELDS)[number], string>;

export function BuyerFormPage() {
  const { id } = useParams();
  const q = useQuery({ queryKey: ['buyer', id], queryFn: () => api.get<Buyer>(`/api/buyers/${id}`), enabled: !!id });
  if (id && q.isPending) return <Loading />;
  if (id && q.error) return <ErrorState error={q.error} />;
  return <BuyerForm key={id ?? 'new'} buyer={q.data} />;
}

function BuyerForm({ buyer }: { buyer?: Buyer }) {
  const navigate = useNavigate();
  const [form, setForm] = useState<BuyerForm>(
    () => Object.fromEntries(FIELDS.map((f) => [f, buyer?.[f] ?? ''])) as BuyerForm,
  );
  const save = useAction(() => {
    const body = Object.fromEntries(FIELDS.map((f) => [f, blankToNull(form[f])]));
    return buyer ? api.put<Message>(`/api/buyers/${buyer.id}`, body) : api.post<Message>('/api/buyers', body);
  }, { invalidate: [['buyers'], ['buyer']], onSuccess: () => navigate('/admin/buyers') });
  const errors = fieldErrors(save.error);
  const input = (name: (typeof FIELDS)[number], label: string, props: Record<string, unknown> = {}, wide = false) => (
    <Field label={label} error={errors[name]} className={wide ? 'sm:col-span-2' : ''}>
      <input className="input" value={form[name]} aria-invalid={!!errors[name]}
        onChange={(e) => setForm({ ...form, [name]: e.target.value })} {...props} />
    </Field>
  );
  const submit = (e: FormEvent) => {
    e.preventDefault();
    save.mutate();
  };

  return (
    <form onSubmit={submit} noValidate className="max-w-3xl">
      <PageHeader eyebrow="Ekspor internasional" title={buyer ? 'Edit buyer' : 'Buyer baru'} back={{ to: '/admin/buyers', label: 'Buyer' }} />
      <div className="space-y-6">
        <Section eyebrow="01" title="Perusahaan & kontak">
          <div className="grid gap-4 sm:grid-cols-2">
            {input('company_name', 'Nama perusahaan *', { required: true }, true)}
            {input('contact_person', 'Contact person')}
            {input('email', 'Email', { type: 'email' })}
            {input('phone', 'Telepon')}
            {input('tax_vat_number', 'Tax / VAT number')}
          </div>
        </Section>
        <Section eyebrow="02" title="Alamat">
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Alamat" className="sm:col-span-2">
              <textarea className="input" rows={2} value={form.address} onChange={(e) => setForm({ ...form, address: e.target.value })} />
            </Field>
            {input('city', 'Kota')}
            {input('state_province', 'Provinsi / state')}
            {input('postal_code', 'Kode pos')}
            <Field label="Negara" error={errors.country_code}>
              <CountrySelect value={form.country_code} onChange={(v) => setForm({ ...form, country_code: v })} />
            </Field>
            <Field label="Catatan" className="sm:col-span-2">
              <textarea className="input" rows={3} value={form.notes} onChange={(e) => setForm({ ...form, notes: e.target.value })} />
            </Field>
          </div>
        </Section>
        <div className="flex justify-end gap-2">
          <Link to="/admin/buyers" className="btn-secondary">Batal</Link>
          <button className="btn-primary" disabled={save.isPending}>{save.isPending ? 'Menyimpan…' : 'Simpan buyer'}</button>
        </div>
      </div>
    </form>
  );
}
