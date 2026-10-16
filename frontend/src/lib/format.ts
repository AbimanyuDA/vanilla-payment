const idr = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });
const intl2 = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export const rupiah = (n: number | null | undefined) => `Rp ${idr.format(n ?? 0)}`;
export const money = (n: number | null | undefined) => intl2.format(n ?? 0);
export const qty = (n: number) => String(Number(n.toFixed(2)));

export function formatDate(value: string | null | undefined, withTime = false): string {
  if (!value) return '—';
  const d = new Date(value.length === 10 ? `${value}T00:00:00` : value);
  return d.toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}),
  });
}

export const todayIso = () => new Date().toLocaleDateString('sv-SE');

export function addDays(iso: string, days: number): string {
  const d = new Date(`${iso}T00:00:00`);
  d.setDate(d.getDate() + days);
  return d.toLocaleDateString('sv-SE');
}

/** Empty inputs travel as null so the API treats them as "not provided". */
export const blankToNull = (v: string) => (v.trim() === '' ? null : v);
