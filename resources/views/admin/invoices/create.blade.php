@extends('admin.layouts.app')
@section('title', 'Buat Invoice')
@section('page-title', 'Buat Invoice Baru')

@section('content')
<div class="max-w-3xl" x-data="invoiceForm()">
    <form method="POST" action="{{ route('admin.invoices.store') }}">
        @csrf

        <!-- Customer Info -->
        <div class="card bg-base-100 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="card-title text-base mb-4">Informasi Customer</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text">Nama Customer <span class="text-error">*</span></span></label>
                        <input type="text" name="customer_name" value="{{ old('customer_name') }}"
                            class="input input-bordered @error('customer_name') input-error @enderror" required>
                        @error('customer_name')<span class="text-error text-xs mt-1">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">No. Telepon (WA)</span></label>
                        <input type="text" name="customer_phone" value="{{ old('customer_phone') }}"
                            class="input input-bordered" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Email</span></label>
                        <input type="email" name="customer_email" value="{{ old('customer_email') }}"
                            class="input input-bordered">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Jatuh Tempo</span></label>
                        <input type="date" name="due_date" value="{{ old('due_date') }}"
                            class="input input-bordered" min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                    </div>
                    <div class="form-control sm:col-span-2">
                        <label class="label"><span class="label-text">Alamat Pengiriman</span></label>
                        <textarea name="customer_address" class="textarea textarea-bordered" rows="2">{{ old('customer_address') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice Items -->
        <div class="card bg-base-100 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="card-title text-base mb-4">Item Produk</h2>

                <template x-for="(item, index) in items" :key="index">
                    <div class="border rounded-lg p-3 mb-3 relative">
                        <button type="button" @click="removeItem(index)"
                            x-show="items.length > 1"
                            class="btn btn-ghost btn-xs btn-circle absolute top-2 right-2 text-error">✕</button>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                            <div class="form-control sm:col-span-2">
                                <label class="label py-1"><span class="label-text text-xs">Nama Produk *</span></label>
                                <input type="text" :name="`items[${index}][product_name]`" x-model="item.product_name"
                                    class="input input-bordered input-sm" required>
                            </div>
                            <div class="form-control sm:col-span-2">
                                <label class="label py-1"><span class="label-text text-xs">Deskripsi</span></label>
                                <input type="text" :name="`items[${index}][description]`" x-model="item.description"
                                    class="input input-bordered input-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="form-control">
                                <label class="label py-1"><span class="label-text text-xs">Qty *</span></label>
                                <input type="number" :name="`items[${index}][quantity]`" x-model.number="item.quantity"
                                    @input="calcTotal()" class="input input-bordered input-sm" min="1" required>
                            </div>
                            <div class="form-control">
                                <label class="label py-1"><span class="label-text text-xs">Harga Satuan *</span></label>
                                <input type="number" :name="`items[${index}][unit_price]`" x-model.number="item.unit_price"
                                    @input="calcTotal()" class="input input-bordered input-sm" min="0" required>
                            </div>
                            <div class="form-control">
                                <label class="label py-1"><span class="label-text text-xs">Subtotal</span></label>
                                <div class="input input-bordered input-sm bg-base-200 flex items-center text-sm font-semibold"
                                    x-text="'Rp ' + formatNum(item.quantity * item.unit_price)"></div>
                            </div>
                        </div>
                    </div>
                </template>

                <button type="button" @click="addItem()" class="btn btn-outline btn-sm w-full">+ Tambah Item</button>
            </div>
        </div>

        <!-- Biaya Tambahan -->
        <div class="card bg-base-100 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="card-title text-base mb-4">Biaya Tambahan</h2>
                <p class="text-xs text-base-content/50 mb-3">Ongkir, packaging, biaya layanan, dll.</p>

                <template x-for="(extra, index) in extras" :key="index">
                    <div class="flex gap-3 items-end mb-3">
                        <div class="form-control flex-1">
                            <label class="label py-1"><span class="label-text text-xs">Keterangan</span></label>
                            <input type="text" :name="`extras[${index}][label]`" x-model="extra.label"
                                placeholder="cth: Ongkir, Packaging" class="input input-bordered input-sm">
                        </div>
                        <div class="form-control w-40">
                            <label class="label py-1"><span class="label-text text-xs">Jumlah (Rp)</span></label>
                            <input type="number" :name="`extras[${index}][amount]`" x-model.number="extra.amount"
                                @input="calcTotal()" class="input input-bordered input-sm" min="0">
                        </div>
                        <button type="button" @click="removeExtra(index)" class="btn btn-ghost btn-sm btn-circle text-error mb-0.5">✕</button>
                    </div>
                </template>

                <button type="button" @click="addExtra()" class="btn btn-outline btn-sm">+ Tambah Biaya</button>
            </div>
        </div>

        <!-- Totals & Notes -->
        <div class="card bg-base-100 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text">Diskon (Rp)</span></label>
                        <input type="number" name="discount" x-model.number="discount" @input="calcTotal()"
                            value="{{ old('discount', 0) }}" class="input input-bordered" min="0">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Catatan</span></label>
                        <textarea name="notes" class="textarea textarea-bordered" rows="2">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="divider"></div>
                <div class="flex flex-col items-end gap-1 text-sm">
                    <div class="flex gap-4 justify-between w-56">
                        <span>Subtotal:</span>
                        <span x-text="'Rp ' + formatNum(subtotal)"></span>
                    </div>
                    <template x-for="(extra, i) in extras" :key="i">
                        <div class="flex gap-4 justify-between w-56 text-base-content/70" x-show="extra.label || extra.amount">
                            <span x-text="extra.label || 'Biaya Tambahan'"></span>
                            <span x-text="'+ Rp ' + formatNum(extra.amount)"></span>
                        </div>
                    </template>
                    <div class="flex gap-4 justify-between w-56" x-show="discount > 0">
                        <span class="text-success">Diskon:</span>
                        <span class="text-success" x-text="'- Rp ' + formatNum(discount)"></span>
                    </div>
                    <div class="flex gap-4 justify-between w-56 font-bold text-base border-t pt-1 mt-1">
                        <span>Total:</span>
                        <span x-text="'Rp ' + formatNum(total)"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-3 justify-end">
            <a href="{{ route('admin.invoices.index') }}" class="btn btn-ghost">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Invoice</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function invoiceForm() {
    return {
        items: [{ product_name: '', description: '', quantity: 1, unit_price: 0 }],
        extras: [],
        discount: 0,
        subtotal: 0,
        total: 0,
        addItem() {
            this.items.push({ product_name: '', description: '', quantity: 1, unit_price: 0 });
        },
        removeItem(index) {
            this.items.splice(index, 1);
            this.calcTotal();
        },
        addExtra() {
            this.extras.push({ label: '', amount: 0 });
        },
        removeExtra(index) {
            this.extras.splice(index, 1);
            this.calcTotal();
        },
        calcTotal() {
            this.subtotal = this.items.reduce((sum, i) => sum + (i.quantity * i.unit_price), 0);
            const extrasTotal = this.extras.reduce((sum, e) => sum + (e.amount || 0), 0);
            this.total = this.subtotal - this.discount + extrasTotal;
        },
        formatNum(n) {
            return new Intl.NumberFormat('id-ID').format(n || 0);
        }
    }
}
</script>
@endpush
@endsection
