{{-- Expects: $formAction, $method, $countries, optional $buyer --}}
<form method="POST" action="{{ $formAction }}">
    @csrf
    @if($method === 'PUT')
        @method('PUT')
    @endif

    <div class="card bg-base-100 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-control sm:col-span-2">
                    <label class="label"><span class="label-text">Buyer / Company Name <span class="text-error">*</span></span></label>
                    <input type="text" name="company_name" value="{{ old('company_name', $buyer->company_name ?? '') }}"
                        class="input input-bordered @error('company_name') input-error @enderror" required>
                    @error('company_name')<span class="text-error text-xs mt-1">{{ $message }}</span>@enderror
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">Contact Person</span></label>
                    <input type="text" name="contact_person" value="{{ old('contact_person', $buyer->contact_person ?? '') }}" class="input input-bordered">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">Email</span></label>
                    <input type="email" name="email" value="{{ old('email', $buyer->email ?? '') }}" class="input input-bordered">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">Phone</span></label>
                    <input type="text" name="phone" value="{{ old('phone', $buyer->phone ?? '') }}" class="input input-bordered">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">Country</span></label>
                    <select name="country_code" class="select select-bordered">
                        <option value="">— Select Country —</option>
                        @foreach($countries as $code => $name)
                            <option value="{{ $code }}" @selected(old('country_code', $buyer->country_code ?? '') === $code)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control sm:col-span-2">
                    <label class="label"><span class="label-text">Address</span></label>
                    <textarea name="address" class="textarea textarea-bordered" rows="2">{{ old('address', $buyer->address ?? '') }}</textarea>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">City</span></label>
                    <input type="text" name="city" value="{{ old('city', $buyer->city ?? '') }}" class="input input-bordered">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">State / Province</span></label>
                    <input type="text" name="state_province" value="{{ old('state_province', $buyer->state_province ?? '') }}" class="input input-bordered">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">Postal Code</span></label>
                    <input type="text" name="postal_code" value="{{ old('postal_code', $buyer->postal_code ?? '') }}" class="input input-bordered">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">Tax / VAT / EORI No. (optional)</span></label>
                    <input type="text" name="tax_vat_number" value="{{ old('tax_vat_number', $buyer->tax_vat_number ?? '') }}" class="input input-bordered">
                </div>
                <div class="form-control sm:col-span-2">
                    <label class="label"><span class="label-text">Notes</span></label>
                    <textarea name="notes" class="textarea textarea-bordered" rows="2">{{ old('notes', $buyer->notes ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="flex gap-3 justify-end">
        <a href="{{ route('admin.buyers.index') }}" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Buyer</button>
    </div>
</form>
