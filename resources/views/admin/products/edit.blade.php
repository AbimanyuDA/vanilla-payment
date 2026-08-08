@extends('admin.layouts.app')
@section('title', 'Edit Product')
@section('page-title', 'Edit Product')

@section('content')
<div class="max-w-2xl">
    @include('admin.products._form', ['formAction' => route('admin.products.update', $product), 'method' => 'PUT'])
</div>
@endsection
