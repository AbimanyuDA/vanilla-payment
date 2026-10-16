import { useQuery } from '@tanstack/react-query';

import { api } from '../../lib/api';
import type { TradeMeta } from '../../lib/types';

export function useTradeMeta() {
  return useQuery({
    queryKey: ['trade-meta'],
    queryFn: () => api.get<TradeMeta>('/api/meta/trade'),
    staleTime: Infinity,
  });
}

export function CountrySelect({ value, onChange, id }: { value: string; onChange: (v: string) => void; id?: string }) {
  const meta = useTradeMeta();
  return (
    <select id={id} className="input" value={value} onChange={(e) => onChange(e.target.value)}>
      <option value="">— Pilih negara —</option>
      {meta.data?.countries.map((c) => (
        <option key={c.code} value={c.code}>{c.name} ({c.code})</option>
      ))}
    </select>
  );
}
