import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router';

import { useToast } from '../components/ui/Toast';
import { ApiError, type Message } from './api';

export function useDebounce<T>(value: T, delay = 300): T {
  const [debounced, setDebounced] = useState(value);
  useEffect(() => {
    const id = setTimeout(() => setDebounced(value), delay);
    return () => clearTimeout(id);
  }, [value, delay]);
  return debounced;
}

/** Filters live in the URL so lists are shareable and survive refresh. */
export function useUrlFilters<K extends string>(keys: readonly K[]) {
  const [params, setParams] = useSearchParams();
  const values = Object.fromEntries(keys.map((k) => [k, params.get(k) ?? ''])) as Record<K, string>;
  const page = Number(params.get('page') ?? '1') || 1;

  const set = (patch: Partial<Record<K | 'page', string | number>>) => {
    const next = new URLSearchParams(params);
    for (const [k, v] of Object.entries(patch)) {
      if (v === '' || v === undefined || v === null) next.delete(k);
      else next.set(k, String(v));
    }
    if (!('page' in patch)) next.delete('page');
    setParams(next, { replace: true });
  };
  return { values, page, set };
}

/** Action mutation that toasts the server message and refreshes cached data. */
export function useAction<V = void>(fn: (vars: V) => Promise<Message>, opts: {
  invalidate?: string[][];
  onSuccess?: (res: Message) => void;
} = {}) {
  const qc = useQueryClient();
  const notify = useToast();
  return useMutation({
    mutationFn: fn,
    onSuccess: (res) => {
      if (res.message && !res.redirect_url) notify(res.message);
      for (const key of opts.invalidate ?? []) qc.invalidateQueries({ queryKey: key });
      opts.onSuccess?.(res);
    },
    onError: (err) => notify(err instanceof ApiError ? err.message : 'Terjadi kesalahan.', 'error'),
  });
}

export function fieldErrors(err: unknown): Record<string, string> {
  return err instanceof ApiError ? err.errors : {};
}
