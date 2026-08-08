@extends('admin.layouts.app')
@section('title', 'Edit Quotation')
@section('page-title', 'Edit Quotation ' . $quotation->quotation_number)

@section('content')
<div class="max-w-4xl">
    @php
        $initData = old() ?: [
            'buyer_id' => $quotation->buyer_id,
            'buyer_company_name' => $quotation->buyer_company_name,
            'buyer_contact_person' => $quotation->buyer_contact_person,
            'buyer_email' => $quotation->buyer_email,
            'buyer_phone' => $quotation->buyer_phone,
            'buyer_address' => $quotation->buyer_address,
            'buyer_city' => $quotation->buyer_city,
            'buyer_state_province' => $quotation->buyer_state_province,
            'buyer_postal_code' => $quotation->buyer_postal_code,
            'buyer_country_code' => $quotation->buyer_country_code,
            'buyer_tax_vat_number' => $quotation->buyer_tax_vat_number,
            'quotation_date' => $quotation->quotation_date->toDateString(),
            'validity_days' => $quotation->validity_days,
            'currency' => $quotation->currency,
            'incoterm' => $quotation->incoterm,
            'incoterm_place' => $quotation->incoterm_place,
            'destination_country_code' => $quotation->destination_country_code,
            'port_of_loading' => $quotation->port_of_loading,
            'port_of_discharge' => $quotation->port_of_discharge,
            'final_destination' => $quotation->final_destination,
            'shipping_method' => $quotation->shipping_method,
            'production_lead_time' => $quotation->production_lead_time,
            'estimated_shipment' => $quotation->estimated_shipment,
            'payment_terms' => $quotation->payment_terms,
            'moq' => $quotation->moq,
            'country_of_origin' => $quotation->country_of_origin,
            'discount_amount' => (float) $quotation->discount_amount,
            'freight_amount' => (float) $quotation->freight_amount,
            'insurance_amount' => (float) $quotation->insurance_amount,
            'other_charges_label' => $quotation->other_charges_label,
            'other_charges_amount' => (float) $quotation->other_charges_amount,
            'tax_label' => $quotation->tax_label,
            'tax_rate' => (float) $quotation->tax_rate,
            'tax_amount' => (float) $quotation->tax_amount,
            'notes' => $quotation->notes,
            'items' => $quotation->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'species' => $item->species,
                'grade' => $item->grade,
                'size' => $item->size,
                'weight' => $item->weight,
                'moisture' => $item->moisture,
                'packaging' => $item->packaging,
                'condition' => $item->condition,
                'aroma' => $item->aroma,
                'description' => $item->description,
                'hs_code' => $item->hs_code,
                'country_of_origin' => $item->country_of_origin,
                'quantity' => (float) $item->quantity,
                'unit' => $item->unit,
                'unit_price' => (float) $item->unit_price,
            ])->values()->all(),
        ];
    @endphp
    @include('admin.quotations._form', [
        'formAction' => route('admin.quotations.update', $quotation),
        'method' => 'PUT',
        'initData' => $initData,
    ])
</div>
@endsection
