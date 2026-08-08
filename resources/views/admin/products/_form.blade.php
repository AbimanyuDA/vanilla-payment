{{-- Expects: $formAction, $method, optional $product (with productAttributes loaded) --}}
@php
    $initAttributes = old('attributes') ?: (
        isset($product) ? $product->productAttributes->map(fn ($a) => ['key' => $a->key, 'value' => $a->value])->values()->all() : []
    );
@endphp
<div x-data="productForm(@js($initAttributes))">
    <form method="POST" action="{{ $formAction }}">
        @csrf
        @if($method === 'PUT')
            @method('PUT')
        @endif

        <div class="card bg-base-100 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control sm:col-span-2">
                        <label class="label"><span class="label-text">Product Name <span class="text-error">*</span></span></label>
                        <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}"
                            class="input input-bordered @error('name') input-error @enderror" required>
                        @error('name')<span class="text-error text-xs mt-1">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Species (Latin name)</span></label>
                        <input type="text" name="species" value="{{ old('species', $product->species ?? '') }}"
                            placeholder="e.g. Vanilla planifolia Andrews" class="input input-bordered">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Category <span class="text-error">*</span></span></label>
                        <select name="category" class="select select-bordered" required>
                            <option value="raw" @selected(old('category', $product->category ?? 'raw') === 'raw')>Raw</option>
                            <option value="value_added" @selected(old('category', $product->category ?? '') === 'value_added')>Value-Added</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">HS Code (optional)</span></label>
                        <input type="text" name="hs_code" value="{{ old('hs_code', $product->hs_code ?? '') }}" class="input input-bordered">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Default Unit <span class="text-error">*</span></span></label>
                        <input type="text" name="default_unit" value="{{ old('default_unit', $product->default_unit ?? 'KG') }}"
                            class="input input-bordered" required>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Default Country of Origin <span class="text-error">*</span></span></label>
                        <input type="text" name="default_country_of_origin" value="{{ old('default_country_of_origin', $product->default_country_of_origin ?? 'Indonesia') }}"
                            class="input input-bordered" required>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Sort Order</span></label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', $product->sort_order ?? 0) }}" class="input input-bordered">
                    </div>
                    <div class="form-control justify-end">
                        <label class="label cursor-pointer justify-start gap-3">
                            <input type="checkbox" name="is_active" value="1" class="checkbox"
                                @checked(old('is_active', $product->is_active ?? true))>
                            <span class="label-text">Active (selectable in new quotations)</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="card-title text-base mb-1">Specification Attributes</h2>
                <p class="text-xs text-base-content/50 mb-3">e.g. grade, size, weight, moisture, packaging, condition, aroma, description — these auto-fill the quotation item when this product is selected.</p>

                <template x-for="(attr, index) in attributes" :key="index">
                    <div class="flex gap-2 items-end mb-2">
                        <div class="form-control w-40">
                            <label class="label py-1"><span class="label-text text-xs">Key</span></label>
                            <input type="text" :name="`attributes[${index}][key]`" x-model="attr.key"
                                placeholder="grade" class="input input-bordered input-sm">
                        </div>
                        <div class="form-control flex-1">
                            <label class="label py-1"><span class="label-text text-xs">Value</span></label>
                            <input type="text" :name="`attributes[${index}][value]`" x-model="attr.value"
                                placeholder="Gourmet Premium" class="input input-bordered input-sm">
                        </div>
                        <button type="button" @click="attributes.splice(index, 1)" class="btn btn-ghost btn-sm btn-circle text-error mb-0.5">✕</button>
                    </div>
                </template>

                <button type="button" @click="attributes.push({ key: '', value: '' })" class="btn btn-outline btn-sm">+ Add Attribute</button>
            </div>
        </div>

        <div class="flex gap-3 justify-end">
            <a href="{{ route('admin.products.index') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Product</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function productForm(initAttributes) {
    return {
        attributes: (initAttributes && initAttributes.length) ? initAttributes : [{ key: '', value: '' }],
    };
}
</script>
@endpush
