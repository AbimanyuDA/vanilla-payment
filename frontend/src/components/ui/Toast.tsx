import { createContext, useCallback, useContext, useState, type ReactNode } from 'react';

type Tone = 'success' | 'error' | 'info';
type ToastItem = { id: number; tone: Tone; text: string };
type Notify = (text: string, tone?: Tone) => void;

const ToastContext = createContext<Notify>(() => undefined);

const toneClass: Record<Tone, string> = {
  success: 'border-moss bg-moss-soft text-moss',
  error: 'border-flag bg-flag-soft text-flag',
  info: 'border-sky bg-sky-soft text-sky',
};

export function ToastProvider({ children }: { children: ReactNode }) {
  const [items, setItems] = useState<ToastItem[]>([]);

  const notify = useCallback<Notify>((text, tone = 'success') => {
    const id = Date.now() + Math.random();
    setItems((prev) => [...prev, { id, tone, text }]);
    setTimeout(() => setItems((prev) => prev.filter((t) => t.id !== id)), 4500);
  }, []);

  return (
    <ToastContext.Provider value={notify}>
      {children}
      <div
        aria-live="polite"
        className="pointer-events-none fixed inset-x-4 bottom-4 z-50 flex flex-col items-end gap-2 sm:left-auto"
      >
        {items.map((t) => (
          <div
            key={t.id}
            role={t.tone === 'error' ? 'alert' : 'status'}
            className={`animate-rise pointer-events-auto max-w-sm rounded-md border-l-4 px-4 py-3 text-sm shadow-[0_8px_24px_-12px_rgba(26,18,16,0.35)] ${toneClass[t.tone]}`}
          >
            {t.text}
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  );
}

export const useToast = () => useContext(ToastContext);
