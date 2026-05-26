@extends('admin.layouts.app')
@section('title', $invoice->invoice_number)
@section('page-title', 'Invoice ' . $invoice->invoice_number)

@section('content')
<div class="max-w-3xl">
    <!-- Actions Bar -->
    <div class="flex flex-wrap gap-2 mb-4">
        <a href="{{ route('admin.invoices.index') }}" class="btn btn-ghost btn-sm">← Kembali</a>

        @if(!in_array($invoice->status, ['paid', 'cancelled']))
            <a href="{{ route('admin.invoices.edit', $invoice) }}" class="btn btn-sm btn-outline">Edit</a>
        @endif

        <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="btn btn-sm btn-outline" target="_blank">Download PDF</a>

        @if(in_array($invoice->status, ['sent', 'pending_confirmation']))
            <!-- Send Notification Dropdown -->
            <div class="dropdown" x-data="{ open: false }">
                <button class="btn btn-sm btn-primary" @click="open = !open">Kirim ke Customer ▼</button>
                <ul x-show="open" @click.away="open = false" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-52 z-10">
                    @if($invoice->customer_phone)
                    <li>
                        <form method="POST" action="{{ route('admin.invoices.send-notification', $invoice) }}">
                            @csrf
                            <input type="hidden" name="channel" value="whatsapp">
                            <button type="submit" class="w-full text-left">📱 WhatsApp</button>
                        </form>
                    </li>
                    @endif
                    @if($invoice->customer_email)
                    <li>
                        <form method="POST" action="{{ route('admin.invoices.send-notification', $invoice) }}">
                            @csrf
                            <input type="hidden" name="channel" value="email">
                            <button type="submit" class="w-full text-left">📧 Email</button>
                        </form>
                    </li>
                    @endif
                </ul>
            </div>
        @endif

        @if(!in_array($invoice->status, ['paid', 'cancelled']))
            <form method="POST" action="{{ route('admin.invoices.cancel', $invoice) }}" class="inline"
                onsubmit="return confirm('Yakin ingin membatalkan invoice ini?')">
                @csrf
                <button class="btn btn-sm btn-error btn-outline">Batalkan</button>
            </form>
        @endif
    </div>

    <!-- Invoice Card -->
    <div class="card bg-base-100 shadow-sm mb-4">
        <div class="card-body p-4 sm:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center">
                            <span class="text-white font-bold">V</span>
                        </div>
                        <span class="font-bold text-lg">Vanilla Royal</span>
                    </div>
                    <p class="text-sm text-base-content/60">Payment System</p>
                </div>
                <div class="text-right">
                    <div class="font-mono font-bold text-lg">{{ $invoice->invoice_number }}</div>
                    <span class="badge {{ $invoice->status_badge }}">{{ $invoice->status_label }}</span>
                    <div class="text-sm text-base-content/60 mt-1">{{ $invoice->created_at->format('d M Y') }}</div>
                    @if($invoice->due_date)
                        <div class="text-sm {{ $invoice->isExpired() ? 'text-error' : 'text-base-content/60' }}">
                            Jatuh Tempo: {{ $invoice->due_date->format('d M Y') }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Customer Info -->
            <div class="bg-base-200 rounded-lg p-3 mb-4">
                <div class="text-xs font-semibold text-base-content/60 mb-2">TAGIHAN KEPADA</div>
                <div class="font-semibold">{{ $invoice->customer_name }}</div>
                @if($invoice->customer_phone)<div class="text-sm">{{ $invoice->customer_phone }}</div>@endif
                @if($invoice->customer_email)<div class="text-sm">{{ $invoice->customer_email }}</div>@endif
                @if($invoice->customer_address)<div class="text-sm mt-1 text-base-content/70">{{ $invoice->customer_address }}</div>@endif
            </div>

            <!-- Items Table -->
            <div class="overflow-x-auto mb-4">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="text-center">Qty</th>
                            <th class="text-right">Harga</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $item)
                        <tr>
                            <td>
                                <div class="font-medium">{{ $item->product_name }}</div>
                                @if($item->description)<div class="text-xs text-base-content/60">{{ $item->description }}</div>@endif
                            </td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="text-right font-medium">Rp {{ number_format($item->total_price, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Totals -->
            <div class="flex justify-end">
                <div class="w-full max-w-xs space-y-1 text-sm">
                    <div class="flex justify-between"><span>Subtotal</span><span>Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span></div>
                    @foreach($invoice->extras as $extra)
                    <div class="flex justify-between text-base-content/70">
                        <span>{{ $extra->label }}</span>
                        <span>+ Rp {{ number_format($extra->amount, 0, ',', '.') }}</span>
                    </div>
                    @endforeach
                    @if($invoice->discount > 0)
                    <div class="flex justify-between text-success"><span>Diskon</span><span>- Rp {{ number_format($invoice->discount, 0, ',', '.') }}</span></div>
                    @endif
                    <div class="flex justify-between font-bold text-base border-t pt-2 mt-2">
                        <span>Total</span>
                        <span>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                    </div>
                    @if($invoice->payment_method)
                    <div class="flex justify-between text-base-content/60">
                        <span>Metode</span>
                        <span>{{ $invoice->payment_method === 'qris' ? 'QRIS' : 'Transfer Bank' }}</span>
                    </div>
                    @endif
                    @if($invoice->paid_at)
                    <div class="flex justify-between text-success">
                        <span>Lunas</span>
                        <span>{{ $invoice->paid_at->format('d M Y H:i') }}</span>
                    </div>
                    @endif
                </div>
            </div>

            @if($invoice->notes)
            <div class="mt-4 p-3 bg-base-200 rounded-lg">
                <div class="text-xs font-semibold text-base-content/60 mb-1">CATATAN</div>
                <p class="text-sm">{{ $invoice->notes }}</p>
            </div>
            @endif

            <!-- Payment Link -->
            <div class="mt-4 p-3 border border-dashed rounded-lg">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-semibold text-base-content/60">LINK PEMBAYARAN CUSTOMER</span>
                </div>
                <div class="flex gap-2 items-center">
                    <input type="text" value="{{ route('payment.show', $invoice->payment_token) }}"
                        class="input input-bordered input-sm flex-1" readonly id="paymentLink">
                    <button onclick="copyLink()" class="btn btn-sm btn-outline">Salin</button>
                    <a href="{{ route('payment.show', $invoice->payment_token) }}" target="_blank" class="btn btn-sm btn-ghost">Buka</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Proofs -->
    @if($invoice->paymentProofs->count() > 0)
    <div class="card bg-base-100 shadow-sm mb-4">
        <div class="card-body p-4">
            <h2 class="card-title text-base mb-4">Bukti Transfer</h2>

            @foreach($invoice->paymentProofs as $proof)
            <div class="border rounded-lg p-3 mb-3">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <span class="font-medium text-sm">{{ $proof->file_name }}</span>
                        <div class="text-xs text-base-content/60">{{ $proof->created_at->format('d M Y H:i') }}</div>
                    </div>
                    <span class="badge badge-sm {{ $proof->status === 'confirmed' ? 'badge-success' : ($proof->status === 'rejected' ? 'badge-error' : 'badge-warning') }}">
                        {{ ucfirst($proof->status) }}
                    </span>
                </div>
                @if($proof->isPdf())
                    <a href="{{ $proof->file_url }}" target="_blank" class="btn btn-xs btn-outline mb-2">Lihat Bukti (PDF)</a>
                @else
                    <div class="mb-2">
                        <img src="{{ $proof->file_url }}" alt="Bukti Transfer" class="max-h-48 rounded-lg border cursor-pointer"
                            onclick="window.open(this.src)">
                    </div>
                @endif

                @if($proof->admin_notes)
                    <p class="text-xs text-base-content/60">Catatan: {{ $proof->admin_notes }}</p>
                @endif
            </div>
            @endforeach

            <!-- Confirm/Reject Form -->
            @if($invoice->status === 'pending_confirmation' && $invoice->latestProof?->status === 'pending')
            <div class="border-t pt-4 mt-2" x-data="{ action: '' }">
                <h3 class="font-semibold text-sm mb-3">Konfirmasi Pembayaran</h3>
                <form method="POST" action="{{ route('admin.invoices.confirm-payment', $invoice) }}">
                    @csrf
                    <div class="form-control mb-3">
                        <label class="label"><span class="label-text text-sm">Catatan Admin (opsional)</span></label>
                        <textarea name="admin_notes" class="textarea textarea-bordered textarea-sm" rows="2"></textarea>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" name="action" value="confirm" class="btn btn-success btn-sm flex-1"
                            onclick="return confirm('Konfirmasi pembayaran ini? Invoice akan ditandai LUNAS.')">
                            ✓ Konfirmasi Lunas
                        </button>
                        <button type="submit" name="action" value="reject" class="btn btn-error btn-sm flex-1"
                            onclick="return confirm('Tolak bukti transfer ini?')">
                            ✕ Tolak
                        </button>
                    </div>
                </form>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
function copyLink() {
    const el = document.getElementById('paymentLink');
    el.select();
    navigator.clipboard.writeText(el.value);
    alert('Link berhasil disalin!');
}
</script>
@endpush
@endsection
