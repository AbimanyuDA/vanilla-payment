<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'species', 'category', 'hs_code',
        'default_unit', 'default_country_of_origin', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function productAttributes()
    {
        return $this->hasMany(ProductAttribute::class)->orderBy('sort_order');
    }

    /**
     * Convenience accessor: attribute key => value, e.g. ['grade' => 'Gourmet Premium', 'size' => '16-18 cm'].
     */
    public function getAttributeMapAttribute(): array
    {
        return $this->productAttributes->pluck('value', 'key')->all();
    }

    public function quotationItems()
    {
        return $this->hasMany(QuotationItem::class);
    }
}
