<?php

namespace Database\Seeders;

use App\Models\CompanySettings;
use Illuminate\Database\Seeder;

class CompanySettingsSeeder extends Seeder
{
    public function run(): void
    {
        if (CompanySettings::query()->exists()) {
            return;
        }

        CompanySettings::create([
            'company_name' => 'Vanilla Royal (PT Coffee Nation Prosperity)',
            'logo_path' => 'images/logo/logo.webp',
            'address' => 'Jl. Raya Bukit Kweni No 40, Sukodono, Kab. Sidoarjo, Jawa Timur, Indonesia',
            'country' => 'Indonesia',
            'email' => 'info@vanillaroyal.id',
            'phone' => '+62 858-5366-9568',
            'website' => 'www.vanillaroyal.id',
            'bank_name' => 'Bank Rakyat Indonesia (BRI)',
            'swift_bic' => 'BRINIDJA',
            'bank_account_name' => 'PT COFFEE NATION PROSPERITY',
            'bank_account_number' => '115601002957562',
            'bank_account_currency' => 'USD / IDR',
            'payment_charge_instruction' => 'OUR',
            // Optional extras — fill in from the bank when needed.
            'bank_branch' => null,
            'bank_address' => null,
            'intermediary_bank_name' => null,
            'intermediary_bank_swift' => null,
            'intermediary_bank_account' => null,
            'authorized_person_name' => null,
            'authorized_person_position' => null,
            'default_terms_conditions' => implode("\n\n", [
                "1. Price & Currency: Prices quoted are in the currency stated on this quotation and are subject to change without prior notice unless otherwise agreed in writing.",
                "2. Incoterms: Trade terms follow Incoterms 2020 as specified on this quotation.",
                "3. Payment Terms: As specified on this quotation. Payment must be made in accordance with the agreed schedule.",
                "4. Quotation Validity: This quotation is valid until the date stated above.",
                "5. Lead Time: Production and shipment lead times are estimates and may vary depending on order volume and logistics conditions.",
                "6. Packaging: Products are packed as specified per item unless otherwise agreed.",
                "7. Country of Origin: Indonesia, unless otherwise stated.",
                "8. Shipping: Shipping arrangements follow the agreed Incoterm and are subject to carrier availability.",
                "9. Quality / Specification: Products are supplied according to the specification stated per item on this quotation.",
                "10. Inspection: Pre-shipment inspection may be arranged upon buyer's request and at buyer's cost, unless otherwise agreed.",
                "11. Force Majeure: Neither party shall be liable for delay or failure to perform due to causes beyond its reasonable control.",
                "12. Documentation: Export documentation will be provided as applicable to the agreed Incoterm and destination.",
                "13. Order Confirmation: This quotation becomes binding only upon written order confirmation from both parties.",
            ]),
        ]);
    }
}
