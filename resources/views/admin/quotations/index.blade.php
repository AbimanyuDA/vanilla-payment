@extends('admin.layouts.app')
@section('title', 'Quotations')
@section('page-title', 'International Export Quotations')

@section('content')
<div class="card bg-base-100 shadow-sm">
    <div class="card-body p-4">
        <!-- Toolbar -->
        <div class="flex flex-col sm:flex-row gap-3 mb-4">
            <form method="GET" class="flex flex-1 gap-2 flex-wrap">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search quotation no. / buyer..."
                    class="input input-bordered input-sm flex-1 min-w-0">
                <select name="status" class="select select-bordered select-sm">
                    <option value="">All Status</option>
                    <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                    <option value="sent" @selected(request('status') === 'sent')>Sent</option>
                    <option value="accepted" @selected(request('status') === 'accepted')>Accepted</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                    <option value="expired" @selected(request('status') === 'expired')>Expired</option>
                    <option value="converted_to_proforma" @selected(request('status') === 'converted_to_proforma')>Converted to Proforma</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                </select>
                <button type="submit" class="btn btn-sm btn-neutral">Filter</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('admin.quotations.index') }}" class="btn btn-sm btn-ghost">Reset</a>
                @endif
            </form>
            <a href="{{ route('admin.quotations.create') }}" class="btn btn-primary btn-sm whitespace-nowrap">+ Create Quotation</a>
        </div>

        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Quotation No.</th>
                        <th>Buyer</th>
                        <th>Incoterm</th>
                        <th>Grand Total</th>
                        <th>Status</th>
                        <th>Valid Until</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($quotations as $quotation)
                    <tr class="hover">
                        <td class="font-mono text-xs">{{ $quotation->quotation_number }}</td>
                        <td>{{ $quotation->buyer_company_name }}</td>
                        <td class="text-xs">{{ $quotation->incoterm }} {{ $quotation->incoterm_place }}</td>
                        <td class="font-semibold">{{ $quotation->currency }} {{ number_format($quotation->grand_total, 2) }}</td>
                        <td><span class="badge badge-sm {{ $quotation->status_badge }}">{{ $quotation->status_label }}</span></td>
                        <td class="text-sm {{ $quotation->isExpired() ? 'text-error' : '' }}">
                            {{ $quotation->valid_until?->format('d/m/Y') ?? '-' }}
                        </td>
                        <td>
                            <div class="flex gap-1">
                                <a href="{{ route('admin.quotations.show', $quotation) }}" class="btn btn-ghost btn-xs">Detail</a>
                                @if(!in_array($quotation->status, ['accepted', 'converted_to_proforma', 'cancelled']))
                                    <a href="{{ route('admin.quotations.edit', $quotation) }}" class="btn btn-ghost btn-xs">Edit</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-base-content/40 py-8">No quotations found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $quotations->links() }}
        </div>
    </div>
</div>
@endsection
