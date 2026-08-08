<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();

            // Snapshot of the product/spec at the time the quotation was created —
            // must stay unchanged even if the product master is edited afterwards.
            $table->string('product_name');
            $table->string('species')->nullable();
            $table->string('grade')->nullable();
            $table->string('size')->nullable();
            $table->string('weight')->nullable();
            $table->string('moisture')->nullable();
            $table->string('packaging')->nullable();
            $table->string('condition')->nullable();
            $table->string('aroma')->nullable();
            $table->text('description')->nullable();
            $table->string('hs_code')->nullable();
            $table->string('country_of_origin')->nullable();

            $table->decimal('quantity', 12, 2);
            $table->string('unit')->default('KG');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);

            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};
