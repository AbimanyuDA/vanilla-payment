<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Country codes move from ISO 3166-1 alpha-2 to alpha-3, which is the form
     * normally seen on export/customs paperwork.
     */
    public function up(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            $table->string('country_code', 3)->nullable()->change();
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->string('buyer_country_code', 3)->nullable()->change();
            $table->string('destination_country_code', 3)->nullable()->change();
        });

        $this->convertExistingCodes();
    }

    public function down(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            $table->string('country_code', 2)->nullable()->change();
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->string('buyer_country_code', 2)->nullable()->change();
            $table->string('destination_country_code', 2)->nullable()->change();
        });
    }

    /**
     * Rewrites any alpha-2 values already stored, resolving them by country name
     * against the (now alpha-3 keyed) countries config.
     */
    private function convertExistingCodes(): void
    {
        $nameToAlpha3 = array_flip(config('countries'));

        foreach (DB::table('buyers')->whereNotNull('country')->get() as $buyer) {
            if (isset($nameToAlpha3[$buyer->country])) {
                DB::table('buyers')->where('id', $buyer->id)
                    ->update(['country_code' => $nameToAlpha3[$buyer->country]]);
            }
        }

        foreach (DB::table('quotations')->get() as $quotation) {
            $updates = [];

            if ($quotation->buyer_country && isset($nameToAlpha3[$quotation->buyer_country])) {
                $updates['buyer_country_code'] = $nameToAlpha3[$quotation->buyer_country];
            }
            if ($quotation->destination_country && isset($nameToAlpha3[$quotation->destination_country])) {
                $updates['destination_country_code'] = $nameToAlpha3[$quotation->destination_country];
            }

            // Re-issue the country segment of the quotation number to match the alpha-3 code.
            $code = $updates['destination_country_code'] ?? $updates['buyer_country_code'] ?? null;
            if ($code && preg_match('/^(QT-)([A-Z]{2})(-\d{6}-\d{4})$/', $quotation->quotation_number, $m)) {
                $updates['quotation_number'] = $m[1] . $code . $m[3];
            }

            if ($updates) {
                DB::table('quotations')->where('id', $quotation->id)->update($updates);
            }
        }
    }
};
