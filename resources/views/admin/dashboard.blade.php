@extends('admin.layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<!-- Stats -->
<div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
    <div class="stat bg-base-100 rounded-box shadow-sm">
        <div class="stat-title text-xs">Total Invoice</div>
        <div class="stat-value text-2xl">{{ $stats['total'] }}</div>
    </div>
    <div class="stat bg-base-100 rounded-box shadow-sm">
        <div class="stat-title text-xs">Draft</div>
        <div class="stat-value text-2xl text-base-content/40">{{ $stats['draft'] }}</div>
    </div>
    <div class="stat bg-base-100 rounded-box shadow-sm">
        <div class="stat-title text-xs">Terkirim</div>
        <div class="stat-value text-2xl text-info">{{ $stats['sent'] }}</div>
    </div>
    <div class="stat bg-base-100 rounded-box shadow-sm">
        <div class="stat-title text-xs">Menunggu</div>
        <div class="stat-value text-2xl text-warning">{{ $stats['pending'] }}</div>
    </div>
    <div class="stat bg-base-100 rounded-box shadow-sm">
        <div class="stat-title text-xs">Lunas</div>
        <div class="stat-value text-2xl text-success">{{ $stats['paid'] }}</div>
    </div>
    <div class="stat bg-base-100 rounded-box shadow-sm col-span-2 lg:col-span-1">
        <div class="stat-title text-xs">Total Revenue</div>
        <div class="stat-value text-lg text-primary">Rp {{ number_format($stats['total_revenue'], 0, ',', '.') }}</div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Recent Invoices -->
    <div class="lg:col-span-2">
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body p-4">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="card-title text-base">Invoice Terbaru</h2>
                    <a href="{{ route('admin.invoices.create') }}" class="btn btn-primary btn-sm">+ Buat Invoice</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>No. Invoice</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentInvoices as $invoice)
                            <tr class="hover cursor-pointer" onclick="window.location='{{ route('admin.invoices.show', $invoice) }}'">
                                <td class="font-mono text-xs">{{ $invoice->invoice_number }}</td>
                                <td>{{ $invoice->customer_name }}</td>
                                <td>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                                <td><span class="badge badge-sm {{ $invoice->status_badge }}">{{ $invoice->status_label }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-base-content/40">Belum ada invoice</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Confirmation -->
    <div>
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body p-4">
                <h2 class="card-title text-base mb-4">Butuh Konfirmasi</h2>
                @forelse($pendingProofs as $invoice)
                <div class="border rounded-lg p-3 mb-3">
                    <div class="font-medium text-sm">{{ $invoice->customer_name }}</div>
                    <div class="text-xs text-base-content/60 font-mono">{{ $invoice->invoice_number }}</div>
                    <div class="font-semibold text-sm mt-1">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</div>
                    <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-warning btn-xs mt-2 w-full">Konfirmasi</a>
                </div>
                @empty
                <p class="text-base-content/40 text-sm text-center py-4">Tidak ada yang menunggu konfirmasi</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
