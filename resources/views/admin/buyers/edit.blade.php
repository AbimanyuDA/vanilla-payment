@extends('admin.layouts.app')
@section('title', 'Edit Buyer')
@section('page-title', 'Edit Buyer')

@section('content')
<div class="max-w-2xl">
    @include('admin.buyers._form', ['formAction' => route('admin.buyers.update', $buyer), 'method' => 'PUT'])
</div>
@endsection
