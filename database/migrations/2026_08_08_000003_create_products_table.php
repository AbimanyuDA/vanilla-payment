<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('species')->nullable();
            $table->enum('category', ['raw', 'value_added'])->default('raw');
            $table->string('hs_code')->nullable();
            $table->string('default_unit')->default('KG');
            $table->string('default_country_of_origin')->default('Indonesia');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
