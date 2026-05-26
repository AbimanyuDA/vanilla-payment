<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Dibatalkan — Vanilla Royal</title>
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
            <img src="{{ asset('images/logo/logo.png') }}" alt="Vanilla Royal" style="height:40px; width:auto; display:block;">
        </div>
    </div>

    <!-- Card -->
    <div style="background:#fff; border-radius:20px; overflow:hidden; box-shadow:0 12px 40px rgba(44,24,16,0.22);">

        <div style="height:4px; background: linear-gradient(90deg, #dc2626, #ef4444);"></div>

        <div style="padding:2.25rem 2rem 1.5rem; text-align:center; border-bottom:1px solid #f3ede4;">
            <div style="width:64px; height:64px; border-radius:50%; background:#fef2f2; border:2px solid #fecaca; display:flex; align-items:center; justify-content:center; margin:0 auto 1.25rem;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </div>
            <h1 style="font-size:1.25rem; font-weight:700; color:#991b1b; margin-bottom:0.35rem;">Invoice Dibatalkan</h1>
            <p style="font-size:0.8rem; color:#6b7280; font-weight:500; letter-spacing:0.04em; text-transform:uppercase;">{{ $invoice->invoice_number }}</p>
        </div>

        <div style="padding:1.5rem 2rem;">
            <div style="background:#fef9f0; border-radius:12px; padding:1rem 1.25rem; margin-bottom:1.5rem;">
                <table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
                    <tr>
                        <td style="padding:0.4rem 0; color:#9ca3af;">Customer</td>
                        <td style="padding:0.4rem 0; color:#1f2937; font-weight:600; text-align:right;">{{ $invoice->customer_name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:0.4rem 0; color:#9ca3af;">Total</td>
                        <td style="padding:0.4rem 0; color:#41281b; font-weight:700; text-align:right;">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
            <p style="font-size:0.82rem; color:#9ca3af; text-align:center; line-height:1.65;">
                Invoice ini telah dibatalkan.<br>
                Hubungi <strong style="color:#41281b;">Vanilla Royal</strong> untuk informasi lebih lanjut.
            </p>
        </div>

        <div style="padding:1rem 2rem; background:#fafafa; border-top:1px solid #f3ede4; text-align:center;">
            <p style="font-size:0.7rem; color:#d1c4b8; letter-spacing:0.05em; text-transform:uppercase;">Invoice tidak dapat diproses</p>
        </div>
    </div>

    <div style="margin-top:1.75rem; text-align:center;">
        <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.6rem;">
            <div style="flex:1; height:1px; background:rgba(242,153,35,0.18);"></div>
            <img src="{{ asset('images/logo/logo.png') }}" alt="" style="height:18px; width:auto; opacity:0.3;">
            <div style="flex:1; height:1px; background:rgba(242,153,35,0.18);"></div>
        </div>
        <p style="font-size:0.7rem; color:rgba(255,221,121,0.5); font-weight:500;">© {{ date('Y') }} Vanilla Royal &bull; All rights reserved</p>
    </div>

</div>
</body>
</html>
