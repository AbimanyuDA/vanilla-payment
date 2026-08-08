<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A domestic transfer only needs bank name + account number, but an inbound
     * international wire needs the full beneficiary/bank/correspondent chain.
     */
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            // Beneficiary details as the sending bank must key them in.
            $table->text('beneficiary_address')->nullable()->after('bank_account_number');
            $table->string('bank_account_currency', 3)->nullable()->after('bank_account_number');

            // Beneficiary bank details.
            $table->string('bank_branch')->nullable()->after('bank_name');

            // Correspondent / intermediary bank — commonly required for USD clearing.
            $table->string('intermediary_bank_name')->nullable()->after('bank_address');
            $table->string('intermediary_bank_swift')->nullable()->after('intermediary_bank_name');
            $table->string('intermediary_bank_account')->nullable()->after('intermediary_bank_swift');

            // OUR / SHA / BEN — who absorbs the wire fees.
            $table->string('payment_charge_instruction')->nullable()->after('intermediary_bank_account');
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn([
                'beneficiary_address',
                'bank_account_currency',
                'bank_branch',
                'intermediary_bank_name',
                'intermediary_bank_swift',
                'intermediary_bank_account',
                'payment_charge_instruction',
            ]);
        });
    }
};
