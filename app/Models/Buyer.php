<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Buyer extends Model
{
    protected $fillable = [
        'company_name', 'contact_person', 'email', 'phone',
        'address', 'city', 'state_province', 'postal_code', 'country', 'country_code',
        'tax_vat_number', 'notes', 'created_by',
    ];

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
