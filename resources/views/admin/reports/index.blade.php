@extends('admin.layouts.app')
@section('title', 'Laporan')
@section('page-title', 'Laporan Invoice')

@section('content')
<div class="card bg-base-100 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="form-control">
                <label class="label"><span class="label-text text-xs">Dari Tanggal</span></label>
                <input type="date" name="from" value="{{ request('from') }}" class="input input-bordered input-sm">
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text text-xs">Sampai Tanggal</span></label>
                <input type="date" name="to" value="{{ request('to') }}" class="input input-bordered input-sm">
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text text-xs">Status</span></label>
                <select name="status" class="select select-bordered select-sm">
                    <option value="">Semua</option>
                    <option value="paid" @selected(request('status') === 'paid')>Lunas</option>
                    <option value="pending_confirmation" @selected(request('status') === 'pending_confirmation')>Menunggu</option>
                    <option value="sent" @selected(request('status') === 'sent')>Terkirim</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Dibatalkan</option>
                </select>
            </div>
            <button type="submit" class="btn btn-sm btn-neutral">Filter</button>
            <a href="{{ route('admin.reports.export-pdf', request()->query()) }}" class="btn btn-sm btn-error" target="_blank">Export PDF</a>
            <a href="{{ route('admin.reports.export-excel', request()->query()) }}" class="btn btn-sm btn-success">Export Excel</a>
        </form>
    </div>
</div>

<div class="stats bg-base-100 shadow-sm mb-4 w-full">
    <div class="stat">
        <div class="stat-title">Total Invoice</div>
        <div class="stat-value text-2xl">{{ $invoices->count() }}</div>
    </div>
    <div class="stat">
        <div class="stat-title">Total Revenue (Lunas)</div>
        <div class="stat-value text-2xl text-success">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
    </div>
    <div class="stat">
        <div class="stat-title">Rata-rata Invoice</div>
        <div class="stat-value text-2xl">Rp {{ $invoices->count() ? number_format($invoices->avg('total_amount'), 0, ',', '.') : '0' }}</div>
    </div>
</div>

<div class="card bg-base-100 shadow-sm">
    <div class="card-body p-4">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>No. Invoice</th>
                        <th>Customer</th>
                        <th>Telepon</th>
                        <th>Total</th>
                        <th>Metode</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th>Lunas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    <tr class="hover cursor-pointer" onclick="window.location='{{ route('admin.invoices.show', $invoice) }}'">
                        <td class="font-mono text-xs">{{ $invoice->invoice_number }}</td>
                        <td>{{ $invoice->customer_name }}</td>
                        <td>{{ $invoice->customer_phone ?? '-' }}</td>
                        <td class="font-semibold">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                        <td>{{ $invoice->payment_method ? ($invoice->payment_method === 'qris' ? 'QRIS' : 'Transfer') : '-' }}</td>
                        <td><span class="badge badge-sm {{ $invoice->status_badge }}">{{ $invoice->status_label }}</span></td>
                        <td>{{ $invoice->created_at->format('d/m/Y') }}</td>
                        <td>{{ $invoice->paid_at?->format('d/m/Y') ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-base-content/40 py-8">Tidak ada data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
