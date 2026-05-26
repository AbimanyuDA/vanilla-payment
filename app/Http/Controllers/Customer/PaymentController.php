<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function show(string $token)
    {
        $invoice = Invoice::where('payment_token', $token)
            ->with('items', 'extras', 'latestProof')
            ->firstOrFail();

        if ($invoice->status === 'cancelled') {
            return view('customer.payment.cancelled', compact('invoice'));
        }
        if ($invoice->status === 'paid') {
            return view('customer.payment.paid', compact('invoice'));
        }
        if ($invoice->isExpired()) {
            $invoice->update(['status' => 'expired']);
            return view('customer.payment.expired', compact('invoice'));
        }
        if (in_array($invoice->status, ['draft'])) {
            abort(404);
        }

        return view('customer.payment.show', compact('invoice'));
    }

    public function chooseMethod(Request $request, string $token)
    {
        $invoice = Invoice::where('payment_token', $token)->firstOrFail();

        if (!in_array($invoice->status, ['sent', 'pending_confirmation'])) {
            return back()->with('error', 'Invoice ini tidak bisa diproses.');
        }

        $request->validate([
            'payment_method' => 'required|in:transfer',
        ]);

        $invoice->update([
            'payment_method' => $request->payment_method,
        ]);

        return redirect()->route('payment.show', $token)->with('info', 'Silakan upload bukti transfer Anda.');
    }

    public function resetMethod(string $token)
    {
        $invoice = Invoice::where('payment_token', $token)->firstOrFail();

        // Hanya bisa ganti kalau belum upload bukti yang pending/confirmed
        if ($invoice->status === 'pending_confirmation' && $invoice->payment_method === 'transfer') {
            $latestProof = $invoice->latestProof;
            if ($latestProof && $latestProof->status === 'pending') {
                return back()->with('error', 'Bukti transfer sedang diverifikasi, tidak bisa ganti metode.');
            }
        }

        if (!in_array($invoice->status, ['sent', 'pending_confirmation'])) {
            return back()->with('error', 'Tidak bisa mengubah metode pembayaran.');
        }

        $invoice->update([
            'payment_method' => null,
            'status' => 'sent',
        ]);

        return redirect()->route('payment.show', $token)->with('info', 'Silakan pilih metode pembayaran.');
    }

    public function downloadInvoice(string $token)
    {
        $invoice = Invoice::where('payment_token', $token)
            ->with('items', 'extras')
            ->firstOrFail();

        if ($invoice->status !== 'paid') {
            abort(403, 'Invoice belum lunas.');
        }

        $pdf = Pdf::loadView('admin.invoices.pdf', compact('invoice'))->setPaper('a4');
        return $pdf->download('invoice-lunas-' . $invoice->invoice_number . '.pdf');
    }

    public function uploadProof(Request $request, string $token)
    {
        $invoice = Invoice::where('payment_token', $token)->firstOrFail();

        if (!in_array($invoice->status, ['sent', 'pending_confirmation'])) {
            return back()->with('error', 'Invoice ini tidak bisa diproses.');
        }
        if (!in_array($invoice->payment_method, ['transfer', 'qris'])) {
            return back()->with('error', 'Metode pembayaran tidak valid.');
        }

        $request->validate([
            'proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $file = $request->file('proof');
        $filename = 'proof_' . $invoice->invoice_number . '_' . time() . '.' . $file->getClientOriginalExtension();

        $invoice->paymentProofs()->create([
            'file_path' => 'payment_proofs/' . $filename,
            'file_name' => $file->getClientOriginalName(),
            'file_content' => base64_encode(file_get_contents($file->getRealPath())),
            'file_mime' => $file->getMimeType(),
            'status' => 'pending',
        ]);

        $invoice->update(['status' => 'pending_confirmation']);

        return redirect()->route('payment.show', $token)->with('success', 'Bukti transfer berhasil dikirim. Menunggu konfirmasi admin.');
    }
}
