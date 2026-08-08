<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The beneficiary address is already covered by the company address block on the
     * quotation, so it is dropped from the payment panel. "Currency accepted" is widened
     * because banks commonly accept more than one (e.g. "USD / IDR").
     */
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn('beneficiary_address');
            $table->string('bank_account_currency', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->text('beneficiary_address')->nullable();
            $table->string('bank_account_currency', 3)->nullable()->change();
        });
    }
};
