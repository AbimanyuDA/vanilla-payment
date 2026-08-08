@extends('admin.layouts.app')
@section('title', 'Create Quotation')
@section('page-title', 'Create International Export Quotation')

@section('content')
<div class="max-w-4xl">
    @php
        $initData = old() ?: [
            'quotation_date' => now()->toDateString(),
            'validity_days' => 14,
            'currency' => 'USD',
            'incoterm' => 'FOB',
            'country_of_origin' => 'Indonesia',
            'items' => [],
        ];
    @endphp
    @include('admin.quotations._form', [
        'formAction' => route('admin.quotations.store'),
        'method' => 'POST',
        'initData' => $initData,
    ])
</div>
@endsection
