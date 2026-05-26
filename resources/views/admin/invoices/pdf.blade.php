<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid #7c3aed; padding-bottom: 15px; }
        .company-name { font-size: 20px; font-weight: bold; color: #7c3aed; }
        .invoice-number { font-size: 16px; font-weight: bold; text-align: right; }
        .status { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-sent { background: #dbeafe; color: #1e40af; }
        .status-default { background: #f3f4f6; color: #6b7280; }
        .customer-box { background: #f9fafb; padding: 12px; border-radius: 6px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #7c3aed; color: white; padding: 8px; text-align: left; font-size: 11px; }
        td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals { float: right; width: 220px; }
        .totals table td { border: none; padding: 4px 8px; }
        .total-row { font-weight: bold; border-top: 2px solid #333; }
        .notes { background: #fef9c3; padding: 10px; border-radius: 6px; margin-top: 20px; }
        .footer { text-align: center; margin-top: 40px; color: #9ca3af; font-size: 10px; border-top: 1px solid #e5e7eb; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="company-name">Vanilla Royal</div>
            <div style="color: #6b7280; font-size: 11px;">Payment System</div>
        </div>
        <div>
            <div class="invoice-number">{{ $invoice->invoice_number }}</div>
            <div class="status status-{{ $invoice->status === 'paid' ? 'paid' : ($invoice->status === 'pending_confirmation' ? 'pending' : ($invoice->status === 'sent' ? 'sent' : 'default')) }}">
                {{ $invoice->status_label }}
            </div>
            <div style="text-align:right; color:#9ca3af; font-size:10px; margin-top:4px;">
                {{ $invoice->created_at->format('d M Y') }}
            </div>
        </div>
    </div>

    <div class="customer-box">
        <strong>TAGIHAN KEPADA:</strong><br>
        <strong>{{ $invoice->customer_name }}</strong><br>
        @if($invoice->customer_phone){{ $invoice->customer_phone }}<br>@endif
        @if($invoice->customer_email){{ $invoice->customer_email }}<br>@endif
        @if($invoice->customer_address){{ $invoice->customer_address }}@endif
    </div>

    @if($invoice->due_date)
    <p style="font-size:11px; color:#6b7280;">Jatuh Tempo: <strong>{{ $invoice->due_date->format('d M Y') }}</strong></p>
    @endif

    <table>
        <thead>
            <tr>
                <th>Produk</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Harga Satuan</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>
                    <strong>{{ $item->product_name }}</strong>
                    @if($item->description)<br><span style="color:#6b7280; font-size:10px;">{{ $item->description }}</span>@endif
                </td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="text-right"><strong>Rp {{ number_format($item->total_price, 0, ',', '.') }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr><td>Subtotal</td><td class="text-right">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td></tr>
            @foreach($invoice->extras as $extra)
            <tr><td style="color:#6b7280;">{{ $extra->label }}</td><td class="text-right" style="color:#6b7280;">+ Rp {{ number_format($extra->amount, 0, ',', '.') }}</td></tr>
            @endforeach
            @if($invoice->discount > 0)
            <tr><td style="color:#059669;">Diskon</td><td class="text-right" style="color:#059669;">- Rp {{ number_format($invoice->discount, 0, ',', '.') }}</td></tr>
            @endif
            <tr class="total-row">
                <td>TOTAL</td>
                <td class="text-right">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
            </tr>
            @if($invoice->payment_method)
            <tr><td style="color:#6b7280;">Metode</td><td class="text-right">{{ $invoice->payment_method === 'qris' ? 'QRIS' : 'Transfer Bank' }}</td></tr>
            @endif
        </table>
    </div>

    <div style="clear:both;"></div>

    @if($invoice->notes)
    <div class="notes"><strong>Catatan:</strong> {{ $invoice->notes }}</div>
    @endif

    <div class="footer">
        Dokumen ini dibuat secara otomatis oleh Vanilla Royal Payment System &bull; {{ now()->format('d M Y H:i') }}
    </div>
</body>
</html>
