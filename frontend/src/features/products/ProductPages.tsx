import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { useEffect, useState, type FormEvent } from 'react';
import { Link, useNavigate, useParams } from 'react-router';

import { EmptyRow, ErrorState, Field, Loading, PageHeader, Pagination, Section } from '../../components/ui/primitives';
import { api, withQuery, type Message } from '../../lib/api';
import { blankToNull } from '../../lib/format';
import { fieldErrors, useAction, useDebounce, useUrlFilters } from '../../lib/hooks';
import type { Paginated, Product } from '../../lib/types';

/** Keys the quotation form auto-fills from a product. */
export const SPEC_KEYS = ['grade', 'size', 'weight', 'moisture', 'packaging', 'condition', 'aroma', 'description'];
const CATEGORY_LABEL = { raw: 'Raw', value_added: 'Value added' };

export function ProductListPage() {
  const { values, page, set } = useUrlFilters(['search', 'category'] as const);
  const [search, setSearch] = useState(values.search);
  const debounced = useDebounce(search);
  useEffect(() => {
    if (debounced !== values.search) set({ search: debounced });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [debounced]);

  const q = useQuery({
    queryKey: ['products', values, page],
    queryFn: () => api.get<Paginated<Product>>(withQuery('/api/products', { ...values, page })),
    placeholderData: keepPreviousData,
  });
  const remove = useAction((id: number) => api.delete<Message>(`/api/products/${id}`), { invalidate: [['products']] });

  return (
    <>
      <PageHeader eyebrow="Ekspor internasional" title="Produk"
        actions={<Link to="/admin/products/new" className="btn-primary">+ Produk baru</Link>} />
      <div className="mb-4 flex flex-wrap gap-3">
        <input type="search" className="input max-w-xs" placeholder="Cari nama produk…" aria-label="Cari produk"
          value={search} onChange={(e) => setSearch(e.target.value)} />
        <select className="input w-auto" aria-label="Kategori" value={values.category} onChange={(e) => set({ category: e.target.value })}>
          <option value="">Semua kategori</option>
          <option value="raw">Raw</option>
          <option value="value_added">Value added</option>
        </select>
      </div>

      {q.isPending ? <Loading /> : q.error ? <ErrorState error={q.error} /> : (
        <div className="panel overflow-hidden">
          <div className="scroll-x">
            <table className="ledger">
              <thead><tr><th>Produk</th><th>Kategori</th><th>HS code</th><th>Spesifikasi</th><th>Status</th><th /></tr></thead>
              <tbody>
                {q.data.data.length === 0 && <EmptyRow colSpan={6}>Belum ada produk.</EmptyRow>}
                {q.data.data.map((p) => (
                  <tr key={p.id}>
                    <td><p className="font-medium">{p.name}</p>{p.species && <p className="text-xs text-clay italic">{p.species}</p>}</td>
                    <td>{CATEGORY_LABEL[p.category]}</td>
                    <td className="font-mono text-xs">{p.hs_code ?? '—'}</td>
                    <td className="max-w-xs text-xs text-ink-soft">
                      {p.attribute_list.slice(0, 3).map((a) => `${a.key}: ${a.value}`).join(' · ') || '—'}
                    </td>
                    <td>
                      <span className={`font-mono text-[0.7rem] uppercase ${p.is_active ? 'text-moss' : 'text-clay'}`}>
                        {p.is_active ? 'Aktif' : 'Nonaktif'}
                      </span>
                    </td>
                    <td className="text-right whitespace-nowrap">
                      <Link to={`/admin/products/${p.id}/edit`} className="link text-sm">Edit</Link>
                      <button className="ml-4 text-sm text-flag hover:underline"
                        onClick={() => confirm(`Hapus produk ${p.name}?`) && remove.mutate(p.id)}>Hapus</button>
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

export function ProductFormPage() {
  const { id } = useParams();
  const q = useQuery({ queryKey: ['product', id], queryFn: () => api.get<Product>(`/api/products/${id}`), enabled: !!id });
  if (id && q.isPending) return <Loading />;
  if (id && q.error) return <ErrorState error={q.error} />;
  return <ProductForm key={id ?? 'new'} product={q.data} />;
}

function ProductForm({ product }: { product?: Product }) {
  const navigate = useNavigate();
  const [form, setForm] = useState({
    name: product?.name ?? '',
    species: product?.species ?? '',
    category: product?.category ?? 'raw',
    hs_code: product?.hs_code ?? '',
    default_unit: product?.default_unit ?? 'KG',
    default_country_of_origin: product?.default_country_of_origin ?? 'Indonesia',
    is_active: product?.is_active ?? true,
    sort_order: String(product?.sort_order ?? 0),
  });
  const [attrs, setAttrs] = useState(product?.attribute_list.length ? product.attribute_list : [{ key: 'grade', value: '' }]);

  const save = useAction(() => {
    const body = {
      ...form,
      species: blankToNull(form.species),
      hs_code: blankToNull(form.hs_code),
      sort_order: Number(form.sort_order) || 0,
      attributes: attrs.filter((a) => a.key.trim() && a.value.trim()),
    };
    return product ? api.put<Message>(`/api/products/${product.id}`, body) : api.post<Message>('/api/products', body);
  }, { invalidate: [['products'], ['product']], onSuccess: () => navigate('/admin/products') });
  const errors = fieldErrors(save.error);
  const submit = (e: FormEvent) => {
    e.preventDefault();
    save.mutate();
  };

  return (
    <form onSubmit={submit} noValidate className="max-w-3xl">
      <PageHeader eyebrow="Ekspor internasional" title={product ? 'Edit produk' : 'Produk baru'} back={{ to: '/admin/products', label: 'Produk' }} />
      <div className="space-y-6">
        <Section eyebrow="01" title="Identitas produk">
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Nama produk *" error={errors.name} className="sm:col-span-2">
              <input className="input" required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
            </Field>
            <Field label="Spesies (nama latin)">
              <input className="input italic" value={form.species} onChange={(e) => setForm({ ...form, species: e.target.value })} />
            </Field>
            <Field label="Kategori *">
              <select className="input" value={form.category} onChange={(e) => setForm({ ...form, category: e.target.value as Product['category'] })}>
                <option value="raw">Raw</option>
                <option value="value_added">Value added</option>
              </select>
            </Field>
            <Field label="HS code" error={errors.hs_code}>
              <input className="input font-mono" maxLength={20} value={form.hs_code} onChange={(e) => setForm({ ...form, hs_code: e.target.value })} />
            </Field>
            <Field label="Satuan default *">
              <input className="input" maxLength={20} value={form.default_unit} onChange={(e) => setForm({ ...form, default_unit: e.target.value })} />
            </Field>
            <Field label="Negara asal default *">
              <input className="input" value={form.default_country_of_origin} onChange={(e) => setForm({ ...form, default_country_of_origin: e.target.value })} />
            </Field>
            <Field label="Urutan">
              <input className="input-figure" type="number" min={0} value={form.sort_order} onChange={(e) => setForm({ ...form, sort_order: e.target.value })} />
            </Field>
            <label className="flex items-center gap-2 text-sm sm:col-span-2">
              <input type="checkbox" className="size-4 accent-amber" checked={form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} />
              Aktif (muncul di pencarian produk quotation)
            </label>
          </div>
        </Section>

        <Section eyebrow="02" title="Spesifikasi" aside={
          <button type="button" className="btn-secondary btn-sm" onClick={() => setAttrs([...attrs, { key: '', value: '' }])}>+ Atribut</button>
        }>
          <p className="mb-4 text-sm text-clay">Atribut ini otomatis mengisi item quotation saat produk dipilih.</p>
          <datalist id="spec-keys">{SPEC_KEYS.map((k) => <option key={k} value={k} />)}</datalist>
          <div className="space-y-2">
            {attrs.map((a, i) => (
              <div key={i} className="grid grid-cols-[10rem_1fr_auto] items-center gap-2">
                <input className="input font-mono text-sm" list="spec-keys" placeholder="key" aria-label={`Kunci atribut ${i + 1}`} maxLength={100}
                  value={a.key} onChange={(e) => setAttrs(attrs.map((x, j) => (j === i ? { ...x, key: e.target.value } : x)))} />
                <input className="input" placeholder="nilai" aria-label={`Nilai atribut ${i + 1}`} maxLength={255}
                  value={a.value} onChange={(e) => setAttrs(attrs.map((x, j) => (j === i ? { ...x, value: e.target.value } : x)))} />
                <button type="button" className="text-xs text-flag hover:underline" onClick={() => setAttrs(attrs.filter((_, j) => j !== i))}>Hapus</button>
              </div>
            ))}
          </div>
        </Section>

        <div className="flex justify-end gap-2">
          <Link to="/admin/products" className="btn-secondary">Batal</Link>
          <button className="btn-primary" disabled={save.isPending}>{save.isPending ? 'Menyimpan…' : 'Simpan produk'}</button>
        </div>
      </div>
    </form>
  );
}
