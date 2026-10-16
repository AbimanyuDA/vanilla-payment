"""SQLAlchemy models mapped 1:1 onto the tables created by the Laravel migrations.

Column names and types intentionally mirror the existing schema so the new app
runs against the production database without a data migration.
"""

from datetime import date, datetime
from decimal import Decimal
from typing import Any, Optional

from sqlalchemy import JSON, Boolean, Date, DateTime, ForeignKey, Integer, Numeric, String, Text
from sqlalchemy.orm import Mapped, mapped_column, relationship

from .db import Base, now_local


class TimestampMixin:
    created_at: Mapped[Optional[datetime]] = mapped_column(DateTime, default=now_local)
    updated_at: Mapped[Optional[datetime]] = mapped_column(DateTime, default=now_local, onupdate=now_local)


class Admin(TimestampMixin, Base):
    __tablename__ = "admins"

    id: Mapped[int] = mapped_column(primary_key=True)
    name: Mapped[str] = mapped_column(String(255))
    email: Mapped[str] = mapped_column(String(255), unique=True)
    password: Mapped[str] = mapped_column(String(255))
    remember_token: Mapped[Optional[str]] = mapped_column(String(100))


class Invoice(TimestampMixin, Base):
    __tablename__ = "invoices"

    id: Mapped[int] = mapped_column(primary_key=True)
    invoice_number: Mapped[str] = mapped_column(String(255), unique=True)
    customer_name: Mapped[str] = mapped_column(String(255))
    customer_phone: Mapped[Optional[str]] = mapped_column(String(255))
    customer_email: Mapped[Optional[str]] = mapped_column(String(255))
    customer_address: Mapped[Optional[str]] = mapped_column(String(255))
    subtotal: Mapped[Decimal] = mapped_column(Numeric(15, 2), default=0)
    discount: Mapped[Decimal] = mapped_column(Numeric(15, 2), default=0)
    total_amount: Mapped[Decimal] = mapped_column(Numeric(15, 2), default=0)
    payment_method: Mapped[Optional[str]] = mapped_column(String(255))
    status: Mapped[str] = mapped_column(String(255), default="draft")
    payment_token: Mapped[str] = mapped_column(String(255), unique=True)
    notes: Mapped[Optional[str]] = mapped_column(Text)
    due_date: Mapped[Optional[date]] = mapped_column(Date)
    paid_at: Mapped[Optional[datetime]] = mapped_column(DateTime)
    created_by: Mapped[int] = mapped_column(ForeignKey("admins.id"))

    items: Mapped[list["InvoiceItem"]] = relationship(
        cascade="all, delete-orphan", order_by="InvoiceItem.id"
    )
    extras: Mapped[list["InvoiceExtra"]] = relationship(
        cascade="all, delete-orphan", order_by="InvoiceExtra.id"
    )
    payment_proofs: Mapped[list["PaymentProof"]] = relationship(
        cascade="all, delete-orphan", order_by="PaymentProof.id"
    )
    creator: Mapped[Optional[Admin]] = relationship(foreign_keys=[created_by])

    @property
    def latest_proof(self) -> Optional["PaymentProof"]:
        return self.payment_proofs[-1] if self.payment_proofs else None

    def is_expired(self) -> bool:
        from .db import today_local

        # A submitted proof means the customer already paid before (or around) the
        # due date; it must stay reviewable instead of silently expiring.
        return bool(
            self.due_date
            and self.due_date < today_local()
            and self.status not in ("paid", "cancelled", "pending_confirmation")
        )


class InvoiceItem(TimestampMixin, Base):
    __tablename__ = "invoice_items"

    id: Mapped[int] = mapped_column(primary_key=True)
    invoice_id: Mapped[int] = mapped_column(ForeignKey("invoices.id", ondelete="CASCADE"))
    product_name: Mapped[str] = mapped_column(String(255))
    description: Mapped[Optional[str]] = mapped_column(Text)
    quantity: Mapped[int] = mapped_column(Integer, default=1)
    unit_price: Mapped[Decimal] = mapped_column(Numeric(15, 2))
    total_price: Mapped[Decimal] = mapped_column(Numeric(15, 2))


