import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useEffect, useState } from 'react';
import { Navigate, NavLink, Outlet, useLocation, useNavigate } from 'react-router';

import { api, ApiError } from '../../lib/api';
import type { Admin } from '../../lib/types';
import { Loading } from '../ui/primitives';

const NAV = [
  { group: 'Ringkasan', links: [{ to: '/admin/dashboard', label: 'Dashboard' }] },
  {
    group: 'Pembayaran domestik',
    links: [
      { to: '/admin/invoices', label: 'Invoice' },
      { to: '/admin/reports', label: 'Laporan' },
    ],
  },
  {
    group: 'Ekspor internasional',
    links: [
      { to: '/admin/quotations', label: 'Quotation' },
      { to: '/admin/buyers', label: 'Buyer' },
      { to: '/admin/products', label: 'Produk' },
    ],
  },
  { group: 'Pengaturan', links: [{ to: '/admin/settings/company', label: 'Perusahaan' }] },
];

export function useMe() {
  return useQuery({
    queryKey: ['me'],
    queryFn: () => api.get<{ admin: Admin }>('/api/auth/me'),
    retry: false,
    staleTime: 5 * 60_000,
  });
}

function Sidebar({ admin, onNavigate }: { admin: Admin; onNavigate: () => void }) {
  const qc = useQueryClient();
  const navigate = useNavigate();

  const logout = async () => {
    await api.post('/api/auth/logout').catch(() => undefined);
    qc.clear();
    navigate('/admin/login', { replace: true });
  };

  return (
    <div className="flex h-full flex-col bg-ink text-paper">
      <div className="flex items-center gap-3 border-b border-paper/10 px-6 py-6">
        <img src="/images/logo/CleanlLogo.webp" alt="" width={36} height={36} className="size-9 object-contain" />
        <div className="leading-tight">
          <p className="font-display text-lg font-semibold">Vanilla Royal</p>
          <p className="font-mono text-[0.66rem] tracking-[0.16em] text-gold uppercase">Payment desk</p>
        </div>
      </div>

      <nav aria-label="Navigasi admin" className="flex-1 overflow-y-auto px-3 py-5">
        {NAV.map((section) => (
          <div key={section.group} className="mb-5">
            <p className="px-3 pb-1.5 font-mono text-[0.66rem] tracking-[0.14em] text-paper/45 uppercase">
              {section.group}
            </p>
            {section.links.map((link) => (
              <NavLink
                key={link.to}
                to={link.to}
                onClick={onNavigate}
                className={({ isActive }) =>
                  `group relative flex items-center rounded-md px-3 py-2 text-sm transition-colors duration-150 ${
                    isActive ? 'bg-paper/10 text-paper' : 'text-paper/70 hover:bg-paper/5 hover:text-paper'
                  }`
                }
              >
                {({ isActive }) => (
                  <>
                    <span
                      aria-hidden
                      className={`absolute inset-y-2 left-0 w-0.5 rounded-full bg-gold transition-opacity ${isActive ? 'opacity-100' : 'opacity-0'}`}
                    />
                    {link.label}
                  </>
                )}
              </NavLink>
            ))}
          </div>
        ))}
      </nav>

      <div className="border-t border-paper/10 px-6 py-5">
        <p className="truncate text-sm">{admin.name}</p>
        <p className="truncate font-mono text-[0.7rem] text-paper/50">{admin.email}</p>
        <button onClick={logout} className="mt-3 font-mono text-[0.7rem] tracking-[0.14em] text-gold uppercase hover:text-paper">
          Keluar →
        </button>
      </div>
    </div>
  );
}

export function AdminLayout() {
  const me = useMe();
  const location = useLocation();
  const [open, setOpen] = useState(false);

  useEffect(() => setOpen(false), [location.pathname]);

  if (me.isPending) return <div className="px-8"><Loading /></div>;
  if (me.error) {
    const unauth = me.error instanceof ApiError && me.error.status === 401;
    if (unauth) return <Navigate to="/admin/login" replace state={{ from: location.pathname }} />;
    return <div className="p-8 text-flag">{me.error.message}</div>;
  }

  return (
    <div className="min-h-dvh lg:grid lg:grid-cols-[16rem_1fr]">
      <aside className="sticky top-0 hidden h-dvh lg:block">
        <Sidebar admin={me.data.admin} onNavigate={() => undefined} />
      </aside>

      <div className="flex items-center justify-between border-b border-frost-deep bg-paper px-4 py-3 lg:hidden">
        <span className="font-display text-lg font-semibold">Vanilla Royal</span>
        <button className="btn-secondary btn-sm" aria-expanded={open} aria-controls="mobile-nav" onClick={() => setOpen(true)}>
          Menu
        </button>
      </div>
      {open && (
        <div id="mobile-nav" className="fixed inset-0 z-40 lg:hidden" role="dialog" aria-modal="true">
          <button aria-label="Tutup menu" className="absolute inset-0 bg-ink/40" onClick={() => setOpen(false)} />
          <div className="animate-rise absolute inset-y-0 left-0 w-72 max-w-[85vw]">
            <Sidebar admin={me.data.admin} onNavigate={() => setOpen(false)} />
          </div>
        </div>
      )}

      <main className="min-w-0 px-4 py-8 sm:px-8 lg:px-12 lg:py-10">
        <div className="mx-auto max-w-6xl">
          <Outlet />
        </div>
      </main>
    </div>
  );
}
