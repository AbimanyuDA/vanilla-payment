<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }} — Vanilla Royal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(160deg, #2c1810 0%, #41281b 100%); min-height: 100vh; }
        .card-vr { background: #fff; border-radius: 16px; box-shadow: 0 8px 32px rgba(44,24,16,0.18); }
        .btn-primary-vr { background: linear-gradient(135deg, #f29923, #e08810); color: #2c1810; border: none; font-weight: 600; border-radius: 10px; padding: 0.75rem 1.5rem; cursor: pointer; transition: opacity .2s; width: 100%; }
        .btn-primary-vr:hover { opacity: 0.9; }
        .method-card { border: 2px solid #e5e7eb; border-radius: 14px; padding: 1.25rem; text-align: center; cursor: pointer; transition: all .2s; }
        .method-card.selected { border-color: #f29923; background: rgba(242,153,35,0.06); box-shadow: 0 0 0 3px rgba(242,153,35,0.2); }
        .method-card:hover { border-color: #f29923; }
        .badge-status { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .alert-info { background: #fff3e0 !important; color: #7c3d00 !important; border: 1.5px solid #f29923 !important; }
        .alert-success { background: #d1fae5 !important; color: #065f46 !important; border: 1.5px solid #22c55e !important; }
        .alert-error { background: #fee2e2 !important; color: #991b1b !important; border: 1.5px solid #ef4444 !important; }
    </style>
</head>
<body class="py-8 px-4">

<div class="max-w-lg mx-auto">

    <!-- Brand Header -->
    <div class="text-center mb-6">
        <div class="inline-block rounded-2xl px-5 py-4 mb-2"
            style="background: #fff; box-shadow: 0 4px 20px rgba(0,0,0,0.25);">
            <img src="{{ asset('images/logo/CleanlLogo.png') }}" alt="Vanilla Royal Logo"
                style="height:80px; width:auto; display:block; margin:0 auto;">
        </div>
        <p class="text-xs font-semibold tracking-[0.2em] mt-2" style="color:#ffdd79;">INVOICE PEMBAYARAN</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4 text-sm"><span>{{ session('success') }}</span></div>
    @endif
    @if(session('error'))
        <div class="alert alert-error mb-4 text-sm"><span>{{ session('error') }}</span></div>
    @endif
    @if(session('info'))
        <div class="alert alert-info mb-4 text-sm"><span>{{ session('info') }}</span></div>
    @endif

    <!-- Invoice Card -->
    <div class="card-vr p-5 mb-4">
        <!-- Invoice Header -->
        <div class="flex justify-between items-start mb-4 pb-3" style="border-bottom: 2px solid #f29923;">
            <div>
                <div class="font-mono font-bold text-sm" style="color:#41281b;">{{ $invoice->invoice_number }}</div>
                <div class="text-xs mt-0.5" style="color:#999;">{{ $invoice->created_at->format('d M Y') }}</div>
            </div>
            <div>
                @php
                    $statusColor = match($invoice->status) {
                        'paid' => 'background:#d1fae5; color:#065f46;',
                        'pending_confirmation' => 'background:#fef3c7; color:#92400e;',
                        'sent' => 'background:#fee2e2; color:#991b1b;',
                        default => 'background:#f3f4f6; color:#6b7280;',
                    };
                    $statusLabel = match($invoice->status) {
                        'paid' => 'Lunas',
                        'pending_confirmation' => 'Menunggu Konfirmasi',
                        'sent' => 'Belum Lunas',
                        default => $invoice->status_label,
                    };
                @endphp
                <span class="badge-status" style="{{ $statusColor }}">{{ $statusLabel }}</span>
            </div>
        </div>

        <!-- Customer Info -->
        <div class="rounded-xl p-3 mb-4 text-sm" style="background:#fef9f0; border-left: 3px solid #f29923;">
            <div class="font-semibold" style="color:#2c1810;">{{ $invoice->customer_name }}</div>
            @if($invoice->customer_phone)
                <div style="color:#666;">{{ $invoice->customer_phone }}</div>
            @endif
            @if($invoice->customer_address)
                <div class="text-xs mt-1" style="color:#888;">{{ $invoice->customer_address }}</div>
            @endif
        </div>

        <!-- Items -->
        <table class="w-full text-sm mb-4">
            <thead>
                <tr style="background:#41281b; color:#ffdd79;">
                    <th class="text-left py-2 px-3 rounded-tl-lg font-medium text-xs">Produk</th>
                    <th class="text-center py-2 px-2 font-medium text-xs">Qty</th>
                    <th class="text-right py-2 px-3 rounded-tr-lg font-medium text-xs">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr style="border-bottom: 1px solid #f3ede4;">
                    <td class="py-2.5 px-3">
                        <div class="font-medium" style="color:#2c1810;">{{ $item->product_name }}</div>
                        @if($item->description)
                            <div class="text-xs" style="color:#999;">{{ $item->description }}</div>
                        @endif
                        <div class="text-xs" style="color:#b08040;">Rp {{ number_format($item->unit_price, 0, ',', '.') }} / pcs</div>
                    </td>
                    <td class="text-center py-2.5 px-2" style="color:#41281b;">{{ $item->quantity }}</td>
                    <td class="text-right py-2.5 px-3 font-semibold" style="color:#2c1810;">Rp {{ number_format($item->total_price, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="space-y-1.5 text-sm">
            <div class="flex justify-between" style="color:#666;">
                <span>Subtotal</span>
                <span>Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span>
            </div>
            @foreach($invoice->extras as $extra)
            <div class="flex justify-between" style="color:#888;">
                <span>{{ $extra->label }}</span>
                <span>+ Rp {{ number_format($extra->amount, 0, ',', '.') }}</span>
            </div>
            @endforeach
            @if($invoice->discount > 0)
            <div class="flex justify-between" style="color:#059669;">
                <span>Diskon</span>
                <span>- Rp {{ number_format($invoice->discount, 0, ',', '.') }}</span>
            </div>
            @endif
            <div class="flex justify-between items-center pt-2 mt-1" style="border-top: 2px solid #f29923;">
                <span class="font-bold text-base" style="color:#2c1810;">TOTAL</span>
                <span class="font-bold text-xl" style="color:#f29923;">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
            </div>
        </div>

        @if($invoice->due_date)
        <div class="text-center text-xs mt-3 {{ $invoice->due_date->isPast() ? '' : '' }}"
            style="color: {{ $invoice->due_date->isPast() ? '#dc2626' : '#888' }}">
            Jatuh Tempo: {{ $invoice->due_date->format('d M Y') }}
        </div>
        @endif

        @if($invoice->notes)
        <div class="mt-3 p-3 rounded-lg text-xs" style="background:#fef9f0; border-left:3px solid #d1a96a; color:#666;">
            <strong style="color:#41281b;">Catatan:</strong> {{ $invoice->notes }}
        </div>
        @endif
    </div>

    <!-- Download Invoice (if paid) -->
    @if($invoice->status === 'paid')
    <div class="card-vr p-4 mb-4 text-center" style="border:1.5px solid #86efac; background:#f0fdf4;">
        <div class="font-semibold mb-1" style="color:#14532d;">Pembayaran Dikonfirmasi</div>
        @if($invoice->paid_at)<div class="text-xs mb-3" style="color:#6b7280;">{{ $invoice->paid_at->format('d M Y, H:i') }}</div>@endif
        <a href="{{ route('payment.invoice-pdf', $invoice->payment_token) }}"
           class="btn-primary-vr" style="display:inline-block; text-decoration:none;">
            Download Invoice PDF
        </a>
    </div>
    @endif

    <!-- Payment Section -->
    @if($invoice->status === 'pending_confirmation')
        <div class="card-vr p-5 text-center mb-4">
            <div class="text-4xl mb-3">⏳</div>
            <div class="font-bold text-base mb-1" style="color:#2c1810;">Menunggu Konfirmasi</div>
            <p class="text-sm" style="color:#666;">Bukti transfer Anda sedang diperiksa admin. Kami akan segera mengkonfirmasi.</p>
            @if($invoice->latestProof?->status === 'rejected')
                <div class="alert alert-error mt-3 text-left text-sm">
                    <div>
                        <div class="font-semibold">Bukti Transfer Ditolak</div>
                        @if($invoice->latestProof->admin_notes)
                            <div>Alasan: {{ $invoice->latestProof->admin_notes }}</div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

    @elseif(!$invoice->payment_method)
        <!-- Choose Payment Method -->
        <div class="card-vr p-5 mb-4" x-data="{ method: '' }">
            <h3 class="font-bold text-base mb-1" style="color:#2c1810;">Pilih Metode Pembayaran</h3>
            <p class="text-xs mb-4" style="color:#888;">Pilih cara yang paling nyaman untuk Anda</p>

            <div class="mb-5">
                <label class="cursor-pointer">
                    <input type="radio" name="method_select" value="transfer" x-model="method" class="hidden">
                    <div :class="method === 'transfer' ? 'method-card selected' : 'method-card'" @click="method = 'transfer'">
                        <div class="text-3xl mb-2">🏦</div>
                        <div class="font-semibold text-sm" style="color:#2c1810;">Transfer Bank</div>
                        <div class="text-xs mt-0.5" style="color:#888;">Upload bukti transfer</div>
                    </div>
                </label>
            </div>

            <form method="POST" action="{{ route('payment.choose-method', $invoice->payment_token) }}" x-show="method !== ''">
                @csrf
                <input type="hidden" name="payment_method" :value="method">
                <button type="submit" class="btn-primary-vr">Konfirmasi Metode Pembayaran</button>
            </form>
        </div>

    @elseif($invoice->payment_method === 'transfer' && in_array($invoice->status, ['sent', 'pending_confirmation']))
        <!-- Transfer Instructions & Upload -->
        <div class="card-vr p-5 mb-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold text-base" style="color:#2c1810;">Detail Transfer Bank</h3>
                <form method="POST" action="{{ route('payment.reset-method', $invoice->payment_token) }}">
                    @csrf
                    <button type="submit" class="text-xs font-medium px-3 py-1.5 rounded-lg border transition-all"
                        style="color:#f29923; border-color:#f29923; background:transparent;"
                        onmouseover="this.style.background='rgba(242,153,35,0.08)'"
                        onmouseout="this.style.background='transparent'">
                        ← Ganti Metode
                    </button>
                </form>
            </div>
            <div class="rounded-xl p-4 mb-4" style="background:#fef9f0; border: 1.5px solid #d1a96a;">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span style="color:#888;">Atas Nama</span>
                        <span class="font-semibold" style="color:#2c1810;">PT Coffee Nation Prosperity</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color:#888;">Bank</span>
                        <span class="font-semibold" style="color:#2c1810;">BRI</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color:#888;">No. Rekening</span>
                        <span class="font-mono font-bold" style="color:#2c1810;">115601002957562</span>
                    </div>
                    <div class="flex justify-between items-center pt-2" style="border-top: 1.5px dashed #d1a96a; margin-top: 4px;">
                        <span class="font-bold" style="color:#2c1810;">Jumlah Transfer</span>
                        <span class="font-bold text-lg" style="color:#f29923;">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            @if($invoice->latestProof?->status === 'rejected')
            <div class="alert alert-error mb-4 text-sm">
                <div>
                    <div class="font-semibold">Bukti sebelumnya ditolak.</div>
                    @if($invoice->latestProof->admin_notes)
                        <div>Alasan: {{ $invoice->latestProof->admin_notes }}</div>
                    @endif
                    <div class="mt-1">Silakan upload ulang bukti yang benar.</div>
                </div>
            </div>
            @endif

            <h3 class="font-bold text-sm mb-3" style="color:#2c1810;">Upload Bukti Transfer</h3>
            <form method="POST" action="{{ route('payment.upload-proof', $invoice->payment_token) }}" enctype="multipart/form-data"
                onsubmit="return validateProofFile(this)">
                @csrf
                @error('proof')
                    <div class="alert alert-error mb-3 text-sm"><span>{{ $message }}</span></div>
                @enderror
                <div id="proof-error" class="mb-3 text-sm hidden" style="background:#fee2e2; color:#991b1b; border:1.5px solid #ef4444; padding:0.75rem 1rem; border-radius:8px;"></div>
                <div class="mb-4">
                    <label class="block text-xs font-medium mb-2" style="color:#666;">File Bukti Transfer (JPG/PNG/PDF, maks 5MB)</label>
                    <input type="file" name="proof" id="proof-input" class="file-input file-input-bordered w-full text-sm" accept=".jpg,.jpeg,.png,.pdf" required
                        onchange="clearProofError()">
                </div>
                <button type="submit" class="btn-primary-vr">Kirim Bukti Transfer</button>
            </form>
        </div>

    @endif

    <!-- Footer -->
    <div class="mt-6 pb-6">
        <div class="flex items-center gap-3 mb-3">
            <div class="flex-1 h-px" style="background: rgba(242,153,35,0.2);"></div>
            <img src="{{ asset('images/logo/logo.png') }}" alt="Vanilla Royal"
                class="h-6 w-auto object-contain"
                style="opacity: 0.4;">
            <div class="flex-1 h-px" style="background: rgba(242,153,35,0.2);"></div>
        </div>
        <p class="text-center text-xs font-medium" style="color: rgba(255,221,121,0.55);">© {{ date('Y') }} Vanilla Royal &bull; All rights reserved</p>
        <p class="text-center text-xs mt-0.5" style="color: rgba(255,255,255,0.2);">Payment System</p>
    </div>
</div>

<script>
function validateProofFile(form) {
    const input = document.getElementById('proof-input');
    const errorBox = document.getElementById('proof-error');
    if (!input || !input.files[0]) return true;

    const maxSize = 5 * 1024 * 1024; // 5MB
    const file = input.files[0];
    const allowed = ['image/jpeg', 'image/png', 'application/pdf'];

    if (!allowed.includes(file.type)) {
        showProofError('Format file tidak didukung. Gunakan JPG, PNG, atau PDF.');
        return false;
    }
    if (file.size > maxSize) {
        showProofError('File terlalu besar (' + formatBytes(file.size) + '). Maksimal ukuran file adalah 5MB.');
        return false;
    }
    return true;
}
function showProofError(msg) {
    const box = document.getElementById('proof-error');
    box.textContent = msg;
    box.classList.remove('hidden');
    box.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function clearProofError() {
    const box = document.getElementById('proof-error');
    if (box) box.classList.add('hidden');
}
function formatBytes(bytes) {
    return (bytes / (1024 * 1024)).toFixed(1) + 'MB';
}
</script>
</body>
</html>
