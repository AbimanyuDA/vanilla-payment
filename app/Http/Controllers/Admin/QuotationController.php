<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuotationController extends Controller
{
    private const INCOTERMS = ['EXW', 'FCA', 'FOB', 'CFR', 'CIF', 'CPT', 'CIP', 'DAP', 'DPU', 'DDP'];

    public function index(Request $request)
    {
        $query = Quotation::with('buyer', 'creator')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->whereLike('quotation_number', '%' . $request->search . '%')
                  ->orWhereLike('buyer_company_name', '%' . $request->search . '%');
            });
        }

        $quotations = $query->paginate(15)->withQueryString();
        return view('admin.quotations.index', compact('quotations'));
    }

    public function create()
    {
        $countries = config('countries');
        $currencies = config('currencies');
        $incoterms = self::INCOTERMS;
        $buyers = Buyer::orderBy('company_name')->get();

        return view('admin.quotations.create', compact('countries', 'currencies', 'incoterms', 'buyers'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $quotation = DB::transaction(function () use ($data) {
            return $this->persist(new Quotation(), $data);
        });

        return redirect()->route('admin.quotations.show', $quotation)->with('success', 'Quotation berhasil dibuat.');
    }

    public function show(Quotation $quotation)
    {
        $quotation->load('items', 'buyer', 'creator');
        return view('admin.quotations.show', compact('quotation'));
    }

    public function edit(Quotation $quotation)
    {
        if (in_array($quotation->status, ['accepted', 'converted_to_proforma', 'cancelled'])) {
            return back()->with('error', 'Quotation dengan status ini tidak bisa diedit.');
        }

        $quotation->load('items');
        $countries = config('countries');
        $currencies = config('currencies');
        $incoterms = self::INCOTERMS;
        $buyers = Buyer::orderBy('company_name')->get();

        return view('admin.quotations.edit', compact('quotation', 'countries', 'currencies', 'incoterms', 'buyers'));
    }

    public function update(Request $request, Quotation $quotation)
    {
        if (in_array($quotation->status, ['accepted', 'converted_to_proforma', 'cancelled'])) {
            return back()->with('error', 'Quotation dengan status ini tidak bisa diedit.');
        }

        $data = $this->validated($request);

        DB::transaction(function () use ($quotation, $data) {
            $this->persist($quotation, $data);
        });

        return redirect()->route('admin.quotations.show', $quotation)->with('success', 'Quotation berhasil diperbarui.');
    }

    public function destroy(Quotation $quotation)
    {
        if (in_array($quotation->status, ['accepted', 'converted_to_proforma'])) {
            return back()->with('error', 'Quotation yang sudah diterima/dikonversi tidak bisa dihapus.');
        }
        $quotation->delete();
        return redirect()->route('admin.quotations.index')->with('success', 'Quotation berhasil dihapus.');
    }

    public function updateStatus(Request $request, Quotation $quotation)
    {
        $request->validate([
            'status' => 'required|in:draft,sent,accepted,rejected,expired,cancelled',
        ]);

        $quotation->update(['status' => $request->status]);

        return back()->with('success', 'Status quotation diubah menjadi ' . $quotation->status_label . '.');
    }

    public function pdf(Quotation $quotation)
    {
        $quotation->refreshPdfSnapshot();

        return $this->renderPdf($quotation)->download($quotation->quotation_number . '.pdf');
    }

    /**
     * Streams the very same PDF inline instead of downloading it, so what the admin
     * previews in the browser is the document itself rather than an HTML lookalike.
     * Deliberately does not re-freeze the snapshot — previewing is not issuing.
     */
    public function pdfPreview(Quotation $quotation)
    {
        return $this->renderPdf($quotation)->stream($quotation->quotation_number . '.pdf');
    }

    private function renderPdf(Quotation $quotation): \Barryvdh\DomPDF\PDF
    {
        return Pdf::setOptions(['isPhpEnabled' => true])
            ->loadView('admin.quotations.pdf', [
                'quotation' => $quotation->fresh(['items', 'buyer', 'creator']),
            ])
            ->setPaper('a4');
    }

    /**
     * Shared create/update persistence: recalculates totals server-side (never trusts
     * client-computed totals), snapshots buyer info, and replaces line items atomically.
     */
    private function persist(Quotation $quotation, array $data): Quotation
    {
        $items = $data['items'];
        unset($data['items']);

        $subtotal = collect($items)->sum(fn ($item) => $item['quantity'] * $item['unit_price']);
        $data['subtotal'] = $subtotal;
        $data['grand_total'] = $subtotal
            - ($data['discount_amount'] ?? 0)
            + ($data['freight_amount'] ?? 0)
            + ($data['insurance_amount'] ?? 0)
            + ($data['other_charges_amount'] ?? 0)
            + ($data['tax_amount'] ?? 0);

        if (!$quotation->exists) {
            $data['created_by'] = Auth::guard('admin')->id();
            $data['status'] = 'draft';
        }

        $quotation->fill($data)->save();

        $quotation->items()->delete();
        foreach ($items as $index => $item) {
            $quotation->items()->create([
                'product_id' => $item['product_id'] ?? null,
                'product_name' => $item['product_name'],
                'species' => $item['species'] ?? null,
                'grade' => $item['grade'] ?? null,
                'size' => $item['size'] ?? null,
                'weight' => $item['weight'] ?? null,
                'moisture' => $item['moisture'] ?? null,
                'packaging' => $item['packaging'] ?? null,
                'condition' => $item['condition'] ?? null,
                'aroma' => $item['aroma'] ?? null,
                'description' => $item['description'] ?? null,
                'hs_code' => $item['hs_code'] ?? null,
                'country_of_origin' => $item['country_of_origin'] ?? null,
                'quantity' => $item['quantity'],
                'unit' => $item['unit'] ?? 'KG',
                'unit_price' => $item['unit_price'],
                'line_total' => $item['quantity'] * $item['unit_price'],
                'sort_order' => $index,
            ]);
        }

        return $quotation;
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'buyer_id' => 'nullable|exists:buyers,id',
            'buyer_company_name' => 'required|string|max:255',
            'buyer_contact_person' => 'nullable|string|max:255',
            'buyer_email' => 'nullable|email|max:255',
            'buyer_phone' => 'nullable|string|max:50',
            'buyer_address' => 'nullable|string',
            'buyer_city' => 'nullable|string|max:255',
            'buyer_state_province' => 'nullable|string|max:255',
            'buyer_postal_code' => 'nullable|string|max:50',
            'buyer_country_code' => 'nullable|string|size:3',
            'buyer_tax_vat_number' => 'nullable|string|max:100',

            'quotation_date' => 'required|date',
            'validity_days' => 'required|integer|min:1|max:365',

            'currency' => 'required|string|size:3',
            'incoterm' => 'required|in:' . implode(',', self::INCOTERMS),
            'incoterm_place' => 'nullable|string|max:255',
            'destination_country_code' => 'nullable|string|size:3',

            'port_of_loading' => 'nullable|string|max:255',
            'port_of_discharge' => 'nullable|string|max:255',
            'final_destination' => 'nullable|string|max:255',
            'shipping_method' => 'nullable|string|max:100',
            'production_lead_time' => 'nullable|string|max:255',
            'estimated_shipment' => 'nullable|string|max:255',

            'payment_terms' => 'nullable|string',
            'moq' => 'nullable|string|max:100',
            'country_of_origin' => 'nullable|string|max:100',

            'discount_amount' => 'nullable|numeric|min:0',
            'freight_amount' => 'nullable|numeric|min:0',
            'insurance_amount' => 'nullable|numeric|min:0',
            'other_charges_label' => 'nullable|string|max:100',
            'other_charges_amount' => 'nullable|numeric|min:0',
            'tax_label' => 'nullable|string|max:100',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_amount' => 'nullable|numeric|min:0',

            'notes' => 'nullable|string',

            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.species' => 'nullable|string|max:255',
            'items.*.grade' => 'nullable|string|max:255',
            'items.*.size' => 'nullable|string|max:100',
            'items.*.weight' => 'nullable|string|max:100',
            'items.*.moisture' => 'nullable|string|max:100',
            'items.*.packaging' => 'nullable|string|max:255',
            'items.*.condition' => 'nullable|string|max:255',
            'items.*.aroma' => 'nullable|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.hs_code' => 'nullable|string|max:20',
            'items.*.country_of_origin' => 'nullable|string|max:100',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:20',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        if (!empty($data['buyer_id'])) {
            $buyer = Buyer::find($data['buyer_id']);
            if ($buyer) {
                $data['buyer_country_code'] = $buyer->country_code;
            }
        }

        $data['buyer_country'] = !empty($data['buyer_country_code'])
            ? (config('countries')[$data['buyer_country_code']] ?? null)
            : null;

        $data['destination_country'] = !empty($data['destination_country_code'])
            ? (config('countries')[$data['destination_country_code']] ?? null)
            : null;

        return $data;
    }
}
