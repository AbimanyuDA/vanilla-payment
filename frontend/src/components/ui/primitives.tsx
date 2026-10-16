import type { ReactNode } from 'react';
import { Link } from 'react-router';

/* Status colour is semantic, never decorative: moss = settled, amber = needs
   attention, flag = stopped, sky = in flight, clay = inert. */
const STATUS_TONE: Record<string, string> = {
  draft: 'bg-frost text-clay',
  sent: 'bg-sky-soft text-sky',
  pending_confirmation: 'bg-[#f7e7d2] text-amber-deep',
  paid: 'bg-moss-soft text-moss',
  accepted: 'bg-moss-soft text-moss',
  converted_to_proforma: 'bg-moss-soft text-moss',
  cancelled: 'bg-flag-soft text-flag',
  rejected: 'bg-flag-soft text-flag',
  expired: 'bg-frost-deep text-ink-soft',
  pending: 'bg-[#f7e7d2] text-amber-deep',
  confirmed: 'bg-moss-soft text-moss',
};

export function StatusBadge({ status, label }: { status: string; label: string }) {
  return (
    <span
      className={`inline-flex items-center gap-1.5 rounded-sm px-2 py-0.5 font-mono text-[0.7rem] tracking-wide whitespace-nowrap uppercase ${STATUS_TONE[status] ?? 'bg-frost text-clay'}`}
    >
      <span aria-hidden className="size-1.5 rounded-full bg-current" />
      {label}
    </span>
  );
}

interface PageHeaderProps {
  eyebrow: string;
  title: ReactNode;
  back?: { to: string; label: string };
  actions?: ReactNode;
  children?: ReactNode;
}

export function PageHeader({ eyebrow, title, back, actions, children }: PageHeaderProps) {
  return (
    <header className="mb-8 flex flex-wrap items-end justify-between gap-4 border-b border-frost-deep pb-6">
      <div className="min-w-0">
        {back && (
          <Link to={back.to} className="eyebrow mb-3 inline-block hover:text-amber">
            ← {back.label}
          </Link>
        )}
        <p className="eyebrow-accent">{eyebrow}</p>
        <h1 className="mt-1 text-3xl break-words sm:text-4xl">{title}</h1>
        {children}
      </div>
      {actions && <div className="flex flex-wrap gap-2">{actions}</div>}
    </header>
  );
}

export function Field({
  label,
  error,
  hint,
  children,
  className = '',
}: {
  label: string;
  error?: string;
  hint?: string;
  children: ReactNode;
  className?: string;
}) {
  return (
    <label className={`block ${className}`}>
      <span className="label">{label}</span>
      {children}
      {hint && !error && <span className="mt-1 block text-xs text-clay">{hint}</span>}
      {error && <span className="mt-1 block text-xs text-flag">{error}</span>}
    </label>
  );
}

export function Section({ title, eyebrow, children, aside }: {
  title: string;
  eyebrow?: string;
  children: ReactNode;
  aside?: ReactNode;
}) {
  return (
    <section className="panel p-5 sm:p-6">
      <div className="mb-5 flex items-baseline justify-between gap-3">
        <div>
          {eyebrow && <p className="eyebrow">{eyebrow}</p>}
          <h2 className="text-xl">{title}</h2>
        </div>
        {aside}
      </div>
      {children}
    </section>
  );
}

export function Pagination({ page, pages, onPage }: { page: number; pages: number; onPage: (p: number) => void }) {
  if (pages <= 1) return null;
  return (
    <nav aria-label="Halaman" className="flex items-center justify-between gap-3 border-t border-frost-deep px-4 py-3">
      <button className="btn-secondary btn-sm" disabled={page <= 1} onClick={() => onPage(page - 1)}>
        ← Sebelumnya
      </button>
      <span className="eyebrow tabular">
        {page} / {pages}
      </span>
      <button className="btn-secondary btn-sm" disabled={page >= pages} onClick={() => onPage(page + 1)}>
        Berikutnya →
      </button>
    </nav>
  );
}

export function EmptyRow({ colSpan, children }: { colSpan: number; children: ReactNode }) {
  return (
    <tr>
      <td colSpan={colSpan} className="py-14 text-center text-sm text-clay">
        {children}
      </td>
    </tr>
  );
}

export function Loading({ label = 'Memuat…' }: { label?: string }) {
  return (
    <div role="status" className="flex items-center gap-3 py-16 text-sm text-clay">
      <span className="size-2 animate-pulse rounded-full bg-amber" />
      {label}
    </div>
  );
}

export function ErrorState({ error }: { error: unknown }) {
  return (
    <div role="alert" className="rounded-md border-l-4 border-flag bg-flag-soft px-4 py-3 text-sm text-flag">
      {error instanceof Error ? error.message : 'Terjadi kesalahan.'}
    </div>
  );
}

export function DefinitionList({ rows }: { rows: [string, ReactNode][] }) {
  const visible = rows.filter(([, v]) => v !== null && v !== undefined && v !== '');
  return (
    <dl className="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-[minmax(8rem,auto)_1fr]">
      {visible.map(([k, v]) => (
        <div key={k} className="contents">
          <dt className="eyebrow pt-0.5">{k}</dt>
          <dd className="break-words text-ink">{v}</dd>
        </div>
      ))}
    </dl>
  );
}
