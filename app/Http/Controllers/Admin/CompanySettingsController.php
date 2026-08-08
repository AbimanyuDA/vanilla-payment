<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanySettings;
use Illuminate\Http\Request;

class CompanySettingsController extends Controller
{
    public function edit()
    {
        $settings = CompanySettings::current();
        return view('admin.settings.company', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'country' => 'required|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'bank_branch' => 'nullable|string|max:255',
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_account_currency' => 'nullable|string|max:50',
            'swift_bic' => 'nullable|string|max:11',
            'bank_address' => 'nullable|string',
            'intermediary_bank_name' => 'nullable|string|max:255',
            'intermediary_bank_swift' => 'nullable|string|max:11',
            'intermediary_bank_account' => 'nullable|string|max:100',
            'payment_charge_instruction' => 'nullable|in:OUR,SHA,BEN',
            'authorized_person_name' => 'nullable|string|max:255',
            'authorized_person_position' => 'nullable|string|max:255',
            'default_terms_conditions' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
        ]);

        $settings = CompanySettings::current();

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('company', 'public');
            $data['logo_path'] = 'storage/' . $path;
        }

        $settings->update($data);

        return back()->with('success', 'Pengaturan perusahaan berhasil disimpan.');
    }
}
