<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySettings extends Model
{
    protected $table = 'company_settings';

    protected $fillable = [
        'company_name', 'logo_path', 'address', 'country', 'email', 'phone', 'website',
        'bank_name', 'bank_branch', 'bank_account_name', 'bank_account_number',
        'bank_account_currency', 'swift_bic', 'bank_address',
        'intermediary_bank_name', 'intermediary_bank_swift', 'intermediary_bank_account',
        'payment_charge_instruction',
        'authorized_person_name', 'authorized_person_position', 'default_terms_conditions',
    ];

    /**
     * Company settings is a single-row table. Returns the existing row,
     * or creates a bare default one on first access.
     */
    public static function current(): self
    {
        return self::first() ?? self::create([
            'company_name' => config('app.name'),
        ]);
    }

    /**
     * Snapshot of the fields that get frozen into a quotation's pdf_company_snapshot
     * at the moment its PDF is generated.
     */
    public function toSnapshotArray(): array
    {
        return [
            'company_name' => $this->company_name,
            'logo_path' => $this->logo_path,
            'address' => $this->address,
            'country' => $this->country,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'bank_name' => $this->bank_name,
            'bank_branch' => $this->bank_branch,
            'bank_account_name' => $this->bank_account_name,
            'bank_account_number' => $this->bank_account_number,
            'bank_account_currency' => $this->bank_account_currency,
            'swift_bic' => $this->swift_bic,
            'bank_address' => $this->bank_address,
            'intermediary_bank_name' => $this->intermediary_bank_name,
            'intermediary_bank_swift' => $this->intermediary_bank_swift,
            'intermediary_bank_account' => $this->intermediary_bank_account,
            'payment_charge_instruction' => $this->payment_charge_instruction,
            'authorized_person_name' => $this->authorized_person_name,
            'authorized_person_position' => $this->authorized_person_position,
            'terms_conditions' => $this->default_terms_conditions,
        ];
    }
}
