<x-mail::message>
# Invoice {{ $invoice->invoice_number }}

Halo **{{ $invoice->customer_name }}**,

Berikut adalah invoice pembayaran dari Vanilla Royal:

| Detail | Info |
|--------|------|
| No. Invoice | {{ $invoice->invoice_number }} |
| Total | Rp {{ number_format($invoice->total_amount, 0, ',', '.') }} |
@if($invoice->due_date)
| Jatuh Tempo | {{ $invoice->due_date->format('d M Y') }} |
@endif

<x-mail::button :url="route('payment.show', $invoice->payment_token)" color="primary">
Bayar Sekarang
</x-mail::button>

Klik tombol di atas untuk memilih metode pembayaran (Transfer Bank atau QRIS).

Terima kasih telah berbelanja di Vanilla Royal!

Salam,<br>
Tim Vanilla Royal
</x-mail::message>