class InvoiceExtra(TimestampMixin, Base):
    __tablename__ = "invoice_extras"

    id: Mapped[int] = mapped_column(primary_key=True)
    invoice_id: Mapped[int] = mapped_column(ForeignKey("invoices.id", ondelete="CASCADE"))
    label: Mapped[str] = mapped_column(String(255))
    amount: Mapped[Decimal] = mapped_column(Numeric(15, 2))


class PaymentProof(TimestampMixin, Base):
    __tablename__ = "payment_proofs"

    id: Mapped[int] = mapped_column(primary_key=True)
    invoice_id: Mapped[int] = mapped_column(ForeignKey("invoices.id", ondelete="CASCADE"))
    file_path: Mapped[str] = mapped_column(String(255))
    file_content: Mapped[Optional[str]] = mapped_column(Text)
    file_mime: Mapped[Optional[str]] = mapped_column(String(255))
    file_name: Mapped[str] = mapped_column(String(255))
    status: Mapped[str] = mapped_column(String(255), default="pending")
    admin_notes: Mapped[Optional[str]] = mapped_column(Text)
    confirmed_at: Mapped[Optional[datetime]] = mapped_column(DateTime)
    confirmed_by: Mapped[Optional[int]] = mapped_column(ForeignKey("admins.id", ondelete="SET NULL"))

    confirmer: Mapped[Optional[Admin]] = relationship(foreign_keys=[confirmed_by])

    @property
    def is_pdf(self) -> bool:
        return self.file_mime == "application/pdf" or (self.file_name or "").lower().endswith(".pdf")

    @property
    def file_url(self) -> Optional[str]:
        if not self.file_content:
            return None
        return f"data:{self.file_mime or 'image/jpeg'};base64,{self.file_content}"


class CompanySettings(TimestampMixin, Base):
    __tablename__ = "company_settings"

    id: Mapped[int] = mapped_column(primary_key=True)
    company_name: Mapped[str] = mapped_column(String(255))
    logo_path: Mapped[Optional[str]] = mapped_column(String(255))
    logo_data: Mapped[Optional[str]] = mapped_column(Text)
    address: Mapped[Optional[str]] = mapped_column(Text)
    country: Mapped[str] = mapped_column(String(255), default="Indonesia")
    email: Mapped[Optional[str]] = mapped_column(String(255))
    phone: Mapped[Optional[str]] = mapped_column(String(255))
    website: Mapped[Optional[str]] = mapped_column(String(255))
    bank_name: Mapped[Optional[str]] = mapped_column(String(255))
    bank_branch: Mapped[Optional[str]] = mapped_column(String(255))
    bank_account_name: Mapped[Optional[str]] = mapped_column(String(255))
    bank_account_number: Mapped[Optional[str]] = mapped_column(String(255))
    bank_account_currency: Mapped[Optional[str]] = mapped_column(String(50))
    swift_bic: Mapped[Optional[str]] = mapped_column(String(255))
    bank_address: Mapped[Optional[str]] = mapped_column(Text)
    intermediary_bank_name: Mapped[Optional[str]] = mapped_column(String(255))
    intermediary_bank_swift: Mapped[Optional[str]] = mapped_column(String(255))
    intermediary_bank_account: Mapped[Optional[str]] = mapped_column(String(255))
    payment_charge_instruction: Mapped[Optional[str]] = mapped_column(String(255))
    authorized_person_name: Mapped[Optional[str]] = mapped_column(String(255))
    authorized_person_position: Mapped[Optional[str]] = mapped_column(String(255))
    default_terms_conditions: Mapped[Optional[str]] = mapped_column(Text)

    SNAPSHOT_FIELDS = (
        "company_name", "logo_path", "address", "country", "email", "phone", "website",
        "bank_name", "bank_branch", "bank_account_name", "bank_account_number",
        "bank_account_currency", "swift_bic", "bank_address", "intermediary_bank_name",
        "intermediary_bank_swift", "intermediary_bank_account", "payment_charge_instruction",
        "authorized_person_name", "authorized_person_position",
    )

    def to_snapshot(self) -> dict[str, Any]:
        snap = {f: getattr(self, f) for f in self.SNAPSHOT_FIELDS}
        snap["terms_conditions"] = self.default_terms_conditions
        # The uploaded logo is frozen too, so a re-download shows the issued logo.
        snap["logo_data"] = self.logo_data
        return snap


