"""Model → JSON-ready dict conversion."""

from datetime import date, datetime
from decimal import Decimal
from typing import Any

from .models import Admin, Buyer, CompanySettings, Invoice, PaymentProof, Product, Quotation

INVOICE_STATUS_LABELS = {
    "draft": "Draft",
    "sent": "Terkirim",
    "pending_confirmation": "Menunggu Konfirmasi",
    "paid": "Lunas",
    "cancelled": "Dibatalkan",
    "expired": "Kadaluarsa",
}

QUOTATION_STATUS_LABELS = {
    "draft": "Draft",
    "sent": "Sent",
    "accepted": "Accepted",
    "rejected": "Rejected",
    "expired": "Expired",
    "converted_to_proforma": "Converted to Proforma Invoice",
    "cancelled": "Cancelled",
}


def _num(value: Decimal | None) -> float | None:
    return float(value) if value is not None else None


def _dt(value: date | datetime | None) -> str | None:
    return value.isoformat() if value else None


def admin_out(admin: Admin | None) -> dict[str, Any] | None:
    if admin is None:
        return None
    return {"id": admin.id, "name": admin.name, "email": admin.email}


def proof_out(proof: PaymentProof, include_file: bool = True) -> dict[str, Any]:
    return {
        "id": proof.id,
        "file_name": proof.file_name,
        "file_url": proof.file_url if include_file else None,
        "is_pdf": proof.is_pdf,
        "status": proof.status,
        "admin_notes": proof.admin_notes,
        "confirmed_at": _dt(proof.confirmed_at),
        "confirmed_by": admin_out(proof.confirmer),
        "created_at": _dt(proof.created_at),
    }


def invoice_summary(inv: Invoice) -> dict[str, Any]:
    return {
        "id": inv.id,
        "invoice_number": inv.invoice_number,
        "customer_name": inv.customer_name,
        "customer_phone": inv.customer_phone,
        "customer_email": inv.customer_email,
        "total_amount": _num(inv.total_amount),
        "payment_method": inv.payment_method,
        "status": inv.status,
        "status_label": INVOICE_STATUS_LABELS.get(inv.status, inv.status),
        "due_date": _dt(inv.due_date),
        "paid_at": _dt(inv.paid_at),
        "created_at": _dt(inv.created_at),
        "creator": admin_out(inv.creator),
    }


def invoice_detail(inv: Invoice, include_proofs: bool = True) -> dict[str, Any]:
    data = invoice_summary(inv) | {
        "customer_address": inv.customer_address,
        "subtotal": _num(inv.subtotal),
        "discount": _num(inv.discount),
        "notes": inv.notes,
        "payment_token": inv.payment_token,
        "items": [
            {
                "id": i.id,
                "product_name": i.product_name,
                "description": i.description,
                "quantity": i.quantity,
                "unit_price": _num(i.unit_price),
                "total_price": _num(i.total_price),
            }
            for i in inv.items
        ],
        "extras": [{"id": e.id, "label": e.label, "amount": _num(e.amount)} for e in inv.extras],
    }
    latest = inv.latest_proof
    data["latest_proof"] = proof_out(latest, include_file=False) if latest else None
    if include_proofs:
        data["payment_proofs"] = [proof_out(p) for p in reversed(inv.payment_proofs)]
    return data


def buyer_out(b: Buyer) -> dict[str, Any]:
    return {
        "id": b.id,
        "company_name": b.company_name,
        "contact_person": b.contact_person,
        "email": b.email,
        "phone": b.phone,
        "address": b.address,
        "city": b.city,
        "state_province": b.state_province,
        "postal_code": b.postal_code,
        "country": b.country,
        "country_code": b.country_code,
        "tax_vat_number": b.tax_vat_number,
        "notes": b.notes,
    }


def product_out(p: Product) -> dict[str, Any]:
    return {
        "id": p.id,
        "name": p.name,
        "species": p.species,
        "category": p.category,
        "hs_code": p.hs_code,
        "default_unit": p.default_unit,
        "default_country_of_origin": p.default_country_of_origin,
        "is_active": p.is_active,
        "sort_order": p.sort_order,
        "attributes": p.attribute_map,
        "attribute_list": [{"key": a.key, "value": a.value} for a in p.product_attributes],
    }


QUOTATION_FIELDS = (
    "buyer_id", "buyer_company_name", "buyer_contact_person", "buyer_email", "buyer_phone",
    "buyer_address", "buyer_city", "buyer_state_province", "buyer_postal_code", "buyer_country",
    "buyer_country_code", "buyer_tax_vat_number", "validity_days", "currency", "incoterm",
    "incoterm_place", "destination_country", "destination_country_code", "port_of_loading",
    "port_of_discharge", "final_destination", "shipping_method", "production_lead_time",
    "estimated_shipment", "payment_terms", "moq", "country_of_origin", "other_charges_label",
    "tax_label", "notes",
)
QUOTATION_MONEY = (
    "subtotal", "discount_amount", "freight_amount", "insurance_amount",
    "other_charges_amount", "tax_rate", "tax_amount", "grand_total",
)
QUOTATION_ITEM_FIELDS = (
    "id", "product_id", "product_name", "species", "grade", "size", "weight", "moisture",
    "packaging", "condition", "aroma", "description", "hs_code", "country_of_origin", "unit",
)


def quotation_summary(q: Quotation) -> dict[str, Any]:
    return {
        "id": q.id,
        "quotation_number": q.quotation_number,
        "buyer_company_name": q.buyer_company_name,
        "buyer_country": q.buyer_country,
        "currency": q.currency,
        "incoterm": q.incoterm,
        "grand_total": _num(q.grand_total),
        "quotation_date": _dt(q.quotation_date),
        "valid_until": _dt(q.valid_until),
        "status": q.status,
        "status_label": QUOTATION_STATUS_LABELS.get(q.status, q.status),
        "is_expired": q.is_expired(),
        "creator": admin_out(q.creator),
    }


def quotation_detail(q: Quotation) -> dict[str, Any]:
    data = quotation_summary(q)
    data |= {f: getattr(q, f) for f in QUOTATION_FIELDS}
    data |= {f: _num(getattr(q, f)) for f in QUOTATION_MONEY}
    data["pdf_generated_at"] = _dt(q.pdf_generated_at)
    data["items"] = [
        {f: getattr(i, f) for f in QUOTATION_ITEM_FIELDS}
        | {
            "quantity": _num(i.quantity),
            "unit_price": _num(i.unit_price),
            "line_total": _num(i.line_total),
        }
        for i in q.items
    ]
    return data


def settings_out(s: CompanySettings) -> dict[str, Any]:
    data = {f: getattr(s, f) for f in CompanySettings.SNAPSHOT_FIELDS}
    data["default_terms_conditions"] = s.default_terms_conditions
    data["has_uploaded_logo"] = bool(s.logo_data)
    return data
