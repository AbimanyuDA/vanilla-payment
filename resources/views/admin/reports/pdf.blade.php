<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Invoice</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; color: #333; margin: 0; padding: 15px; }
        h1 { font-size: 16px; color: #7c3aed; margin: 0 0 4px; }
        .sub { color: #6b7280; font-size: 10px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #7c3aed; color: white; padding: 6px; text-align: left; font-size: 10px; }
        td { padding: 6px; border-bottom: 1px solid #e5e7eb; }
        .text-right { text-align: right; }
        .footer { text-align: center; margin-top: 20px; color: #9ca3af; font-size: 9px; }
        .summary { background: #f3f4f6; padding: 8px; border-radius: 4px; margin-bottom: 15px; display: flex; gap: 30px; }
    </style>
</head>
<body>
    <h1>Vanilla Royal — Laporan Invoice</h1>
    <div class="sub">Dicetak: {{ now()->format('d M Y H:i') }}</div>

    <table style="width:auto; margin-bottom:15px; border:none;">
        <tr><td style="padding:2px 10px 2px 0; border:none; font-weight:bold;">Total Invoice</td><td style="border:none;">{{ $invoices->count() }}</td></tr>
        <tr><td style="padding:2px 10px 2px 0; border:none; font-weight:bold;">Total Revenue</td><td style="border:none;">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</td></tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No. Invoice</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Metode</th>
                <th>Status</th>
                <th>Tanggal</th>
                <th>Lunas</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoices as $i => $invoice)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $invoice->invoice_number }}</td>
                <td>{{ $invoice->customer_name }}</td>
                <td class="text-right">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                <td>{{ $invoice->payment_method ? strtoupper($invoice->payment_method) : '-' }}</td>
                <td>{{ $invoice->status_label }}</td>
                <td>{{ $invoice->created_at->format('d/m/Y') }}</td>
                <td>{{ $invoice->paid_at?->format('d/m/Y') ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">Vanilla Royal Payment System &bull; {{ now()->format('d M Y H:i') }}</div>
</body>
</html>
