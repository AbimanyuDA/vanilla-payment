import { useQuery } from '@tanstack/react-query';
import { useState, type FormEvent } from 'react';

import { ErrorState, Field, Loading, PageHeader, Section } from '../../components/ui/primitives';
import { useToast } from '../../components/ui/Toast';
import { api, ApiError, type Message } from '../../lib/api';
import { fieldErrors, useAction } from '../../lib/hooks';
import type { CompanySettings } from '../../lib/types';

type Editable = Omit<CompanySettings, 'logo_path' | 'has_uploaded_logo'>;
type Key = keyof Editable;

export function CompanySettingsPage() {
  const q = useQuery({ queryKey: ['company-settings'], queryFn: () => api.get<CompanySettings>('/api/settings/company') });
  if (q.isPending) return <Loading />;
  if (q.error) return <ErrorState error={q.error} />;
  return <SettingsForm settings={q.data} />;
}

function SettingsForm({ settings }: { settings: CompanySettings }) {
  const notify = useToast();
  const hasLogo = settings.has_uploaded_logo;
  const [form, setForm] = useState<Record<Key, string>>(
    () => Object.fromEntries(
      Object.entries(settings)
        .filter(([k]) => k !== 'logo_path' && k !== 'has_uploaded_logo')
        .map(([k, v]) => [k, v == null ? '' : String(v)]),
    ) as Record<Key, string>,
  );
  const [logo, setLogo] = useState<File | null>(null);
  const [logoVersion, setLogoVersion] = useState(0);

  const save = useAction(async () => {
    const body = Object.fromEntries(Object.entries(form).map(([k, v]) => [k, v.trim() === '' ? null : v]));
    const res = await api.put<Message>('/api/settings/company', body);
    if (logo) {
      const fd = new FormData();
      fd.append('logo', logo);
      try {
        await api.post<Message>('/api/settings/company/logo', fd);
        setLogo(null);
        setLogoVersion((v) => v + 1);
      } catch (err) {
        notify(err instanceof ApiError ? err.message : 'Gagal mengunggah logo.', 'error');
      }
    }
    return res;
  }, { invalidate: [['company-settings']] });
  const errors = fieldErrors(save.error);

  const field = (name: Key, label: string, opts: { area?: boolean; wide?: boolean; mono?: boolean; max?: number } = {}) => (
    <Field label={label} error={errors[name]} className={opts.wide ? 'sm:col-span-2' : ''}>
      {opts.area ? (
        <textarea className="input" rows={name === 'default_terms_conditions' ? 10 : 2} value={form[name]}
          onChange={(e) => setForm({ ...form, [name]: e.target.value })} />
      ) : (
        <input className={`input ${opts.mono ? 'font-mono' : ''}`} maxLength={opts.max} value={form[name]} aria-invalid={!!errors[name]}
          onChange={(e) => setForm({ ...form, [name]: e.target.value })} />
      )}
    </Field>
  );
  const submit = (e: FormEvent) => {
    e.preventDefault();
    save.mutate();
  };

  return (
    <form onSubmit={submit} noValidate className="max-w-4xl">
      <PageHeader eyebrow="Pengaturan" title="Data perusahaan">
        <p className="mt-2 max-w-xl text-sm text-clay">
          Dipakai di PDF quotation. Data dibekukan (snapshot) saat PDF diunduh, sehingga quotation yang sudah terbit tidak ikut berubah.
        </p>
      </PageHeader>
      <div className="space-y-6">
        <Section eyebrow="01" title="Identitas">
          <div className="grid gap-4 sm:grid-cols-[8rem_1fr]">
            <div>
              <span className="label">Logo</span>
              <div className="grid size-28 place-items-center overflow-hidden rounded-md border border-frost-deep bg-white p-2">
                <img
                  src={logo ? URL.createObjectURL(logo) : hasLogo || logoVersion ? `/api/settings/company/logo?v=${logoVersion}` : '/images/logo/logo.webp'}
                  alt="Logo perusahaan" className="max-h-full max-w-full object-contain" />
              </div>
              <label className="btn-secondary btn-sm mt-2 cursor-pointer">
                Ganti logo
                <input type="file" accept="image/png,image/jpeg,image/webp" className="sr-only" onChange={(e) => setLogo(e.target.files?.[0] ?? null)} />
              </label>
              <p className="mt-1 text-xs text-clay">PNG/JPG/WebP, maks 2MB</p>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              {field('company_name', 'Nama perusahaan *', { wide: true })}
              {field('address', 'Alamat', { area: true, wide: true })}
              {field('country', 'Negara *')}
              {field('email', 'Email')}
              {field('phone', 'Telepon')}
              {field('website', 'Website')}
            </div>
          </div>
        </Section>

        <Section eyebrow="02" title="Rekening untuk transfer internasional">
          <div className="grid gap-4 sm:grid-cols-2">
            {field('bank_name', 'Nama bank')}
            {field('bank_branch', 'Cabang')}
            {field('bank_account_name', 'Nama pemilik rekening')}
            {field('bank_account_number', 'Nomor rekening', { mono: true })}
            {field('bank_account_currency', 'Mata uang diterima')}
            {field('swift_bic', 'SWIFT / BIC', { mono: true, max: 11 })}
            {field('bank_address', 'Alamat bank', { area: true, wide: true })}
            {field('intermediary_bank_name', 'Bank perantara')}
            {field('intermediary_bank_swift', 'SWIFT bank perantara', { mono: true, max: 11 })}
            {field('intermediary_bank_account', 'No. rekening perantara', { mono: true })}
            <Field label="Instruksi biaya transfer" error={errors.payment_charge_instruction}>
              <select className="input" value={form.payment_charge_instruction}
                onChange={(e) => setForm({ ...form, payment_charge_instruction: e.target.value })}>
                <option value="">—</option>
                <option value="OUR">OUR — semua biaya ditanggung pengirim</option>
                <option value="SHA">SHA — biaya dibagi</option>
                <option value="BEN">BEN — biaya ditanggung penerima</option>
              </select>
            </Field>
          </div>
        </Section>

        <Section eyebrow="03" title="Penandatangan & syarat">
          <div className="grid gap-4 sm:grid-cols-2">
            {field('authorized_person_name', 'Nama penandatangan')}
            {field('authorized_person_position', 'Jabatan')}
            {field('default_terms_conditions', 'Terms & conditions default', { area: true, wide: true })}
          </div>
        </Section>

        <div className="sticky bottom-4 flex justify-end">
          <button className="btn-primary px-6 py-3 shadow-[0_10px_30px_-12px_rgba(26,18,16,0.5)]" disabled={save.isPending}>
            {save.isPending ? 'Menyimpan…' : 'Simpan pengaturan'}
          </button>
        </div>
      </div>
    </form>
  );
}
