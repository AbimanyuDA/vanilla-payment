<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    protected $fillable = [
        'quotation_id', 'product_id',
        'product_name', 'species', 'grade', 'size', 'weight', 'moisture',
        'packaging', 'condition', 'aroma', 'description', 'hs_code', 'country_of_origin',
        'quantity', 'unit', 'unit_price', 'line_total', 'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
