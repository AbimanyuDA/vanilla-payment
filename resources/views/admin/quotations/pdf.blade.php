<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $quotation->quotation_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 10.5px; color: #1c2530; }

        .header { display: table; width: 100%; padding: 26px 32px 16px; }
        .header-left { display: table-cell; vertical-align: middle; width: 62%; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; }
        .logo-wrap { display: table; }
        .logo-cell-img { display: table-cell; vertical-align: middle; padding-right: 10px; }
        .logo-cell-name { display: table-cell; vertical-align: middle; }
        .company-name { font-size: 15px; font-weight: bold; letter-spacing: 0.3px; }
        .company-contact { font-size: 8.5px; color: #6b7280; line-height: 1.6; margin-top: 3px; }

        .doc-title { font-size: 10px; letter-spacing: 2.5px; color: #b08040; text-transform: uppercase; font-weight: bold; }
        .doc-number { font-size: 15px; font-weight: bold; font-family: Courier, monospace; margin-top: 2px; }
        .doc-meta { font-size: 9px; color: #6b7280; margin-top: 4px; line-height: 1.6; }

        .accent-bar { height: 3px; background: #b08040; margin: 0 32px; }

        .body { padding: 18px 32px 10px; }

        /* The panels are the table cells themselves, so a row of them always renders
           at equal height — a nested div would only be as tall as its own content. */
        .info-row { display: table; width: 100%; margin-bottom: 14px; }
        .info-box { display: table-cell; width: 48%; vertical-align: top;
                    background: #f8f6f2; border-left: 2.5px solid #b08040; padding: 9px 12px; }
        .info-gap { display: table-cell; width: 4%; }
        .info-label { font-size: 7.5px; text-transform: uppercase; letter-spacing: 1.2px; color: #b08040; font-weight: bold; margin-bottom: 5px; }
        .info-title { font-size: 12px; font-weight: bold; margin-bottom: 2px; }
        .info-line { font-size: 9.5px; color: #374151; line-height: 1.55; }
        .info-line strong { color: #1c2530; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 14px; table-layout: fixed; }
        table.items th, table.items td { padding: 7px 8px; }
        table.items thead th {
            background: #1c2530; color: #f2e6cf; font-size: 8.5px;
            text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold; text-align: left;
        }
        table.items tbody td { font-size: 9.5px; border-bottom: 0.75px solid #e5e0d5; vertical-align: top; }
        table.items tbody tr:nth-child(even) { background: #faf8f4; }
        /* Must out-specify `table.items thead th`, otherwise headers stay left-aligned
           while their figures are right-aligned and the columns read as misaligned. */
        table.items th.text-right, table.items td.text-right { text-align: right; }
        table.items th.text-center, table.items td.text-center { text-align: center; }
        .item-name { font-weight: bold; }
        .item-spec { font-size: 8px; color: #6b7280; margin-top: 2px; line-height: 1.5; }
        .item-desc { font-size: 8px; color: #6b7280; font-style: italic; margin-top: 2px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .nowrap { white-space: nowrap; }

        .totals-wrap { display: table; width: 100%; margin-bottom: 16px; }
        .totals-space { display: table-cell; width: 55%; vertical-align: top; padding-right: 14px; }
        .totals-box { display: table-cell; width: 45%; vertical-align: top; }
        .totals-box table { width: 100%; border-collapse: collapse; }
        .totals-box td { padding: 4px 6px; font-size: 9.5px; }
        .totals-box .subtotal-row td { border-bottom: 0.75px solid #e5e0d5; color: #374151; }
        .totals-box .line-row td { color: #6b7280; }
        .totals-box .grand-row td { font-weight: bold; font-size: 12px; border-top: 1.5px solid #b08040; padding-top: 7px; color: #1c2530; }

        .section-box { background: #f8f6f2; border: 0.75px solid #e5e0d5; border-radius: 3px; padding: 10px 12px; margin-bottom: 12px; }

        /* An empty div carries the break; the spacer that follows keeps the first block
           on the new page clear of the top edge (margins collapse there in dompdf). */
        .page-break { page-break-before: always; }
        .page-top-space { height: 34px; }

        table.bank-table { width: 100%; border-collapse: collapse; }
        table.bank-table td { font-size: 9px; color: #374151; padding: 2.5px 0; vertical-align: top; line-height: 1.5; }
        table.bank-table .bank-key { width: 34%; color: #6b7280; padding-right: 10px; }
        .section-label { font-size: 7.5px; text-transform: uppercase; letter-spacing: 1.2px; color: #b08040; font-weight: bold; margin-bottom: 6px; }
        .terms-text { font-size: 8.5px; color: #374151; line-height: 1.7; white-space: pre-line; }

        .sign-wrap { display: table; width: 100%; margin-top: 12px; }
        .sign-col { display: table-cell; width: 50%; vertical-align: top; }
        .sign-space { height: 28px; }
        .sign-line { border-top: 0.75px solid #9ca3af; padding-top: 5px; width: 70%; }
        .sign-name { font-size: 10px; font-weight: bold; }
        .sign-position { font-size: 8.5px; color: #6b7280; }

        .footer-note { font-size: 7.5px; color: #9ca3af; text-align: center; padding: 10px 32px; border-top: 0.75px solid #e5e0d5; margin-top: 10px; }
    </style>
</head>
<body>

    @php
        $snap = $quotation->pdf_company_snapshot ?? \App\Models\CompanySettings::current()->toSnapshotArray();
        $logoPath = public_path($snap['logo_path'] ?? '');
        $logoSrc = ($snap['logo_path'] ?? null) && file_exists($logoPath)
            ? 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($logoPath))
            : null;
    @endphp

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="logo-wrap">
                @if($logoSrc)
                <div class="logo-cell-img">
                    <img src="{{ $logoSrc }}" style="height:56px; width:auto;">
                </div>
                @endif
                <div class="logo-cell-name">
                    <div class="company-name">{{ $snap['company_name'] ?? '' }}</div>
                    <div class="company-contact">
                        {{ $snap['address'] ?? '' }}@if(!empty($snap['country'])), {{ $snap['country'] }}@endif<br>
                        {{ collect([$snap['email'] ?? null, $snap['phone'] ?? null, $snap['website'] ?? null])->filter()->implode(' | ') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="header-right">
            <div class="doc-title">International Export Quotation</div>
            <div class="doc-number">{{ $quotation->quotation_number }}</div>
            <div class="doc-meta">
                Date: {{ $quotation->quotation_date->format('d M Y') }}<br>
                Valid Until: {{ $quotation->valid_until->format('d M Y') }}
            </div>
        </div>
    </div>
    <div class="accent-bar"></div>

    <div class="body">

        <!-- Buyer + Trade Terms -->
        <div class="info-row">
            <div class="info-box">
                <div class="info-label">Buyer</div>
                <div class="info-title">{{ $quotation->buyer_company_name }}</div>
                <div class="info-line">
                    @if($quotation->buyer_contact_person){{ $quotation->buyer_contact_person }}<br>@endif
                    @if($quotation->buyer_email){{ $quotation->buyer_email }}<br>@endif
                    @if($quotation->buyer_phone){{ $quotation->buyer_phone }}<br>@endif
                    @if($quotation->buyer_address){{ $quotation->buyer_address }}<br>@endif
                    {{ collect([$quotation->buyer_city, $quotation->buyer_state_province, $quotation->buyer_postal_code, $quotation->buyer_country])->filter()->implode(', ') }}
                    @if($quotation->buyer_tax_vat_number)<br>Tax/VAT: {{ $quotation->buyer_tax_vat_number }}@endif
                </div>
            </div>
            <div class="info-gap"></div>
            <div class="info-box">
                <div class="info-label">Trade Terms</div>
                <div class="info-line">
                    <strong>Incoterm:</strong> {{ $quotation->incoterm }}{{ $quotation->incoterm_place ? ' ' . $quotation->incoterm_place : '' }}<br>
                    <strong>Currency:</strong> {{ $quotation->currency }}<br>
                    @if($quotation->payment_terms)<strong>Payment Terms:</strong> {{ $quotation->payment_terms }}<br>@endif
                    @if($quotation->moq)<strong>MOQ:</strong> {{ $quotation->moq }}<br>@endif
                    <strong>Country of Origin:</strong> {{ $quotation->country_of_origin }}
                    @if($quotation->destination_country)<br><strong>Destination:</strong> {{ $quotation->destination_country }}@endif
                </div>
            </div>
        </div>

        @if($quotation->port_of_loading || $quotation->port_of_discharge || $quotation->final_destination || $quotation->shipping_method || $quotation->production_lead_time || $quotation->estimated_shipment)
        <div class="section-box">
            <div class="section-label">Shipping Information</div>
            <div class="info-line">
                @if($quotation->port_of_loading)<strong>Port of Loading:</strong> {{ $quotation->port_of_loading }}<br>@endif
                @if($quotation->port_of_discharge)<strong>Port of Discharge:</strong> {{ $quotation->port_of_discharge }}<br>@endif
                @if($quotation->final_destination)<strong>Final Destination:</strong> {{ $quotation->final_destination }}<br>@endif
                @if($quotation->shipping_method)<strong>Shipping Method:</strong> {{ $quotation->shipping_method }}<br>@endif
                @if($quotation->production_lead_time)<strong>Production Lead Time:</strong> {{ $quotation->production_lead_time }}<br>@endif
                @if($quotation->estimated_shipment)<strong>Estimated Shipment:</strong> {{ $quotation->estimated_shipment }}@endif
            </div>
        </div>
        @endif

        <!-- Items -->
        <table class="items">
            <thead>
                {{-- Widths must total 100%: any shortfall is distributed unpredictably. --}}
                <tr>
                    <th style="width:46%;">Product</th>
                    <th class="text-right nowrap" style="width:14%;">Qty</th>
                    <th class="text-right nowrap" style="width:20%;">Unit Price ({{ $quotation->currency }})</th>
                    <th class="text-right nowrap" style="width:20%;">Total ({{ $quotation->currency }})</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $itemSpecs = fn ($item) => collect([
                        $item->species, $item->grade, $item->size, $item->weight,
                        $item->moisture, $item->packaging, $item->condition, $item->aroma,
                        $item->country_of_origin ? 'Origin: ' . $item->country_of_origin : null,
                    ])->filter()->implode(' • ');
                @endphp
                @foreach($quotation->items as $item)
                <tr>
                    <td>
                        <div class="item-name">{{ $item->product_name }}</div>
                        @if($itemSpecs($item) || $item->hs_code)
                        <div class="item-spec">
                            {{ $itemSpecs($item) }}
                            @if($item->hs_code)<br>HS Code: {{ $item->hs_code }}@endif
                        </div>
                        @endif
                        @if($item->description)<div class="item-desc">{{ $item->description }}</div>@endif
                    </td>
                    <td class="text-right nowrap">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }} {{ $item->unit }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right"><strong>{{ number_format($item->line_total, 2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-wrap">
            <div class="totals-space">
                @if($quotation->notes)
                <div class="section-box">
                    <div class="section-label">Notes</div>
                    <div class="terms-text">{{ $quotation->notes }}</div>
                </div>
                @endif
            </div>
            <div class="totals-box">
                <table>
                    <tr class="subtotal-row">
                        <td>Subtotal</td>
                        <td class="text-right">{{ $quotation->currency }} {{ number_format($quotation->subtotal, 2) }}</td>
                    </tr>
                    @if($quotation->discount_amount > 0)
                    <tr class="line-row"><td>Discount</td><td class="text-right">- {{ $quotation->currency }} {{ number_format($quotation->discount_amount, 2) }}</td></tr>
                    @endif
                    @if($quotation->freight_amount > 0)
                    <tr class="line-row"><td>Freight</td><td class="text-right">+ {{ $quotation->currency }} {{ number_format($quotation->freight_amount, 2) }}</td></tr>
                    @endif
                    @if($quotation->insurance_amount > 0)
                    <tr class="line-row"><td>Insurance</td><td class="text-right">+ {{ $quotation->currency }} {{ number_format($quotation->insurance_amount, 2) }}</td></tr>
                    @endif
                    @if($quotation->other_charges_amount > 0)
                    <tr class="line-row"><td>{{ $quotation->other_charges_label ?: 'Other Charges' }}</td><td class="text-right">+ {{ $quotation->currency }} {{ number_format($quotation->other_charges_amount, 2) }}</td></tr>
                    @endif
                    @if($quotation->tax_amount > 0)
                    <tr class="line-row"><td>{{ $quotation->tax_label ?: 'Tax' }}{{ $quotation->tax_rate ? ' (' . rtrim(rtrim(number_format($quotation->tax_rate, 2), '0'), '.') . '%)' : '' }}</td><td class="text-right">+ {{ $quotation->currency }} {{ number_format($quotation->tax_amount, 2) }}</td></tr>
                    @endif
                    <tr class="grand-row">
                        <td>Grand Total</td>
                        <td class="text-right">{{ $quotation->currency }} {{ number_format($quotation->grand_total, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Terms & Conditions (and everything after it starts on a fresh page) -->
        @if(!empty($snap['terms_conditions']))
        <div class="page-break"></div>
        <div class="page-top-space"></div>
        <div class="section-box">
            <div class="section-label">Terms & Conditions</div>
            <div class="terms-text">{{ $snap['terms_conditions'] }}</div>
        </div>
        @endif

        <!-- Payment / Bank Information for inbound international wires -->
        @if(!empty($snap['bank_name']) || !empty($snap['bank_account_number']))
        <div class="section-box">
            <div class="section-label">Bank Details for International Wire Transfer</div>
            <table class="bank-table">
                @if(!empty($snap['bank_name']))
                <tr><td class="bank-key">Bank Name</td><td>{{ $snap['bank_name'] }}</td></tr>
                @endif
                @if(!empty($snap['bank_branch']))
                <tr><td class="bank-key">Branch</td><td>{{ $snap['bank_branch'] }}</td></tr>
                @endif
                @if(!empty($snap['swift_bic']))
                <tr><td class="bank-key">SWIFT / BIC Code</td><td><strong>{{ $snap['swift_bic'] }}</strong></td></tr>
                @endif
                @if(!empty($snap['bank_account_name']))
                <tr><td class="bank-key">Account Name (A/N)</td><td>{{ $snap['bank_account_name'] }}</td></tr>
                @endif
                @if(!empty($snap['bank_account_number']))
                <tr><td class="bank-key">Account Number</td><td>{{ $snap['bank_account_number'] }}</td></tr>
                @endif
                @if(!empty($snap['bank_account_currency']))
                <tr><td class="bank-key">Currency Accepted</td><td>{{ $snap['bank_account_currency'] }}</td></tr>
                @endif
                @if(!empty($snap['bank_address']))
                <tr><td class="bank-key">Bank Address</td><td>{{ $snap['bank_address'] }}</td></tr>
                @endif
                @if(!empty($snap['intermediary_bank_name']))
                <tr><td class="bank-key">Intermediary Bank</td><td>{{ $snap['intermediary_bank_name'] }}</td></tr>
                @endif
                @if(!empty($snap['intermediary_bank_swift']))
                <tr><td class="bank-key">Intermediary SWIFT / BIC</td><td>{{ $snap['intermediary_bank_swift'] }}</td></tr>
                @endif
                @if(!empty($snap['intermediary_bank_account']))
                <tr><td class="bank-key">Intermediary Account No.</td><td>{{ $snap['intermediary_bank_account'] }}</td></tr>
                @endif
                @if(!empty($snap['payment_charge_instruction']))
                <tr><td class="bank-key">Charge Instruction</td><td>{{ $snap['payment_charge_instruction'] }}</td></tr>
                @endif
                <tr><td class="bank-key">Payment Reference</td><td>{{ $quotation->quotation_number }}</td></tr>
            </table>
        </div>
        @endif

        <!-- Signature -->
        <div class="sign-wrap">
            <div class="sign-col"></div>
            <div class="sign-col">
                <div class="info-line" style="margin-bottom:2px;">Authorized By,</div>
                <div class="sign-space"></div>
                <div class="sign-line">
                    <div class="sign-name">{{ $snap['authorized_person_name'] ?? $snap['company_name'] ?? '' }}</div>
                    @if(!empty($snap['authorized_person_position']))<div class="sign-position">{{ $snap['authorized_person_position'] }}</div>@endif
                    <div class="sign-position">{{ $snap['company_name'] ?? '' }}</div>
                </div>
            </div>
        </div>

    </div>

    <div class="footer-note">
        This quotation is issued by {{ $snap['company_name'] ?? '' }} and is valid until {{ $quotation->valid_until->format('d M Y') }}. Generated on {{ now()->format('d M Y, H:i') }}.
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont("Helvetica");
            $pdf->page_text(500, 815, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, 8, array(0.55, 0.55, 0.55));
        }
    </script>

</body>
</html>