class Buyer(TimestampMixin, Base):
    __tablename__ = "buyers"

    id: Mapped[int] = mapped_column(primary_key=True)
    company_name: Mapped[str] = mapped_column(String(255))
    contact_person: Mapped[Optional[str]] = mapped_column(String(255))
    email: Mapped[Optional[str]] = mapped_column(String(255))
    phone: Mapped[Optional[str]] = mapped_column(String(255))
    address: Mapped[Optional[str]] = mapped_column(Text)
    city: Mapped[Optional[str]] = mapped_column(String(255))
    state_province: Mapped[Optional[str]] = mapped_column(String(255))
    postal_code: Mapped[Optional[str]] = mapped_column(String(255))
    country: Mapped[Optional[str]] = mapped_column(String(255))
    country_code: Mapped[Optional[str]] = mapped_column(String(3))
    tax_vat_number: Mapped[Optional[str]] = mapped_column(String(255))
    notes: Mapped[Optional[str]] = mapped_column(Text)
    created_by: Mapped[Optional[int]] = mapped_column(ForeignKey("admins.id", ondelete="SET NULL"))


class Product(TimestampMixin, Base):
    __tablename__ = "products"

    id: Mapped[int] = mapped_column(primary_key=True)
    name: Mapped[str] = mapped_column(String(255))
    species: Mapped[Optional[str]] = mapped_column(String(255))
    category: Mapped[str] = mapped_column(String(255), default="raw")
    hs_code: Mapped[Optional[str]] = mapped_column(String(255))
    default_unit: Mapped[str] = mapped_column(String(255), default="KG")
    default_country_of_origin: Mapped[str] = mapped_column(String(255), default="Indonesia")
    is_active: Mapped[bool] = mapped_column(Boolean, default=True)
    sort_order: Mapped[int] = mapped_column(Integer, default=0)

    product_attributes: Mapped[list["ProductAttribute"]] = relationship(
        cascade="all, delete-orphan", order_by="ProductAttribute.sort_order"
    )

    @property
    def attribute_map(self) -> dict[str, str]:
        return {a.key: a.value for a in self.product_attributes}


class ProductAttribute(TimestampMixin, Base):
    __tablename__ = "product_attributes"

    id: Mapped[int] = mapped_column(primary_key=True)
    product_id: Mapped[int] = mapped_column(ForeignKey("products.id", ondelete="CASCADE"))
    key: Mapped[str] = mapped_column(String(255))
    value: Mapped[str] = mapped_column(String(255))
    sort_order: Mapped[int] = mapped_column(Integer, default=0)


