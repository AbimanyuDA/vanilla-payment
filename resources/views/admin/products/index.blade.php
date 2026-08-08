@extends('admin.layouts.app')
@section('title', 'Products')
@section('page-title', 'Product Master')

@section('content')
<div class="card bg-base-100 shadow-sm">
    <div class="card-body p-4">
        <div class="flex flex-col sm:flex-row gap-3 mb-4">
            <form method="GET" class="flex flex-1 gap-2 flex-wrap">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search product name..."
                    class="input input-bordered input-sm flex-1 min-w-0">
                <select name="category" class="select select-bordered select-sm">
                    <option value="">All Categories</option>
                    <option value="raw" @selected(request('category') === 'raw')>Raw</option>
                    <option value="value_added" @selected(request('category') === 'value_added')>Value-Added</option>
                </select>
                <button type="submit" class="btn btn-sm btn-neutral">Filter</button>
                @if(request()->hasAny(['search', 'category']))
                    <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-ghost">Reset</a>
                @endif
            </form>
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm whitespace-nowrap">+ Add Product</a>
        </div>

        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Species</th>
                        <th>Category</th>
                        <th>HS Code</th>
                        <th>Attributes</th>
                        <th>Active</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr class="hover">
                        <td class="font-medium">{{ $product->name }}</td>
                        <td class="text-sm">{{ $product->species ?? '-' }}</td>
                        <td class="text-sm">{{ $product->category === 'raw' ? 'Raw' : 'Value-Added' }}</td>
                        <td class="text-sm font-mono">{{ $product->hs_code ?? '-' }}</td>
                        <td class="text-sm">{{ $product->product_attributes_count }}</td>
                        <td>
                            <span class="badge badge-sm {{ $product->is_active ? 'badge-success' : 'badge-ghost' }}">
                                {{ $product->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <div class="flex gap-1">
                                <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-ghost btn-xs">Edit</a>
                                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="inline"
                                    onsubmit="return confirm('Delete this product?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-ghost btn-xs text-error">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-base-content/40 py-8">No products found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $products->links() }}</div>
    </div>
</div>
@endsection
