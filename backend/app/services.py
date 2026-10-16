"""Business rules: numbering, server-side totals, status transitions, snapshots.

Kept free of HTTP concerns so the rules can be exercised directly in tests.
"""

import secrets
import string
from datetime import date, datetime, timedelta
from decimal import Decimal

from sqlalchemy import func, select
from sqlalchemy.orm import Session

from .data import COUNTRIES
from .db import now_local
from .models import (
    CompanySettings,
    Invoice,
    InvoiceExtra,
    InvoiceItem,
    Product,
    ProductAttribute,
    Quotation,
    QuotationItem,
)
from .schemas import InvoiceIn, ProductIn, QuotationIn

COUNTRY_PLACEHOLDER = "XXX"
ZERO = Decimal("0")


class BusinessRuleError(Exception):
    """A request that is valid in shape but not allowed in the current state."""


def _month_bounds(moment: datetime) -> tuple[datetime, datetime]:
    start = moment.replace(day=1, hour=0, minute=0, second=0, microsecond=0)
    end = (start + timedelta(days=32)).replace(day=1)
    return start, end


def _count_this_month(db: Session, model: type, moment: datetime) -> int:
    start, end = _month_bounds(moment)
    stmt = select(func.count()).select_from(model).where(
        model.created_at >= start, model.created_at < end
    )
    return db.scalar(stmt) or 0


def _random_token(length: int = 32) -> str:
    alphabet = string.ascii_letters + string.digits
    return "".join(secrets.choice(alphabet) for _ in range(length))


# ── Invoices ────────────────────────────────────────────────────────────────


def generate_invoice_number(db: Session) -> str:
    now = now_local()
    count = _count_this_month(db, Invoice, now) + 1
    return f"INV-{now:%Y%m}-{count:04d}"


def _apply_invoice_payload(invoice: Invoice, data: InvoiceIn) -> None:
    subtotal = sum((Decimal(i.quantity) * i.unit_price for i in data.items), ZERO)
    discount = data.discount or ZERO
    extras = [e for e in data.extras if e.label and e.amount is not None]
    extras_total = sum((e.amount for e in extras), ZERO)

    invoice.customer_name = data.customer_name
    invoice.customer_phone = data.customer_phone
    invoice.customer_email = data.customer_email
    invoice.customer_address = data.customer_address
    invoice.subtotal = subtotal
    invoice.discount = discount
    invoice.total_amount = subtotal - discount + extras_total
    invoice.due_date = data.due_date
    invoice.notes = data.notes
    invoice.items = [
        InvoiceItem(
            product_name=i.product_name,
            description=i.description,
            quantity=i.quantity,
            unit_price=i.unit_price,
            total_price=Decimal(i.quantity) * i.unit_price,
        )
        for i in data.items
    ]
    invoice.extras = [InvoiceExtra(label=e.label, amount=e.amount) for e in extras]


def create_invoice(db: Session, data: InvoiceIn, admin_id: int, today: date) -> Invoice:
    if data.due_date and data.due_date <= today:
        raise BusinessRuleError("Jatuh tempo harus setelah hari ini.")
    invoice = Invoice(
        invoice_number=generate_invoice_number(db),
        payment_token=_random_token(),
        status="sent",
        created_by=admin_id,
    )
    _apply_invoice_payload(invoice, data)
    db.add(invoice)
    db.commit()
    return invoice


def update_invoice(db: Session, invoice: Invoice, data: InvoiceIn, today: date) -> Invoice:
    if invoice.status in ("paid", "cancelled"):
        raise BusinessRuleError("Invoice yang sudah lunas atau dibatalkan tidak bisa diedit.")
    _apply_invoice_payload(invoice, data)
    if invoice.status == "expired" and (invoice.due_date is None or invoice.due_date >= today):
        # Extending the due date reopens the invoice where it left off.
        latest = invoice.latest_proof
        invoice.status = "pending_confirmation" if latest and latest.status == "pending" else "sent"
    db.commit()
    return invoice


def confirm_payment(
    db: Session, invoice: Invoice, action: str, notes: str | None, admin_id: int
) -> str:
    proof = invoice.latest_proof
    if action == "confirm":
        if proof:
            proof.status = "confirmed"
            proof.confirmed_at = now_local()
            proof.confirmed_by = admin_id
            proof.admin_notes = notes
        invoice.status = "paid"
        invoice.paid_at = now_local()
        db.commit()
        return "Pembayaran dikonfirmasi. Invoice telah lunas."
    if proof:
        proof.status = "rejected"
        proof.admin_notes = notes
    invoice.status = "sent"
    db.commit()
    return "Bukti pembayaran ditolak. Customer perlu upload ulang."


