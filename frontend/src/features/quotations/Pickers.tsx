import { useQuery } from '@tanstack/react-query';
import { useId, useState } from 'react';

import { api, withQuery } from '../../lib/api';
import { useDebounce } from '../../lib/hooks';
import type { Buyer, Product } from '../../lib/types';

interface ComboProps<T> {
  label: string;
  placeholder: string;
  endpoint: string;
  minChars: number;
  value: string;
  onInput: (v: string) => void;
  onPick: (item: T) => void;
  render: (item: T) => { title: string; meta?: string | null };
}

/** Searchable dropdown backed by a JSON search endpoint (ARIA combobox pattern). */
function Combo<T extends { id: number }>({ label, placeholder, endpoint, minChars, value, onInput, onPick, render }: ComboProps<T>) {
  const listId = useId();
  const [open, setOpen] = useState(false);
  const [active, setActive] = useState(0);
  const term = useDebounce(value, 250);
  const q = useQuery({
    queryKey: [endpoint, term],
    queryFn: () => api.get<T[]>(withQuery(endpoint, { q: term })),
    enabled: open && term.trim().length >= minChars,
  });
  const results = q.data ?? [];

  const pick = (item: T) => {
    onPick(item);
    setOpen(false);
  };

  return (
    <div className="relative">
      <input
        className="input" role="combobox" aria-label={label} aria-expanded={open && results.length > 0}
        aria-controls={listId} aria-autocomplete="list" placeholder={placeholder} value={value}
        onChange={(e) => { onInput(e.target.value); setOpen(true); setActive(0); }}
        onFocus={() => setOpen(true)}
        onBlur={() => setTimeout(() => setOpen(false), 150)}
        onKeyDown={(e) => {
          if (!open || results.length === 0) return;
          if (e.key === 'ArrowDown') { e.preventDefault(); setActive((a) => Math.min(a + 1, results.length - 1)); }
          if (e.key === 'ArrowUp') { e.preventDefault(); setActive((a) => Math.max(a - 1, 0)); }
          if (e.key === 'Enter') { e.preventDefault(); pick(results[active]); }
          if (e.key === 'Escape') setOpen(false);
        }}
      />
      {open && results.length > 0 && (
        <ul id={listId} role="listbox"
          className="absolute z-30 mt-1 max-h-72 w-full overflow-auto rounded-md border border-frost-deep bg-white py-1 shadow-[0_16px_40px_-20px_rgba(26,18,16,0.45)]">
          {results.map((item, i) => {
            const r = render(item);
            return (
              <li key={item.id} role="option" aria-selected={i === active}
                onMouseDown={(e) => { e.preventDefault(); pick(item); }}
                onMouseEnter={() => setActive(i)}
                className={`cursor-pointer px-3 py-2 text-sm ${i === active ? 'bg-frost' : ''}`}>
                <p className="font-medium">{r.title}</p>
                {r.meta && <p className="text-xs text-clay">{r.meta}</p>}
              </li>
            );
          })}
        </ul>
      )}
    </div>
  );
}

export function BuyerPicker({ onPick }: { onPick: (b: Buyer) => void }) {
  const [value, setValue] = useState('');
  return (
    <Combo<Buyer>
      label="Cari buyer tersimpan" placeholder="Ketik minimal 2 huruf nama perusahaan…" endpoint="/api/buyers/search"
      minChars={2} value={value} onInput={setValue}
      onPick={(b) => { onPick(b); setValue(''); }}
      render={(b) => ({ title: b.company_name, meta: [b.contact_person, b.country].filter(Boolean).join(' · ') })}
    />
  );
}

export function ProductPicker({ value, onInput, onPick }: {
  value: string; onInput: (v: string) => void; onPick: (p: Product) => void;
}) {
  return (
    <Combo<Product>
      label="Nama produk" placeholder="Cari produk atau ketik manual *" endpoint="/api/products/search"
      minChars={1} value={value} onInput={onInput} onPick={onPick}
      render={(p) => ({ title: p.name, meta: [p.species, p.hs_code && `HS ${p.hs_code}`].filter(Boolean).join(' · ') })}
    />
  );
}
