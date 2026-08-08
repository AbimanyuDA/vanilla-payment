@extends('admin.layouts.app')
@section('title', 'Add Buyer')
@section('page-title', 'Add Buyer')

@section('content')
<div class="max-w-2xl">
    @include('admin.buyers._form', ['formAction' => route('admin.buyers.store'), 'method' => 'POST'])
</div>
@endsection