class Quotation(TimestampMixin, Base):
    __tablename__ = "quotations"

    id: Mapped[int] = mapped_column(primary_key=True)
    quotation_number: Mapped[str] = mapped_column(String(255), unique=True)
    buyer_id: Mapped[Optional[int]] = mapped_column(ForeignKey("buyers.id", ondelete="SET NULL"))
    buyer_company_name: Mapped[str] = mapped_column(String(255))
    buyer_contact_person: Mapped[Optional[str]] = mapped_column(String(255))
    buyer_email: Mapped[Optional[str]] = mapped_column(String(255))
    buyer_phone: Mapped[Optional[str]] = mapped_column(String(255))
    buyer_address: Mapped[Optional[str]] = mapped_column(Text)
    buyer_city: Mapped[Optional[str]] = mapped_column(String(255))
    buyer_state_province: Mapped[Optional[str]] = mapped_column(String(255))
    buyer_postal_code: Mapped[Optional[str]] = mapped_column(String(255))
    buyer_country: Mapped[Optional[str]] = mapped_column(String(255))
    buyer_country_code: Mapped[Optional[str]] = mapped_column(String(3))
    buyer_tax_vat_number: Mapped[Optional[str]] = mapped_column(String(255))
    quotation_date: Mapped[date] = mapped_column(Date)
    validity_days: Mapped[int] = mapped_column(Integer, default=14)
    valid_until: Mapped[date] = mapped_column(Date)
    currency: Mapped[str] = mapped_column(String(3), default="USD")
    incoterm: Mapped[str] = mapped_column(String(255))
    incoterm_place: Mapped[Optional[str]] = mapped_column(String(255))
    destination_country: Mapped[Optional[str]] = mapped_column(String(255))
    destination_country_code: Mapped[Optional[str]] = mapped_column(String(3))
    port_of_loading: Mapped[Optional[str]] = mapped_column(String(255))
    port_of_discharge: Mapped[Optional[str]] = mapped_column(String(255))
    final_destination: Mapped[Optional[str]] = mapped_column(String(255))
    shipping_method: Mapped[Optional[str]] = mapped_column(String(255))
    production_lead_time: Mapped[Optional[str]] = mapped_column(String(255))
    estimated_shipment: Mapped[Optional[str]] = mapped_column(String(255))
    payment_terms: Mapped[Optional[str]] = mapped_column(Text)
    moq: Mapped[Optional[str]] = mapped_column(String(255))
    country_of_origin: Mapped[str] = mapped_column(String(255), default="Indonesia")
    subtotal: Mapped[Decimal] = mapped_column(Numeric(15, 2), default=0)
    discount_amount: Mapped[Decimal] = mapped_column(Numeric(15, 2), default=0)
    freight_amount: Mapped[Optional[Decimal]] = mapped_column(Numeric(15, 2))
    insurance_amount: Mapped[Optional[Decimal]] = mapped_column(Numeric(15, 2))
    other_charges_label: Mapped[Optional[str]] = mapped_column(String(255))
    other_charges_amount: Mapped[Optional[Decimal]] = mapped_column(Numeric(15, 2))
    tax_label: Mapped[Optional[str]] = mapped_column(String(255))
    tax_rate: Mapped[Optional[Decimal]] = mapped_column(Numeric(5, 2))
    tax_amount: Mapped[Optional[Decimal]] = mapped_column(Numeric(15, 2))
    grand_total: Mapped[Decimal] = mapped_column(Numeric(15, 2), default=0)
    status: Mapped[str] = mapped_column(String(255), default="draft")
    notes: Mapped[Optional[str]] = mapped_column(Text)
    pdf_company_snapshot: Mapped[Optional[dict[str, Any]]] = mapped_column(JSON)
    pdf_generated_at: Mapped[Optional[datetime]] = mapped_column(DateTime)
    converted_to_proforma_invoice_id: Mapped[Optional[int]] = mapped_column(Integer)
    created_by: Mapped[int] = mapped_column(ForeignKey("admins.id", ondelete="CASCADE"))

    items: Mapped[list["QuotationItem"]] = relationship(
        cascade="all, delete-orphan", order_by="QuotationItem.sort_order"
    )
    buyer: Mapped[Optional[Buyer]] = relationship()
    creator: Mapped[Optional[Admin]] = relationship(foreign_keys=[created_by])

    def is_expired(self) -> bool:
        from .db import today_local

        return bool(
            self.valid_until
            and self.valid_until < today_local()
            and self.status not in ("accepted", "converted_to_proforma", "cancelled", "rejected")
        )


class QuotationItem(TimestampMixin, Base):
    __tablename__ = "quotation_items"

    id: Mapped[int] = mapped_column(primary_key=True)
    quotation_id: Mapped[int] = mapped_column(ForeignKey("quotations.id", ondelete="CASCADE"))
    product_id: Mapped[Optional[int]] = mapped_column(ForeignKey("products.id", ondelete="SET NULL"))
    product_name: Mapped[str] = mapped_column(String(255))
    species: Mapped[Optional[str]] = mapped_column(String(255))
    grade: Mapped[Optional[str]] = mapped_column(String(255))
    size: Mapped[Optional[str]] = mapped_column(String(255))
    weight: Mapped[Optional[str]] = mapped_column(String(255))
    moisture: Mapped[Optional[str]] = mapped_column(String(255))
    packaging: Mapped[Optional[str]] = mapped_column(String(255))
    condition: Mapped[Optional[str]] = mapped_column(String(255))
    aroma: Mapped[Optional[str]] = mapped_column(String(255))
    description: Mapped[Optional[str]] = mapped_column(Text)
    hs_code: Mapped[Optional[str]] = mapped_column(String(255))
    country_of_origin: Mapped[Optional[str]] = mapped_column(String(255))
    quantity: Mapped[Decimal] = mapped_column(Numeric(12, 2))
    unit: Mapped[str] = mapped_column(String(255), default="KG")
    unit_price: Mapped[Decimal] = mapped_column(Numeric(15, 2))
    line_total: Mapped[Decimal] = mapped_column(Numeric(15, 2))
    sort_order: Mapped[int] = mapped_column(Integer, default=0)
