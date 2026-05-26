<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Lunas — Vanilla Royal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; box-sizing: border-box; margin: 0; padding: 0; }
        body { background: linear-gradient(160deg, #2c1810 0%, #41281b 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }
    </style>
</head>
<body>
<div style="width:100%; max-width:420px;">

    <!-- Logo -->
    <div style="text-align:center; margin-bottom:1.75rem;">
        <div style="display:inline-block; background:#fff; border-radius:16px; padding:1rem 1.5rem; box-shadow:0 4px 20px rgba(0,0,0,0.25);">
            <img src="{{ asset('images/logo/logo.webp') }}" alt="Vanilla Royal" style="height:80px; width:auto; display:block;">
        </div>
    </div>

    <!-- Card -->
    <div style="background:#fff; border-radius:20px; overflow:hidden; box-shadow:0 12px 40px rgba(44,24,16,0.22);">

        <!-- Green success bar -->
        <div style="height:4px; background: linear-gradient(90deg, #16a34a, #22c55e);"></div>

        <!-- Success icon area -->
        <div style="padding:2.25rem 2rem 1.5rem; text-align:center; border-bottom:1px solid #f3ede4;">
            <div style="width:64px; height:64px; border-radius:50%; background:#f0fdf4; border:2px solid #bbf7d0; display:flex; align-items:center; justify-content:center; margin:0 auto 1.25rem;">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            <h1 style="font-size:1.25rem; font-weight:700; color:#14532d; margin-bottom:0.35rem;">Pembayaran Dikonfirmasi</h1>
            <p style="font-size:0.8rem; color:#6b7280; font-weight:500; letter-spacing:0.04em; text-transform:uppercase;">Invoice {{ $invoice->invoice_number }}</p>
        </div>

        <!-- Details -->
        <div style="padding:1.5rem 2rem;">
            <div style="background:#fef9f0; border-radius:12px; padding:1rem 1.25rem; margin-bottom:1.5rem;">
                <table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
                    <tr>
                        <td style="padding:0.45rem 0; color:#9ca3af; width:45%;">Customer</td>
                        <td style="padding:0.45rem 0; color:#1f2937; font-weight:600; text-align:right;">{{ $invoice->customer_name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:0.45rem 0; color:#9ca3af;">No. Invoice</td>
                        <td style="padding:0.45rem 0; color:#41281b; font-family:monospace; font-size:0.8rem; font-weight:600; text-align:right;">{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr style="border-top:1px dashed #e5d8cc;">
                        <td style="padding:0.65rem 0 0.45rem; color:#2c1810; font-weight:700; font-size:0.95rem;">Total Dibayar</td>
                        <td style="padding:0.65rem 0 0.45rem; color:#f29923; font-weight:700; font-size:1.1rem; text-align:right;">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                    </tr>
                    @if($invoice->paid_at)
                    <tr>
                        <td style="padding:0.3rem 0; color:#9ca3af;">Dikonfirmasi</td>
                        <td style="padding:0.3rem 0; color:#6b7280; font-size:0.8rem; text-align:right;">{{ $invoice->paid_at->format('d M Y, H:i') }}</td>
                    </tr>
                    @endif
                </table>
            </div>

            <p style="font-size:0.8rem; color:#9ca3af; text-align:center; line-height:1.6;">
                Terima kasih telah mempercayai <strong style="color:#41281b;">Vanilla Royal</strong>.<br>
                Pesanan Anda sedang kami proses.
            </p>

            <a href="{{ route('payment.invoice-pdf', $invoice->payment_token) }}"
               style="display:block; width:100%; margin-top:1.25rem; padding:0.75rem; background:#2c1810; color:#fff; border-radius:10px; text-align:center; font-size:0.875rem; font-weight:600; text-decoration:none; letter-spacing:0.02em;">
                Download Invoice PDF
            </a>
        </div>

        <!-- Footer strip -->
        <div style="padding:1rem 2rem; background:#fafafa; border-top:1px solid #f3ede4; text-align:center;">
            <p style="font-size:0.7rem; color:#d1c4b8; letter-spacing:0.05em; text-transform:uppercase;">Dokumen ini sebagai bukti pembayaran sah</p>
        </div>
    </div>

    <!-- Bottom -->
    <div style="margin-top:1.75rem; text-align:center;">
        <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.6rem;">
            <div style="flex:1; height:1px; background:rgba(242,153,35,0.18);"></div>
            <img src="{{ asset('images/logo/logo.webp') }}" alt="" style="height:18px; width:auto; opacity:0.3;">
            <div style="flex:1; height:1px; background:rgba(242,153,35,0.18);"></div>
        </div>
        <p style="font-size:0.7rem; color:rgba(255,221,121,0.5); font-weight:500;">© {{ date('Y') }} Vanilla Royal &bull; All rights reserved</p>
    </div>

</div>
</body>
</html>
