<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceExtra extends Model
{
    protected $fillable = ['invoice_id', 'label', 'amount'];

    protected $casts = ['amount' => 'decimal:2'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
