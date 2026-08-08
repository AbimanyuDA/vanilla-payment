<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    /** Stands in for the ISO 3166-1 alpha-3 code until a country is known. */
    public const COUNTRY_PLACEHOLDER = 'XXX';

    protected $fillable = [
        'buyer_id', 'buyer_company_name', 'buyer_contact_person', 'buyer_email', 'buyer_phone',
        'buyer_address', 'buyer_city', 'buyer_state_province', 'buyer_postal_code',
        'buyer_country', 'buyer_country_code', 'buyer_tax_vat_number',
        'quotation_date', 'validity_days', 'valid_until',
        'currency', 'incoterm', 'incoterm_place', 'destination_country', 'destination_country_code',
        'port_of_loading', 'port_of_discharge', 'final_destination', 'shipping_method',
        'production_lead_time', 'estimated_shipment',
        'payment_terms', 'moq', 'country_of_origin',
        'subtotal', 'discount_amount', 'freight_amount', 'insurance_amount',
        'other_charges_label', 'other_charges_amount',
        'tax_label', 'tax_rate', 'tax_amount', 'grand_total',
        'status', 'notes', 'converted_to_proforma_invoice_id', 'created_by',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'valid_until' => 'date',
        'pdf_generated_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'freight_amount' => 'decimal:2',
        'insurance_amount' => 'decimal:2',
        'other_charges_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'pdf_company_snapshot' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Quotation $quotation) {
            if (empty($quotation->quotation_number)) {
                $code = $quotation->destination_country_code ?: $quotation->buyer_country_code;
                $quotation->quotation_number = self::generateQuotationNumber($code);
            }
            if (empty($quotation->quotation_date)) {
                $quotation->quotation_date = now()->toDateString();
            }
            $quotation->valid_until = \Carbon\Carbon::parse($quotation->quotation_date)
                ->addDays((int) ($quotation->validity_days ?: 14));
        });

        static::updating(function (Quotation $quotation) {
            if ($quotation->isDirty(['quotation_date', 'validity_days'])) {
                $quotation->valid_until = \Carbon\Carbon::parse($quotation->quotation_date)
                    ->addDays((int) ($quotation->validity_days ?: 14));
            }

            // A quotation number generated before the buyer/destination country was known
            // carries the placeholder segment. While still a draft (never issued to the
            // buyer), heal it in place once a real country code becomes available.
            $placeholder = '-' . self::COUNTRY_PLACEHOLDER . '-';
            if ($quotation->status === 'draft' && str_contains((string) $quotation->quotation_number, $placeholder)) {
                $code = $quotation->destination_country_code ?: $quotation->buyer_country_code;
                if ($code) {
                    $quotation->quotation_number = str_replace(
                        $placeholder, '-' . strtoupper($code) . '-', $quotation->quotation_number
                    );
                }
            }
        });
    }

    public static function generateQuotationNumber(?string $countryCode = null): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $count = self::whereYear('created_at', $year)->whereMonth('created_at', $month)->count() + 1;
        $code = $countryCode ? strtoupper($countryCode) : self::COUNTRY_PLACEHOLDER;

        return "QT-{$code}-{$year}{$month}-" . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class)->orderBy('sort_order');
    }

    public function buyer()
    {
        return $this->belongsTo(Buyer::class);
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->valid_until
            && $this->valid_until->isPast()
            && !in_array($this->status, ['accepted', 'converted_to_proforma', 'cancelled', 'rejected']);
    }

    /**
     * Freezes the current company settings into pdf_company_snapshot so this quotation's
     * PDF always shows the data as it was at generation time, even if settings change later.
     */
    public function refreshPdfSnapshot(): void
    {
        $this->forceFill([
            'pdf_company_snapshot' => CompanySettings::current()->toSnapshotArray(),
            'pdf_generated_at' => now(),
        ])->save();
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'badge-ghost',
            'sent' => 'badge-info',
            'accepted' => 'badge-success',
            'rejected' => 'badge-error',
            'expired' => 'badge-neutral',
            'converted_to_proforma' => 'badge-primary',
            'cancelled' => 'badge-error',
            default => 'badge-ghost',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'sent' => 'Sent',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'expired' => 'Expired',
            'converted_to_proforma' => 'Converted to Proforma Invoice',
            'cancelled' => 'Cancelled',
            default => $this->status,
        };
    }
}
