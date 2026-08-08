<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BuyerController extends Controller
{
    public function index(Request $request)
    {
        $query = Buyer::withCount('quotations')->latest();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->whereLike('company_name', '%' . $request->search . '%')
                  ->orWhereLike('contact_person', '%' . $request->search . '%')
                  ->orWhereLike('country', '%' . $request->search . '%');
            });
        }

        $buyers = $query->paginate(15)->withQueryString();
        return view('admin.buyers.index', compact('buyers'));
    }

    public function create()
    {
        $countries = config('countries');
        return view('admin.buyers.create', compact('countries'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['created_by'] = Auth::guard('admin')->id();
        Buyer::create($data);

        return redirect()->route('admin.buyers.index')->with('success', 'Buyer berhasil ditambahkan.');
    }

    public function edit(Buyer $buyer)
    {
        $countries = config('countries');
        return view('admin.buyers.edit', compact('buyer', 'countries'));
    }

    public function update(Request $request, Buyer $buyer)
    {
        $buyer->update($this->validated($request));
        return redirect()->route('admin.buyers.index')->with('success', 'Buyer berhasil diperbarui.');
    }

    public function destroy(Buyer $buyer)
    {
        if ($buyer->quotations()->exists()) {
            return back()->with('error', 'Buyer tidak bisa dihapus karena memiliki riwayat quotation.');
        }
        $buyer->delete();
        return redirect()->route('admin.buyers.index')->with('success', 'Buyer berhasil dihapus.');
    }

    /**
     * Lightweight JSON search used by the searchable buyer dropdown on the quotation form.
     */
    public function search(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $buyers = Buyer::query()
            ->when($q !== '', fn ($query) => $query->whereLike('company_name', "%{$q}%"))
            ->orderBy('company_name')
            ->limit(20)
            ->get([
                'id', 'company_name', 'contact_person', 'email', 'phone',
                'address', 'city', 'state_province', 'postal_code',
                'country', 'country_code', 'tax_vat_number',
            ]);

        return response()->json($buyers);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state_province' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:50',
            'country_code' => 'nullable|string|size:3',
            'tax_vat_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $data['country'] = $data['country_code'] ?? null
            ? (config('countries')[$data['country_code']] ?? null)
            : null;

        return $data;
    }
}