# ── Quotations ──────────────────────────────────────────────────────────────


def generate_quotation_number(db: Session, country_code: str | None) -> str:
    now = now_local()
    count = _count_this_month(db, Quotation, now) + 1
    code = country_code.upper() if country_code else COUNTRY_PLACEHOLDER
    return f"QT-{code}-{now:%Y%m}-{count:04d}"


def heal_quotation_number(quotation: Quotation) -> None:
    """While still a draft, replace the XXX placeholder once a country is known."""
    placeholder = f"-{COUNTRY_PLACEHOLDER}-"
    if quotation.status != "draft" or placeholder not in (quotation.quotation_number or ""):
        return
    code = quotation.destination_country_code or quotation.buyer_country_code
    if code:
        quotation.quotation_number = quotation.quotation_number.replace(
            placeholder, f"-{code.upper()}-"
        )


def save_quotation(
    db: Session, quotation: Quotation | None, data: QuotationIn, admin_id: int
) -> Quotation:
    from .models import Buyer

    if quotation is not None and quotation.status in ("accepted", "converted_to_proforma", "cancelled"):
        raise BusinessRuleError("Quotation dengan status ini tidak bisa diedit.")

    fields = data.model_dump(exclude={"items"})
    if data.buyer_id:
        buyer = db.get(Buyer, data.buyer_id)
        if buyer is None:
            raise BusinessRuleError("Buyer tidak ditemukan.")
        fields["buyer_country_code"] = buyer.country_code
    fields["buyer_country"] = COUNTRIES.get(fields["buyer_country_code"] or "")
    fields["destination_country"] = COUNTRIES.get(fields["destination_country_code"] or "")
    fields["country_of_origin"] = fields["country_of_origin"] or "Indonesia"
    fields["discount_amount"] = fields["discount_amount"] or ZERO

    # Totals are always recomputed server-side; client totals are never trusted.
    subtotal = sum((i.quantity * i.unit_price for i in data.items), ZERO)
    fields["subtotal"] = subtotal
    fields["grand_total"] = (
        subtotal
        - fields["discount_amount"]
        + (fields["freight_amount"] or ZERO)
        + (fields["insurance_amount"] or ZERO)
        + (fields["other_charges_amount"] or ZERO)
        + (fields["tax_amount"] or ZERO)
    )

    if quotation is None:
        code = fields["destination_country_code"] or fields["buyer_country_code"]
        quotation = Quotation(
            quotation_number=generate_quotation_number(db, code),
            status="draft",
            created_by=admin_id,
        )
        db.add(quotation)

    for key, value in fields.items():
        setattr(quotation, key, value)
    quotation.valid_until = quotation.quotation_date + timedelta(days=quotation.validity_days or 14)
    heal_quotation_number(quotation)

    for item in data.items:
        if item.product_id and db.get(Product, item.product_id) is None:
            raise BusinessRuleError("Produk tidak ditemukan.")
    quotation.items = [
        QuotationItem(
            **item.model_dump(exclude={"unit"}),
            unit=item.unit or "KG",
            line_total=item.quantity * item.unit_price,
            sort_order=index,
        )
        for index, item in enumerate(data.items)
    ]
    db.commit()
    return quotation


def current_company_settings(db: Session) -> CompanySettings:
    settings = db.scalar(select(CompanySettings).order_by(CompanySettings.id).limit(1))
    if settings is None:
        settings = CompanySettings(company_name="Vanilla Royal Payment", country="Indonesia")
        db.add(settings)
        db.commit()
    return settings


def refresh_pdf_snapshot(db: Session, quotation: Quotation) -> None:
    """Freeze company details so an issued PDF never changes retroactively."""
    quotation.pdf_company_snapshot = current_company_settings(db).to_snapshot()
    quotation.pdf_generated_at = now_local()
    db.commit()


# ── Products ────────────────────────────────────────────────────────────────


def save_product(db: Session, product: Product | None, data: ProductIn) -> Product:
    if product is None:
        product = Product()
        db.add(product)
    for key, value in data.model_dump(exclude={"attributes"}).items():
        setattr(product, key, value)
    attrs = [a for a in data.attributes if a.key and a.value]
    product.product_attributes = [
        ProductAttribute(key=a.key, value=a.value, sort_order=i) for i, a in enumerate(attrs)
    ]
    db.commit()
    return product
