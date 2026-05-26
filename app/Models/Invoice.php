<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number', 'customer_name', 'customer_phone', 'customer_email',
        'customer_address', 'subtotal', 'discount', 'total_amount',
        'payment_method', 'status', 'payment_token', 'notes', 'due_date',
        'paid_at', 'created_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber();
            }
            if (empty($invoice->payment_token)) {
                $invoice->payment_token = Str::random(32);
            }
        });
    }

    public static function generateInvoiceNumber(): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $count = self::whereYear('created_at', $year)->whereMonth('created_at', $month)->count() + 1;
        return 'INV-' . $year . $month . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function extras()
    {
        return $this->hasMany(InvoiceExtra::class);
    }

    public function paymentProofs()
    {
        return $this->hasMany(PaymentProof::class);
    }

    public function latestProof()
    {
        return $this->hasOne(PaymentProof::class)->latestOfMany();
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !in_array($this->status, ['paid', 'cancelled']);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'badge-ghost',
            'sent' => 'badge-info',
            'pending_confirmation' => 'badge-warning',
            'paid' => 'badge-success',
            'cancelled' => 'badge-error',
            'expired' => 'badge-neutral',
            default => 'badge-ghost',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'sent' => 'Terkirim',
            'pending_confirmation' => 'Menunggu Konfirmasi',
            'paid' => 'Lunas',
            'cancelled' => 'Dibatalkan',
            'expired' => 'Kadaluarsa',
            default => $this->status,
        };
    }
}
