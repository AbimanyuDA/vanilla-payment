<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::withCount('productAttributes')->orderBy('sort_order')->orderBy('name');

        if ($request->filled('search')) {
            $query->whereLike('name', '%' . $request->search . '%');
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $products = $query->paginate(20)->withQueryString();
        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        return view('admin.products.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        DB::transaction(function () use ($validated) {
            $product = Product::create($validated['product']);
            $this->syncAttributes($product, $validated['attributes']);
        });

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product)
    {
        $product->load('productAttributes');
        return view('admin.products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $this->validated($request);

        DB::transaction(function () use ($product, $validated) {
            $product->update($validated['product']);
            $this->syncAttributes($product, $validated['attributes']);
        });

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        if ($product->quotationItems()->exists()) {
            return back()->with('error', 'Produk tidak bisa dihapus karena sudah dipakai di quotation. Nonaktifkan saja produk ini.');
        }
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil dihapus.');
    }

    /**
     * Lightweight JSON search used by the searchable product dropdown on the quotation form.
     * Returns each product with its attributes flattened into a key => value map so the
     * frontend can auto-fill grade/size/weight/etc. while keeping every field editable.
     */
    public function search(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $products = Product::query()
            ->where('is_active', true)
            ->when($q !== '', fn ($query) => $query->whereLike('name', "%{$q}%"))
            ->with('productAttributes')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'species' => $product->species,
                'category' => $product->category,
                'hs_code' => $product->hs_code,
                'default_unit' => $product->default_unit,
                'default_country_of_origin' => $product->default_country_of_origin,
                'attributes' => $product->attribute_map,
            ]);

        return response()->json($products);
    }

    private function syncAttributes(Product $product, array $attributes): void
    {
        $product->productAttributes()->delete();

        $order = 0;
        foreach ($attributes as $attribute) {
            if (empty($attribute['key']) || !isset($attribute['value']) || $attribute['value'] === '') {
                continue;
            }
            $product->productAttributes()->create([
                'key' => $attribute['key'],
                'value' => $attribute['value'],
                'sort_order' => $order++,
            ]);
        }
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'species' => 'nullable|string|max:255',
            'category' => 'required|in:raw,value_added',
            'hs_code' => 'nullable|string|max:20',
            'default_unit' => 'required|string|max:20',
            'default_country_of_origin' => 'required|string|max:100',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'attributes' => 'nullable|array',
            'attributes.*.key' => 'nullable|string|max:100',
            'attributes.*.value' => 'nullable|string|max:255',
        ]);

        return [
            'product' => [
                'name' => $data['name'],
                'species' => $data['species'] ?? null,
                'category' => $data['category'],
                'hs_code' => $data['hs_code'] ?? null,
                'default_unit' => $data['default_unit'],
                'default_country_of_origin' => $data['default_country_of_origin'],
                'is_active' => $request->boolean('is_active', true),
                'sort_order' => $data['sort_order'] ?? 0,
            ],
            'attributes' => $data['attributes'] ?? [],
        ];
    }
}
