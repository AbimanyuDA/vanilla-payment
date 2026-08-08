@extends('admin.layouts.app')
@section('title', 'Buyers')
@section('page-title', 'Buyers')

@section('content')
<div class="card bg-base-100 shadow-sm">
    <div class="card-body p-4">
        <div class="flex flex-col sm:flex-row gap-3 mb-4">
            <form method="GET" class="flex flex-1 gap-2 flex-wrap">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search buyer name / country..."
                    class="input input-bordered input-sm flex-1 min-w-0">
                <button type="submit" class="btn btn-sm btn-neutral">Filter</button>
                @if(request()->hasAny(['search']))
                    <a href="{{ route('admin.buyers.index') }}" class="btn btn-sm btn-ghost">Reset</a>
                @endif
            </form>
            <a href="{{ route('admin.buyers.create') }}" class="btn btn-primary btn-sm whitespace-nowrap">+ Add Buyer</a>
        </div>

        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Contact</th>
                        <th>Country</th>
                        <th>Quotations</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($buyers as $buyer)
                    <tr class="hover">
                        <td class="font-medium">{{ $buyer->company_name }}</td>
                        <td class="text-sm">{{ $buyer->contact_person ?? '-' }}</td>
                        <td class="text-sm">{{ $buyer->country ?? '-' }}</td>
                        <td class="text-sm">{{ $buyer->quotations_count }}</td>
                        <td>
                            <div class="flex gap-1">
                                <a href="{{ route('admin.buyers.edit', $buyer) }}" class="btn btn-ghost btn-xs">Edit</a>
                                <form method="POST" action="{{ route('admin.buyers.destroy', $buyer) }}" class="inline"
                                    onsubmit="return confirm('Delete this buyer?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-ghost btn-xs text-error">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-base-content/40 py-8">No buyers found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $buyers->links() }}</div>
    </div>
</div>
@endsection
