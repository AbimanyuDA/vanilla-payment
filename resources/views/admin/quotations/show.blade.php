@extends('admin.layouts.app')
@section('title', $quotation->quotation_number)
@section('page-title', 'Quotation ' . $quotation->quotation_number)

@section('content')
<div class="max-w-3xl" x-data="{ previewOpen: false }">
    <!-- Actions Bar -->
    <div class="flex flex-wrap gap-2 mb-4">
        <a href="{{ route('admin.quotations.index') }}" class="btn btn-ghost btn-sm">← Back</a>

        @if(!in_array($quotation->status, ['accepted', 'converted_to_proforma', 'cancelled']))
            <a href="{{ route('admin.quotations.edit', $quotation) }}" class="btn btn-sm btn-outline">Edit</a>
        @endif

        <button type="button" @click="previewOpen = true" class="btn btn-sm btn-outline">Preview PDF</button>

        <a href="{{ route('admin.quotations.pdf', $quotation) }}" class="btn btn-sm btn-outline" target="_blank">Download PDF</a>

        <!-- Status Dropdown -->
        <div class="dropdown" x-data="{ open: false }">
            <button class="btn btn-sm btn-primary" @click="open = !open">Change Status ▼</button>
            <ul x-show="open" @click.away="open = false" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-56 z-10">
                @foreach(['draft' => 'Draft', 'sent' => 'Sent', 'accepted' => 'Accepted', 'rejected' => 'Rejected', 'expired' => 'Expired', 'cancelled' => 'Cancelled'] as $value => $label)
                    @if($value !== $quotation->status)
                    <li>
                        <form method="POST" action="{{ route('admin.quotations.status', $quotation) }}">
                            @csrf
                            <input type="hidden" name="status" value="{{ $value }}">
                            <button type="submit" class="w-full text-left">{{ $label }}</button>
                        </form>
                    </li>
                    @endif
                @endforeach
            </ul>
        </div>

        @if($quotation->status === 'accepted')
            <button class="btn btn-sm btn-outline" disabled title="Coming soon">Convert to Proforma Invoice</button>
        @endif

        @if(!in_array($quotation->status, ['accepted', 'converted_to_proforma']))
            <form method="POST" action="{{ route('admin.quotations.destroy', $quotation) }}" class="inline"
                onsubmit="return confirm('Delete this quotation? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-error btn-outline">Delete</button>
            </form>
        @endif
    </div>

    <!-- Quotation Card -->
    <div class="card bg-base-100 shadow-sm mb-4">
        <div class="card-body p-4 sm:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <img src="{{ asset('images/logo/CleanlLogo.webp') }}" alt="" class="w-8 h-8 object-contain">
                        <span class="font-bold text-lg">Vanilla Royal</span>
                    </div>
                    <p class="text-sm text-base-content/60">International Export Quotation</p>
                </div>
                <div class="text-right">
                    <div class="font-mono font-bold text-lg">{{ $quotation->quotation_number }}</div>
                    <span class="badge {{ $quotation->status_badge }}">{{ $quotation->status_label }}</span>
                    <div class="text-sm text-base-content/60 mt-1">Date: {{ $quotation->quotation_date->format('d M Y') }}</div>
                    <div class="text-sm {{ $quotation->isExpired() ? 'text-error' : 'text-base-content/60' }}">
                        Valid Until: {{ $quotation->valid_until->format('d M Y') }}
                        @if($quotation->isExpired()) (Expired) @endif
                    </div>
                </div>
            </div>

            <!-- Buyer + Trade Terms -->
            <div class="grid sm:grid-cols-2 gap-4 mb-4">
                <div class="bg-base-200 rounded-lg p-3">
                    <div class="text-xs font-semibold text-base-content/60 mb-2">BUYER</div>
                    <div class="font-semibold">{{ $quotation->buyer_company_name }}</div>
                    @if($quotation->buyer_contact_person)<div class="text-sm">{{ $quotation->buyer_contact_person }}</div>@endif
                    @if($quotation->buyer_email)<div class="text-sm">{{ $quotation->buyer_email }}</div>@endif
                    @if($quotation->buyer_phone)<div class="text-sm">{{ $quotation->buyer_phone }}</div>@endif
                    @if($quotation->buyer_address)<div class="text-sm mt-1 text-base-content/70">{{ $quotation->buyer_address }}</div>@endif
                    <div class="text-sm text-base-content/70">
                        {{ collect([$quotation->buyer_city, $quotation->buyer_state_province, $quotation->buyer_postal_code, $quotation->buyer_country])->filter()->implode(', ') }}
                    </div>
                    @if($quotation->buyer_tax_vat_number)<div class="text-xs text-base-content/50 mt-1">Tax/VAT: {{ $quotation->buyer_tax_vat_number }}</div>@endif
                </div>
                <div class="bg-base-200 rounded-lg p-3">
                    <div class="text-xs font-semibold text-base-content/60 mb-2">TRADE TERMS</div>
                    <div class="text-sm"><strong>Incoterm:</strong> {{ $quotation->incoterm }} {{ $quotation->incoterm_place }}</div>
                    <div class="text-sm"><strong>Currency:</strong> {{ $quotation->currency }}</div>
                    @if($quotation->payment_terms)<div class="text-sm"><strong>Payment:</strong> {{ $quotation->payment_terms }}</div>@endif
                    @if($quotation->moq)<div class="text-sm"><strong>MOQ:</strong> {{ $quotation->moq }}</div>@endif
                    <div class="text-sm"><strong>Country of Origin:</strong> {{ $quotation->country_of_origin }}</div>
                    @if($quotation->destination_country)<div class="text-sm"><strong>Destination:</strong> {{ $quotation->destination_country }}</div>@endif
                </div>
            </div>

            @if($quotation->port_of_loading || $quotation->port_of_discharge || $quotation->final_destination || $quotation->shipping_method || $quotation->production_lead_time || $quotation->estimated_shipment)
            <div class="bg-base-200 rounded-lg p-3 mb-4">
                <div class="text-xs font-semibold text-base-content/60 mb-2">SHIPPING INFORMATION</div>
                <div class="grid sm:grid-cols-2 gap-x-4 text-sm">
                    @if($quotation->port_of_loading)<div><strong>Port of Loading:</strong> {{ $quotation->port_of_loading }}</div>@endif
                    @if($quotation->port_of_discharge)<div><strong>Port of Discharge:</strong> {{ $quotation->port_of_discharge }}</div>@endif
                    @if($quotation->final_destination)<div><strong>Final Destination:</strong> {{ $quotation->final_destination }}</div>@endif
                    @if($quotation->shipping_method)<div><strong>Shipping Method:</strong> {{ $quotation->shipping_method }}</div>@endif
                    @if($quotation->production_lead_time)<div><strong>Production Lead Time:</strong> {{ $quotation->production_lead_time }}</div>@endif
                    @if($quotation->estimated_shipment)<div><strong>Estimated Shipment:</strong> {{ $quotation->estimated_shipment }}</div>@endif
                </div>
            </div>
            @endif

            <!-- Items Table -->
            <div class="overflow-x-auto mb-4">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Spec</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right">Unit Price</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($quotation->items as $item)
                        <tr>
                            <td>
                                <div class="font-medium">{{ $item->product_name }}</div>
                                @if($item->description)<div class="text-xs text-base-content/60">{{ $item->description }}</div>@endif
                            </td>
                            <td class="text-xs text-base-content/60">
                                {{ collect([$item->grade, $item->size, $item->weight, $item->moisture, $item->packaging])->filter()->implode(' · ') }}
                            </td>
                            <td class="text-right">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }} {{ $item->unit }}</td>
                            <td class="text-right">{{ $quotation->currency }} {{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-right font-medium">{{ $quotation->currency }} {{ number_format($item->line_total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Totals -->
            <div class="flex justify-end">
                <div class="w-full max-w-xs space-y-1 text-sm">
                    <div class="flex justify-between"><span>Subtotal</span><span>{{ $quotation->currency }} {{ number_format($quotation->subtotal, 2) }}</span></div>
                    @if($quotation->discount_amount > 0)
                    <div class="flex justify-between text-success"><span>Discount</span><span>- {{ $quotation->currency }} {{ number_format($quotation->discount_amount, 2) }}</span></div>
                    @endif
                    @if($quotation->freight_amount > 0)
                    <div class="flex justify-between"><span>Freight</span><span>+ {{ $quotation->currency }} {{ number_format($quotation->freight_amount, 2) }}</span></div>
                    @endif
                    @if($quotation->insurance_amount > 0)
                    <div class="flex justify-between"><span>Insurance</span><span>+ {{ $quotation->currency }} {{ number_format($quotation->insurance_amount, 2) }}</span></div>
                    @endif
                    @if($quotation->other_charges_amount > 0)
                    <div class="flex justify-between"><span>{{ $quotation->other_charges_label ?: 'Other Charges' }}</span><span>+ {{ $quotation->currency }} {{ number_format($quotation->other_charges_amount, 2) }}</span></div>
                    @endif
                    @if($quotation->tax_amount > 0)
                    <div class="flex justify-between"><span>{{ $quotation->tax_label ?: 'Tax' }}{{ $quotation->tax_rate ? ' (' . rtrim(rtrim(number_format($quotation->tax_rate, 2), '0'), '.') . '%)' : '' }}</span><span>+ {{ $quotation->currency }} {{ number_format($quotation->tax_amount, 2) }}</span></div>
                    @endif
                    <div class="flex justify-between font-bold text-base border-t pt-2 mt-2">
                        <span>Grand Total</span>
                        <span>{{ $quotation->currency }} {{ number_format($quotation->grand_total, 2) }}</span>
                    </div>
                </div>
            </div>

            @if($quotation->notes)
            <div class="mt-4 p-3 bg-base-200 rounded-lg">
                <div class="text-xs font-semibold text-base-content/60 mb-1">NOTES</div>
                <p class="text-sm">{{ $quotation->notes }}</p>
            </div>
            @endif

            <div class="mt-4 text-xs text-base-content/40">
                Created by {{ $quotation->creator?->name ?? '-' }} on {{ $quotation->created_at->format('d M Y H:i') }}
                @if($quotation->pdf_generated_at)
                    &bull; PDF last generated {{ $quotation->pdf_generated_at->format('d M Y H:i') }}
                @endif
            </div>
        </div>
    </div>

    <!-- PDF preview: renders the actual document, so it cannot drift from the download. -->
    <div x-show="previewOpen" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="background: rgba(44,24,16,0.65);"
        @keydown.escape.window="previewOpen = false">
        <div @click.outside="previewOpen = false"
            class="bg-base-100 rounded-xl shadow-2xl w-full max-w-5xl flex flex-col" style="height: 90vh;">
            <div class="flex items-center justify-between px-4 py-3 border-b">
                <div>
                    <div class="font-semibold">PDF Preview</div>
                    <div class="text-xs text-base-content/50">{{ $quotation->quotation_number }} — exactly what will be downloaded</div>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.quotations.pdf', $quotation) }}" class="btn btn-sm btn-primary">Download</a>
                    <button type="button" @click="previewOpen = false" class="btn btn-sm btn-ghost">Close</button>
                </div>
            </div>
            <div class="flex-1 p-3" style="background:#525659; border-radius: 0 0 0.75rem 0.75rem;">
                <template x-if="previewOpen">
                    <iframe src="{{ route('admin.quotations.pdf-preview', $quotation) }}"
                        class="w-full h-full rounded" style="border:0;" title="Quotation PDF preview"></iframe>
                </template>
            </div>
        </div>
    </div>
</div>

@push('head')
<style>[x-cloak] { display: none !important; }</style>
@endpush
@endsection
