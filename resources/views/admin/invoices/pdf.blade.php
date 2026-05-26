<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #2c1810; background: #fff; }

        /* Header */
        .header { background: #2c1810; padding: 24px 28px; display: table; width: 100%; }
        .header-left { display: table-cell; vertical-align: middle; width: 50%; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; }
        .company-info { color: #ffdd79; }
        .company-name { font-size: 22px; font-weight: bold; color: #fff; margin-bottom: 2px; }
        .company-contact { font-size: 9.5px; color: rgba(255,221,121,0.8); line-height: 1.6; margin-top: 4px; }
        .invoice-label { color: rgba(255,221,121,0.6); font-size: 9px; letter-spacing: 2px; text-transform: uppercase; }
        .invoice-number { font-size: 17px; font-weight: bold; color: #f29923; font-family: monospace; }

        /* Gold accent bar */
        .accent-bar { height: 4px; background: linear-gradient(90deg, #f29923, #ffdd79, #f29923); }

        /* Status badge */
        .status-badge { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: 10px; font-weight: bold; letter-spacing: 0.5px; }
        .badge-paid    { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .badge-pending { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
        .badge-sent    { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
        .badge-default { background: #f3f4f6; color: #6b7280; border: 1px solid #d1d5db; }

        /* Body */
        .body { padding: 24px 28px; }

        /* Meta row */
        .meta-row { display: table; width: 100%; margin-bottom: 20px; }
        .meta-left  { display: table-cell; vertical-align: top; width: 55%; }
        .meta-right { display: table-cell; vertical-align: top; text-align: right; }
        .meta-label { font-size: 8.5px; text-transform: uppercase; letter-spacing: 1px; color: #b08040; font-weight: bold; margin-bottom: 6px; }

        /* Customer box */
        .customer-box { background: #fef9f0; border-left: 3px solid #f29923; border-radius: 0 6px 6px 0; padding: 10px 14px; }
        .customer-name { font-size: 14px; font-weight: bold; color: #2c1810; margin-bottom: 3px; }
        .customer-detail { font-size: 10.5px; color: #666; line-height: 1.6; }

        /* Invoice meta right */
        .meta-info-row { font-size: 10.5px; margin-bottom: 5px; color: #666; }
        .meta-info-row strong { color: #2c1810; }

        /* Items table */
        table.items { width: 100%; border-collapse: collapse; margin: 18px 0; }
        table.items thead tr { background: #2c1810; }
        table.items thead th { color: #ffdd79; padding: 9px 10px; font-size: 10.5px; font-weight: bold; letter-spacing: 0.3px; }
        table.items tbody tr:nth-child(even) { background: #fef9f0; }
        table.items tbody td { padding: 8px 10px; border-bottom: 1px solid #f3e8d4; font-size: 11px; color: #2c1810; }
        .product-desc { font-size: 9.5px; color: #999; margin-top: 2px; }

        /* Totals */
        .totals-wrap { display: table; width: 100%; }
        .totals-space { display: table-cell; width: 50%; }
        .totals-box { display: table-cell; width: 50%; }
        .totals-box table { width: 100%; border-collapse: collapse; }
        .totals-box table td { padding: 5px 8px; font-size: 11px; }
        .totals-box .subtotal-row td { color: #666; border-bottom: 1px solid #f3e8d4; }
        .totals-box .extra-row td { color: #888; }
        .totals-box .discount-row td { color: #059669; }
        .totals-box .total-row td { font-weight: bold; font-size: 13px; color: #2c1810; border-top: 2px solid #f29923; padding-top: 8px; }
        .totals-box .method-row td { color: #888; font-size: 10px; }
        .text-right { text-align: right; }

        /* Notes */
        .notes-box { background: #fef9f0; border: 1px solid #f3e8d4; border-radius: 6px; padding: 10px 14px; margin-top: 18px; font-size: 10.5px; color: #666; }
        .notes-box strong { color: #41281b; }

        /* Paid confirmation */
        .paid-confirm { background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 6px; padding: 10px 14px; margin-top: 16px; font-size: 10.5px; color: #14532d; }

        /* Footer */
        .footer-bar { background: #fef9f0; border-top: 1px solid #f3e8d4; padding: 10px 28px; margin-top: 24px; display: table; width: 100%; }
        .footer-left { display: table-cell; font-size: 9px; color: #b08040; }
        .footer-right { display: table-cell; text-align: right; font-size: 9px; color: #b08040; }

        /* LUNAS stamp */
        .stamp { position: fixed; top: 42%; left: 50%; transform: translate(-50%, -50%) rotate(-28deg);
                 border: 5px solid #16a34a; border-radius: 10px; padding: 10px 28px;
                 color: #16a34a; font-size: 60px; font-weight: 900; opacity: 0.10;
                 pointer-events: none; white-space: nowrap; letter-spacing: 8px; }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div style="font-size:22px; font-weight:bold; color:#fff; margin-bottom:4px; letter-spacing:0.5px;">Vanilla Royal</div>
            <div class="company-contact">
                info@vanillaroyal.id &nbsp;|&nbsp; +62 858-5366-9568
            </div>
        </div>
        <div class="header-right">
            <div class="invoice-label">Invoice</div>
            <div class="invoice-number">{{ $invoice->invoice_number }}</div>
            <div style="margin-top:6px;">
                @php
                    $badgeClass = match($invoice->status) {
                        'paid'    => 'badge-paid',
                        'pending_confirmation' => 'badge-pending',
                        'sent'    => 'badge-sent',
                        default   => 'badge-default',
                    };
                @endphp
                <span class="status-badge {{ $badgeClass }}">{{ $invoice->status_label }}</span>
            </div>
        </div>
    </div>
    <div class="accent-bar"></div>

    <!-- Body -->
    <div class="body">

        <!-- Meta: Customer + Invoice info -->
        <div class="meta-row">
            <div class="meta-left">
                <div class="meta-label">Tagihan Kepada</div>
                <div class="customer-box">
                    <div class="customer-name">{{ $invoice->customer_name }}</div>
                    <div class="customer-detail">
                        @if($invoice->customer_phone){{ $invoice->customer_phone }}<br>@endif
                        @if($invoice->customer_email){{ $invoice->customer_email }}<br>@endif
                        @if($invoice->customer_address){{ $invoice->customer_address }}@endif
                    </div>
                </div>
            </div>
            <div class="meta-right">
                <div class="meta-info-row">
                    <strong>Tanggal:</strong> {{ $invoice->created_at->format('d M Y') }}
                </div>
                @if($invoice->due_date)
                <div class="meta-info-row" style="{{ $invoice->isExpired() ? 'color:#dc2626;' : '' }}">
                    <strong>Jatuh Tempo:</strong> {{ $invoice->due_date->format('d M Y') }}
                </div>
                @endif
                @if($invoice->paid_at)
                <div class="meta-info-row" style="color:#16a34a;">
                    <strong>Lunas:</strong> {{ $invoice->paid_at->format('d M Y, H:i') }}
                </div>
                @endif
            </div>
        </div>

        <!-- Items Table -->
        <table class="items">
            <thead>
                <tr>
                    <th style="text-align:left; border-radius:4px 0 0 0;">Produk</th>
                    <th style="text-align:center; width:60px;">Qty</th>
                    <th style="text-align:right; width:110px;">Harga Satuan</th>
                    <th style="text-align:right; width:110px; border-radius:0 4px 0 0;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->product_name }}</strong>
                        @if($item->description)<div class="product-desc">{{ $item->description }}</div>@endif
                    </td>
                    <td style="text-align:center;">{{ $item->quantity }}</td>
                    <td style="text-align:right;">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td style="text-align:right;"><strong>Rp {{ number_format($item->total_price, 0, ',', '.') }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-wrap">
            <div class="totals-space">
                @if($invoice->notes)
                <div class="notes-box">
                    <strong>Catatan:</strong><br>
                    {{ $invoice->notes }}
                </div>
                @endif
            </div>
            <div class="totals-box">
                <table>
                    <tr class="subtotal-row">
                        <td>Subtotal</td>
                        <td class="text-right">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @foreach($invoice->extras as $extra)
                    <tr class="extra-row">
                        <td>{{ $extra->label }}</td>
                        <td class="text-right">+ Rp {{ number_format($extra->amount, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    @if($invoice->discount > 0)
                    <tr class="discount-row">
                        <td>Diskon</td>
                        <td class="text-right">- Rp {{ number_format($invoice->discount, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td>TOTAL</td>
                        <td class="text-right" style="color:#f29923;">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                    </tr>
                    @if($invoice->payment_method)
                    <tr class="method-row">
                        <td>Metode</td>
                        <td class="text-right">{{ $invoice->payment_method === 'transfer' ? 'Transfer Bank' : 'QRIS' }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        @if($invoice->paid_at)
        <div class="paid-confirm">
            <strong>Pembayaran dikonfirmasi:</strong> {{ $invoice->paid_at->format('d M Y, H:i') }}
            &bull; Metode: {{ $invoice->payment_method === 'transfer' ? 'Transfer Bank' : 'QRIS' }}
        </div>
        @endif

    </div>

    <!-- Footer -->
    <div class="footer-bar">
        <div class="footer-left">Vanilla Royal &bull; info@vanillaroyal.id &bull; +62 858-5366-9568</div>
        <div class="footer-right">Dicetak: {{ now()->format('d M Y, H:i') }}</div>
    </div>

    @if($invoice->status === 'paid')
    <div class="stamp">LUNAS</div>
    @endif

</body>
</html>
