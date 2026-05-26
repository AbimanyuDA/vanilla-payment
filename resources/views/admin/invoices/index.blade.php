@extends('admin.layouts.app')
@section('title', 'Daftar Invoice')
@section('page-title', 'Daftar Invoice')

@section('content')
<div class="card bg-base-100 shadow-sm">
    <div class="card-body p-4">
        <!-- Toolbar -->
        <div class="flex flex-col sm:flex-row gap-3 mb-4">
            <form method="GET" class="flex flex-1 gap-2 flex-wrap">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari no. invoice / customer..."
                    class="input input-bordered input-sm flex-1 min-w-0">
                <select name="status" class="select select-bordered select-sm">
                    <option value="">Semua Status</option>
                    <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                    <option value="sent" @selected(request('status') === 'sent')>Terkirim</option>
                    <option value="pending_confirmation" @selected(request('status') === 'pending_confirmation')>Menunggu Konfirmasi</option>
                    <option value="paid" @selected(request('status') === 'paid')>Lunas</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Dibatalkan</option>
                </select>
                <button type="submit" class="btn btn-sm btn-neutral">Filter</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('admin.invoices.index') }}" class="btn btn-sm btn-ghost">Reset</a>
                @endif
            </form>
            <a href="{{ route('admin.invoices.create') }}" class="btn btn-primary btn-sm whitespace-nowrap">+ Buat Invoice</a>
        </div>

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
                        <th>Jatuh Tempo</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    <tr class="hover">
                        <td class="font-mono text-xs">{{ $invoice->invoice_number }}</td>
                        <td>{{ $invoice->customer_name }}</td>
                        <td>{{ $invoice->customer_phone ?? '-' }}</td>
                        <td class="font-semibold">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                        <td>
                            @if($invoice->payment_method === 'transfer')
                                <span class="badge badge-info badge-sm">Transfer</span>
                            @elseif($invoice->payment_method === 'qris')
                                <span class="badge badge-accent badge-sm">QRIS</span>
                            @else
                                <span class="text-base-content/40">-</span>
                            @endif
                        </td>
                        <td><span class="badge badge-sm {{ $invoice->status_badge }}">{{ $invoice->status_label }}</span></td>
                        <td class="text-sm {{ $invoice->isExpired() ? 'text-error' : '' }}">
                            {{ $invoice->due_date?->format('d/m/Y') ?? '-' }}
                        </td>
                        <td>
                            <div class="flex gap-1">
                                <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-ghost btn-xs">Detail</a>
                                @if(!in_array($invoice->status, ['paid', 'cancelled']))
                                    <a href="{{ route('admin.invoices.edit', $invoice) }}" class="btn btn-ghost btn-xs">Edit</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-base-content/40 py-8">Tidak ada invoice ditemukan</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $invoices->links() }}
        </div>
    </div>
</div>
@endsection
