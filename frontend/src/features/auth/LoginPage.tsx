import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useState, type FormEvent } from 'react';
import { Navigate, useLocation, useNavigate } from 'react-router';

import { useMe } from '../../components/layout/AdminLayout';
import { Field } from '../../components/ui/primitives';
import { api, ApiError } from '../../lib/api';
import type { Admin } from '../../lib/types';

export function LoginPage() {
  const me = useMe();
  const qc = useQueryClient();
  const navigate = useNavigate();
  const location = useLocation();
  const [form, setForm] = useState({ email: '', password: '', remember: false });

  const login = useMutation({
    mutationFn: () => api.post<{ admin: Admin }>('/api/auth/login', form),
    onSuccess: (data) => {
      qc.setQueryData(['me'], data);
      const from = (location.state as { from?: string } | null)?.from;
      navigate(from ?? '/admin/dashboard', { replace: true });
    },
  });

  if (me.data) return <Navigate to="/admin/dashboard" replace />;

  const submit = (e: FormEvent) => {
    e.preventDefault();
    login.mutate();
  };
  const error = login.error instanceof ApiError ? login.error.message : null;

  return (
    <main className="grid min-h-dvh lg:grid-cols-[1.1fr_1fr]">
      <section className="relative hidden overflow-hidden bg-ink p-12 text-paper lg:flex lg:flex-col lg:justify-between">
        <div
          aria-hidden
          className="pointer-events-none absolute -right-32 -bottom-40 size-[34rem] rounded-full bg-[radial-gradient(circle,rgba(232,163,61,0.22),transparent_65%)]"
        />
        <div className="flex items-center gap-3">
          <img src="/images/logo/CleanlLogo.webp" alt="" width={44} height={44} className="size-11 object-contain" />
          <span className="font-display text-xl font-semibold">Vanilla Royal</span>
        </div>
        <div className="relative max-w-md">
          <p className="font-mono text-xs tracking-[0.16em] text-gold uppercase">Payment desk</p>
          <h1 className="mt-4 text-5xl leading-[1.05]">Setiap tagihan, tercatat rapi dari invoice sampai lunas.</h1>
          <p className="mt-6 text-paper/65">
            Invoice domestik, verifikasi bukti transfer, dan quotation ekspor dalam satu meja kerja.
          </p>
        </div>
        <p className="font-mono text-[0.7rem] tracking-[0.14em] text-paper/40 uppercase">PT Coffee Nation Prosperity</p>
      </section>

      <section className="flex items-center justify-center px-6 py-16">
        <form onSubmit={submit} className="animate-rise w-full max-w-sm" noValidate>
          <p className="eyebrow-accent">Admin</p>
          <h2 className="mt-1 text-3xl">Masuk</h2>
          <p className="mt-2 text-sm text-clay">Gunakan akun admin Vanilla Royal Anda.</p>

          {error && (
            <div role="alert" className="mt-6 rounded-md border-l-4 border-flag bg-flag-soft px-4 py-3 text-sm text-flag">
              {error}
            </div>
          )}

          <div className="mt-8 space-y-5">
            <Field label="Email">
              <input
                className="input" type="email" autoComplete="email" required autoFocus
                value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })}
              />
            </Field>
            <Field label="Password">
              <input
                className="input" type="password" autoComplete="current-password" required
                value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })}
              />
            </Field>
            <label className="flex items-center gap-2 text-sm text-ink-soft">
              <input
                type="checkbox" className="size-4 accent-amber"
                checked={form.remember} onChange={(e) => setForm({ ...form, remember: e.target.checked })}
              />
              Ingat saya
            </label>
          </div>

          <button className="btn-primary mt-8 w-full py-3" disabled={login.isPending}>
            {login.isPending ? 'Memproses…' : 'Masuk'}
          </button>
        </form>
      </section>
    </main>
  );
}
