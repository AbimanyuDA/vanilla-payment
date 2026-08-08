<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->unique();

            // Buyer reference + snapshot (kept even if the buyer master record changes later).
            $table->foreignId('buyer_id')->nullable()->constrained('buyers')->nullOnDelete();
            $table->string('buyer_company_name');
            $table->string('buyer_contact_person')->nullable();
            $table->string('buyer_email')->nullable();
            $table->string('buyer_phone')->nullable();
            $table->text('buyer_address')->nullable();
            $table->string('buyer_city')->nullable();
            $table->string('buyer_state_province')->nullable();
            $table->string('buyer_postal_code')->nullable();
            $table->string('buyer_country')->nullable();
            $table->string('buyer_country_code', 2)->nullable();
            $table->string('buyer_tax_vat_number')->nullable();

            $table->date('quotation_date');
            $table->integer('validity_days')->default(14);
            $table->date('valid_until');

            $table->string('currency', 3)->default('USD');

            $table->enum('incoterm', ['EXW', 'FCA', 'FOB', 'CFR', 'CIF', 'CPT', 'CIP', 'DAP', 'DPU', 'DDP']);
            $table->string('incoterm_place')->nullable();
            $table->string('destination_country')->nullable();
            $table->string('destination_country_code', 2)->nullable();

            $table->string('port_of_loading')->nullable();
            $table->string('port_of_discharge')->nullable();
            $table->string('final_destination')->nullable();
            $table->string('shipping_method')->nullable();
            $table->string('production_lead_time')->nullable();
            $table->string('estimated_shipment')->nullable();

            $table->text('payment_terms')->nullable();
            $table->string('moq')->nullable();
            $table->string('country_of_origin')->default('Indonesia');

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('freight_amount', 15, 2)->nullable();
            $table->decimal('insurance_amount', 15, 2)->nullable();
            $table->string('other_charges_label')->nullable();
            $table->decimal('other_charges_amount', 15, 2)->nullable();
            $table->string('tax_label')->nullable();
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->decimal('tax_amount', 15, 2)->nullable();
            $table->decimal('grand_total', 15, 2)->default(0);

            $table->enum('status', [
                'draft', 'sent', 'accepted', 'rejected', 'expired',
                'converted_to_proforma', 'cancelled',
            ])->default('draft');

            $table->text('notes')->nullable();

            // Frozen copy of company/bank/terms data, captured the moment the PDF is generated,
            // so a previously issued quotation never changes even if company settings change later.
            $table->json('pdf_company_snapshot')->nullable();
            $table->timestamp('pdf_generated_at')->nullable();

            $table->unsignedBigInteger('converted_to_proforma_invoice_id')->nullable();

            $table->foreignId('created_by')->constrained('admins')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
