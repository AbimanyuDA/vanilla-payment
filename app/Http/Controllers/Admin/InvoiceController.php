<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with('creator')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('invoice_number', 'like', '%' . $request->search . '%')
                  ->orWhere('customer_name', 'like', '%' . $request->search . '%')
                  ->orWhere('customer_phone', 'like', '%' . $request->search . '%');
            });
        }

        $invoices = $query->paginate(15)->withQueryString();
        return view('admin.invoices.index', compact('invoices'));
    }

    public function create()
    {
        return view('admin.invoices.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_email' => 'nullable|email|max:255',
            'customer_address' => 'nullable|string',
            'due_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'extras' => 'nullable|array',
            'extras.*.label' => 'required_with:extras.*.amount|string|max:100',
            'extras.*.amount' => 'required_with:extras.*.label|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $subtotal = collect($request->items)->sum(fn($item) => $item['quantity'] * $item['unit_price']);
            $discount = $request->discount ?? 0;
            $extrasTotal = collect($request->extras ?? [])->sum(fn($e) => $e['amount'] ?? 0);
            $total = $subtotal - $discount + $extrasTotal;

            $invoice = Invoice::create([
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_email' => $request->customer_email,
                'customer_address' => $request->customer_address,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total_amount' => $total,
                'due_date' => $request->due_date,
                'notes' => $request->notes,
                'status' => 'sent',
                'created_by' => Auth::guard('admin')->id(),
            ]);

            foreach ($request->items as $item) {
                $invoice->items()->create([
                    'product_name' => $item['product_name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            foreach ($request->extras ?? [] as $extra) {
                if (!empty($extra['label']) && isset($extra['amount'])) {
                    $invoice->extras()->create(['label' => $extra['label'], 'amount' => $extra['amount']]);
                }
            }
        });

        return redirect()->route('admin.invoices.index')->with('success', 'Invoice berhasil dibuat.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('items', 'extras', 'paymentProofs.confirmedBy', 'creator');
        return view('admin.invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            return back()->with('error', 'Invoice yang sudah lunas atau dibatalkan tidak bisa diedit.');
        }
        $invoice->load('items', 'extras');
        return view('admin.invoices.edit', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            return back()->with('error', 'Invoice yang sudah lunas atau dibatalkan tidak bisa diedit.');
        }

        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_email' => 'nullable|email|max:255',
            'customer_address' => 'nullable|string',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'extras' => 'nullable|array',
            'extras.*.label' => 'required_with:extras.*.amount|string|max:100',
            'extras.*.amount' => 'required_with:extras.*.label|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $invoice) {
            $subtotal = collect($request->items)->sum(fn($item) => $item['quantity'] * $item['unit_price']);
            $discount = $request->discount ?? 0;
            $extrasTotal = collect($request->extras ?? [])->sum(fn($e) => $e['amount'] ?? 0);

            $invoice->update([
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_email' => $request->customer_email,
                'customer_address' => $request->customer_address,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total_amount' => $subtotal - $discount + $extrasTotal,
                'due_date' => $request->due_date,
                'notes' => $request->notes,
            ]);

            $invoice->items()->delete();
            foreach ($request->items as $item) {
                $invoice->items()->create([
                    'product_name' => $item['product_name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            $invoice->extras()->delete();
            foreach ($request->extras ?? [] as $extra) {
                if (!empty($extra['label']) && isset($extra['amount'])) {
                    $invoice->extras()->create(['label' => $extra['label'], 'amount' => $extra['amount']]);
                }
            }
        });

        return redirect()->route('admin.invoices.show', $invoice)->with('success', 'Invoice berhasil diperbarui.');
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', 'Invoice yang sudah lunas tidak bisa dihapus.');
        }
        $invoice->delete();
        return redirect()->route('admin.invoices.index')->with('success', 'Invoice berhasil dihapus.');
    }

    public function markSent(Invoice $invoice)
    {
        $invoice->update(['status' => 'sent']);
        return back()->with('success', 'Status invoice diubah menjadi Terkirim.');
    }

    public function confirmPayment(Request $request, Invoice $invoice)
    {
        $request->validate([
            'action' => 'required|in:confirm,reject',
            'admin_notes' => 'nullable|string',
        ]);

        $proof = $invoice->latestProof;

        if ($request->action === 'confirm') {
            $proof?->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'confirmed_by' => Auth::guard('admin')->id(),
                'admin_notes' => $request->admin_notes,
            ]);
            $invoice->update(['status' => 'paid', 'paid_at' => now()]);
            return back()->with('success', 'Pembayaran dikonfirmasi. Invoice telah lunas.');
        }

        $proof?->update([
            'status' => 'rejected',
            'admin_notes' => $request->admin_notes,
        ]);
        $invoice->update(['status' => 'sent']);
        return back()->with('success', 'Bukti pembayaran ditolak. Customer perlu upload ulang.');
    }

    public function cancel(Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', 'Invoice yang sudah lunas tidak bisa dibatalkan.');
        }
        $invoice->update(['status' => 'cancelled']);
        return back()->with('success', 'Invoice berhasil dibatalkan.');
    }

    public function sendNotification(Request $request, Invoice $invoice)
    {
        $request->validate([
            'channel' => 'required|in:whatsapp,email',
        ]);

        $paymentUrl = route('payment.show', $invoice->payment_token);

        if ($request->channel === 'whatsapp') {
            $phone = preg_replace('/\D/', '', $invoice->customer_phone);
            if (str_starts_with($phone, '0')) {
                $phone = '62' . substr($phone, 1);
            }
            $message = urlencode(
                "Halo *{$invoice->customer_name}*,\n\n" .
                "Berikut invoice pembayaran Anda dari *Vanilla Royal*:\n\n" .
                "No. Invoice: *{$invoice->invoice_number}*\n" .
                "Total: *Rp " . number_format($invoice->total_amount, 0, ',', '.') . "*\n" .
                ($invoice->due_date ? "Jatuh Tempo: *{$invoice->due_date->format('d/m/Y')}*\n\n" : "\n") .
                "Klik link berikut untuk memilih metode pembayaran:\n{$paymentUrl}\n\n" .
                "Terima kasih 🙏"
            );
            $waUrl = "https://wa.me/{$phone}?text={$message}";
            $invoice->update(['status' => 'sent']);
            return redirect($waUrl);
        }

        // Email channel (requires mail config)
        if ($invoice->customer_email) {
            Mail::to($invoice->customer_email)->send(new \App\Mail\InvoiceMail($invoice));
            $invoice->update(['status' => 'sent']);
            return back()->with('success', 'Email invoice berhasil dikirim.');
        }

        return back()->with('error', 'Email customer tidak tersedia.');
    }
}
