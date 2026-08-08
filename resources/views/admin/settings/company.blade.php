@extends('admin.layouts.app')
@section('title', 'Company Settings')
@section('page-title', 'Company Settings')

@section('content')
<div class="max-w-2xl">
    <p class="text-sm text-base-content/60 mb-4">
        This data is used across export quotations (company header, bank details, terms & conditions).
        Each quotation freezes a snapshot of this data at the moment its PDF is generated, so past quotations
        stay unchanged even if you update these settings later.
    </p>

    <form method="POST" action="{{ route('admin.settings.company.update') }}" enctype="multipart/form-data">
        @csrf

        <div class="card bg-base-100 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="card-title text-base mb-4">Company Identity</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control sm:col-span-2">
                        <label class="label"><span class="label-text">Company Name *</span></label>
                        <input type="text" name="company_name" value="{{ old('company_name', $settings->company_name) }}" class="input input-bordered" required>
                    </div>
                    <div class="form-control sm:col-span-2">
                        <label class="label"><span class="label-text">Logo</span></label>
                        @if($settings->logo_path)
                            <img src="{{ asset($settings->logo_path) }}" class="h-12 w-auto object-contain mb-2" alt="Current logo">
                        @endif
                        <input type="file" name="logo" accept="image/*" class="file-input file-input-bordered file-input-sm">
                    </div>
                    <div class="form-control sm:col-span-2">
                        <label class="label"><span class="label-text">Address</span></label>
                        <textarea name="address" class="textarea textarea-bordered" rows="2">{{ old('address', $settings->address) }}</textarea>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Country *</span></label>
                        <input type="text" name="country" value="{{ old('country', $settings->country) }}" class="input input-bordered" required>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Email</span></label>
                        <input type="email" name="email" value="{{ old('email', $settings->email) }}" class="input input-bordered">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Phone</span></label>
                        <input type="text" name="phone" value="{{ old('phone', $settings->phone) }}" class="input input-bordered">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Website</span></label>
                        <input type="text" name="website" value="{{ old('website', $settings->website) }}" class="input input-bordered">
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="card-title text-base mb-1">Bank Details for International Wire Transfer</h2>
                <p class="text-xs text-base-content/50 mb-4">
                    These appear on the quotation PDF exactly as entered here. The first five are the
                    minimum an overseas buyer's bank needs; the rest are optional.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text">Bank Name</span></label>
                        <input type="text" name="bank_name" value="{{ old('bank_name', $settings->bank_name) }}"
                            class="input input-bordered" placeholder="e.g. Bank Rakyat Indonesia (BRI)">
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">SWIFT / BIC Code</span>
                            <span class="label-text-alt text-base-content/50">8 or 11 characters</span>
                        </label>
                        <input type="text" name="swift_bic" value="{{ old('swift_bic', $settings->swift_bic) }}"
                            class="input input-bordered uppercase" maxlength="11" placeholder="e.g. BRINIDJA">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Account Name (A/N)</span></label>
                        <input type="text" name="bank_account_name" value="{{ old('bank_account_name', $settings->bank_account_name) }}"
                            class="input input-bordered" placeholder="Exactly as registered on the account">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Account Number</span></label>
                        <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $settings->bank_account_number) }}" class="input input-bordered">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Currency Accepted</span></label>
                        <input type="text" name="bank_account_currency" value="{{ old('bank_account_currency', $settings->bank_account_currency) }}"
                            class="input input-bordered" placeholder="e.g. USD / IDR">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Charge Instruction</span></label>
                        <select name="payment_charge_instruction" class="select select-bordered">
                            <option value="">— Not specified —</option>
                            @foreach(['OUR' => 'OUR — sender pays all fees', 'SHA' => 'SHA — fees shared', 'BEN' => 'BEN — beneficiary pays all fees'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('payment_charge_instruction', $settings->payment_charge_instruction) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="text-xs font-semibold text-base-content/60 mb-2">OPTIONAL</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text">Branch Name</span></label>
                        <input type="text" name="bank_branch" value="{{ old('bank_branch', $settings->bank_branch) }}" class="input input-bordered">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Bank Address</span></label>
                        <input type="text" name="bank_address" value="{{ old('bank_address', $settings->bank_address) }}" class="input input-bordered">
                    </div>
                </div>

                <div class="text-xs font-semibold text-base-content/60 mb-2">INTERMEDIARY / CORRESPONDENT BANK</div>
                <p class="text-xs text-base-content/50 mb-3">
                    Often required for USD payments, which clear through a correspondent bank.
                    Your bank can tell you which one it routes through — leave blank if not applicable.
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control sm:col-span-2">
                        <label class="label"><span class="label-text">Intermediary Bank Name</span></label>
                        <input type="text" name="intermediary_bank_name" value="{{ old('intermediary_bank_name', $settings->intermediary_bank_name) }}" class="input input-bordered">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Intermediary SWIFT / BIC</span></label>
                        <input type="text" name="intermediary_bank_swift" value="{{ old('intermediary_bank_swift', $settings->intermediary_bank_swift) }}"
                            class="input input-bordered uppercase" maxlength="11">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Intermediary Account No. (if any)</span></label>
                        <input type="text" name="intermediary_bank_account" value="{{ old('intermediary_bank_account', $settings->intermediary_bank_account) }}" class="input input-bordered">
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="card-title text-base mb-4">Authorized Signatory</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text">Name</span></label>
                        <input type="text" name="authorized_person_name" value="{{ old('authorized_person_name', $settings->authorized_person_name) }}" class="input input-bordered">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Position</span></label>
                        <input type="text" name="authorized_person_position" value="{{ old('authorized_person_position', $settings->authorized_person_position) }}" class="input input-bordered">
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="card-title text-base mb-4">Default Terms & Conditions</h2>
                <p class="text-xs text-base-content/50 mb-3">Shown on every quotation PDF. Edit freely — these are contractual terms, not legal requirements.</p>
                <textarea name="default_terms_conditions" class="textarea textarea-bordered w-full" rows="10">{{ old('default_terms_conditions', $settings->default_terms_conditions) }}</textarea>
            </div>
        </div>

        <div class="flex gap-3 justify-end">
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>
    </form>
</div>
@endsection
