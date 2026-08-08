@extends('admin.layouts.app')
@section('title', 'Add Product')
@section('page-title', 'Add Product')

@section('content')
<div class="max-w-2xl">
    @include('admin.products._form', ['formAction' => route('admin.products.store'), 'method' => 'POST'])
</div>
@endsection
