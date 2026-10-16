import { isRouteErrorResponse, useRouteError } from 'react-router';

/** Route-level fallback so a runtime error never leaves the customer with a blank page. */
export function AppError() {
  const error = useRouteError();
  const detail = isRouteErrorResponse(error) ? `${error.status} ${error.statusText}` : null;

  return (
    <main className="grid min-h-dvh place-items-center px-6">
      <div className="max-w-md text-center">
        <img src="/images/logo/logo.webp" alt="Vanilla Royal" width={120} height={40} className="mx-auto h-10 w-auto" />
        <p className="eyebrow-accent mt-8">Terjadi kendala</p>
        <h1 className="mt-2 text-3xl">Halaman gagal dimuat</h1>
        <p className="mt-3 text-sm text-ink-soft">
          Silakan muat ulang halaman. Jika masih terjadi, hubungi Vanilla Royal di info@vanillaroyal.id.
        </p>
        {detail && <p className="mt-2 font-mono text-xs text-clay">{detail}</p>}
        <button className="btn-primary mt-6" onClick={() => window.location.reload()}>Muat ulang</button>
      </div>
    </main>
  );
}
