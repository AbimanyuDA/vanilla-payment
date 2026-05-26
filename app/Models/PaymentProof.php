<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentProof extends Model
{
    protected $fillable = [
        'invoice_id', 'file_path', 'file_name', 'file_content', 'file_mime',
        'status', 'admin_notes', 'confirmed_at', 'confirmed_by',
    ];

    public function getFileUrlAttribute(): string
    {
        if ($this->file_content) {
            return 'data:' . ($this->file_mime ?? 'image/jpeg') . ';base64,' . $this->file_content;
        }
        return asset('storage/' . $this->file_path);
    }

    public function isPdf(): bool
    {
        return ($this->file_mime === 'application/pdf') ||
               str_ends_with(strtolower($this->file_name ?? ''), '.pdf');
    }

    protected $casts = ['confirmed_at' => 'datetime'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function confirmedBy()
    {
        return $this->belongsTo(Admin::class, 'confirmed_by');
    }
}
