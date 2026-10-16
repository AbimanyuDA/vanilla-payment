"""Request payloads — the Pydantic equivalent of the Laravel $request->validate() rules."""

from datetime import date
from decimal import Decimal
from typing import Annotated, Any, Literal

from pydantic import BaseModel, ConfigDict, EmailStr, Field, field_validator, model_validator

from .data import COUNTRIES, CURRENCIES, INCOTERMS

Str255 = Annotated[str, Field(max_length=255)]
Money = Annotated[Decimal, Field(ge=0, max_digits=15, decimal_places=2)]


class Payload(BaseModel):
    model_config = ConfigDict(str_strip_whitespace=True, extra="ignore")

    @model_validator(mode="before")
    @classmethod
    def _blank_to_none(cls, data: Any) -> Any:
        if isinstance(data, dict):
            return {k: (None if v == "" else v) for k, v in data.items()}
        return data


class LoginIn(Payload):
    email: EmailStr
    password: str = Field(min_length=1)
    remember: bool = False


# ── Invoices ────────────────────────────────────────────────────────────────


class InvoiceItemIn(Payload):
    product_name: Str255
    description: str | None = None
    quantity: int = Field(ge=1)
    unit_price: Money


class InvoiceExtraIn(Payload):
    label: str | None = Field(default=None, max_length=100)
    amount: Money | None = None


class InvoiceIn(Payload):
    customer_name: Str255
    customer_phone: str | None = Field(default=None, max_length=20)
    customer_email: EmailStr | None = None
    customer_address: str | None = None
    due_date: date | None = None
    notes: str | None = None
    discount: Money | None = None
    items: list[InvoiceItemIn] = Field(min_length=1)
    extras: list[InvoiceExtraIn] = []


class ConfirmPaymentIn(Payload):
    action: Literal["confirm", "reject"]
    admin_notes: str | None = None


class NotificationIn(Payload):
    channel: Literal["whatsapp", "email"]


class ChooseMethodIn(Payload):
    payment_method: Literal["transfer"]


# ── Buyers & products ───────────────────────────────────────────────────────


def _country_code(value: str | None) -> str | None:
    if value is None:
        return None
    code = value.upper()
    if code not in COUNTRIES:
        raise ValueError("Kode negara tidak dikenal.")
    return code


class BuyerIn(Payload):
    company_name: Str255
    contact_person: Str255 | None = None
    email: EmailStr | None = None
    phone: str | None = Field(default=None, max_length=50)
    address: str | None = None
    city: Str255 | None = None
    state_province: Str255 | None = None
    postal_code: str | None = Field(default=None, max_length=50)
    country_code: str | None = None
    tax_vat_number: str | None = Field(default=None, max_length=100)
    notes: str | None = None

    _cc = field_validator("country_code")(_country_code)


class ProductAttributeIn(Payload):
    key: str | None = Field(default=None, max_length=100)
    value: Str255 | None = None


class ProductIn(Payload):
    name: Str255
    species: Str255 | None = None
    category: Literal["raw", "value_added"]
    hs_code: str | None = Field(default=None, max_length=20)
    default_unit: str = Field(max_length=20)
    default_country_of_origin: str = Field(max_length=100)
    is_active: bool = True
    sort_order: int = Field(default=0, ge=0)
    attributes: list[ProductAttributeIn] = []


# ── Quotations ──────────────────────────────────────────────────────────────


class QuotationItemIn(Payload):
    product_id: int | None = None
    product_name: Str255
    species: Str255 | None = None
    grade: Str255 | None = None
    size: str | None = Field(default=None, max_length=100)
    weight: str | None = Field(default=None, max_length=100)
    moisture: str | None = Field(default=None, max_length=100)
    packaging: Str255 | None = None
    condition: Str255 | None = None
    aroma: Str255 | None = None
    description: str | None = None
    hs_code: str | None = Field(default=None, max_length=20)
    country_of_origin: str | None = Field(default=None, max_length=100)
    quantity: Decimal = Field(ge=Decimal("0.01"), max_digits=12, decimal_places=2)
    unit: str | None = Field(default=None, max_length=20)
    unit_price: Money


class QuotationIn(Payload):
    buyer_id: int | None = None
    buyer_company_name: Str255
    buyer_contact_person: Str255 | None = None
    buyer_email: EmailStr | None = None
    buyer_phone: str | None = Field(default=None, max_length=50)
    buyer_address: str | None = None
    buyer_city: Str255 | None = None
    buyer_state_province: Str255 | None = None
    buyer_postal_code: str | None = Field(default=None, max_length=50)
    buyer_country_code: str | None = None
    buyer_tax_vat_number: str | None = Field(default=None, max_length=100)
    quotation_date: date
    validity_days: int = Field(ge=1, le=365)
    currency: str = Field(min_length=3, max_length=3)
    incoterm: str
    incoterm_place: Str255 | None = None
    destination_country_code: str | None = None
    port_of_loading: Str255 | None = None
    port_of_discharge: Str255 | None = None
    final_destination: Str255 | None = None
    shipping_method: str | None = Field(default=None, max_length=100)
    production_lead_time: Str255 | None = None
    estimated_shipment: Str255 | None = None
    payment_terms: str | None = None
    moq: str | None = Field(default=None, max_length=100)
    country_of_origin: str | None = Field(default=None, max_length=100)
    discount_amount: Money | None = None
    freight_amount: Money | None = None
    insurance_amount: Money | None = None
    other_charges_label: str | None = Field(default=None, max_length=100)
    other_charges_amount: Money | None = None
    tax_label: str | None = Field(default=None, max_length=100)
    tax_rate: Decimal | None = Field(default=None, ge=0, le=100)
    tax_amount: Money | None = None
    notes: str | None = None
    items: list[QuotationItemIn] = Field(min_length=1)

    _cc = field_validator("buyer_country_code", "destination_country_code")(_country_code)

    @field_validator("currency")
    @classmethod
    def _currency(cls, v: str) -> str:
        if v.upper() not in CURRENCIES:
            raise ValueError("Mata uang tidak didukung.")
        return v.upper()

    @field_validator("incoterm")
    @classmethod
    def _incoterm(cls, v: str) -> str:
        if v not in INCOTERMS:
            raise ValueError("Incoterm tidak valid.")
        return v


class QuotationStatusIn(Payload):
    status: Literal["draft", "sent", "accepted", "rejected", "expired", "cancelled"]


# ── Company settings ────────────────────────────────────────────────────────


class CompanySettingsIn(Payload):
    company_name: Str255
    address: str | None = None
    country: str = Field(max_length=100)
    email: EmailStr | None = None
    phone: str | None = Field(default=None, max_length=50)
    website: Str255 | None = None
    bank_name: Str255 | None = None
    bank_branch: Str255 | None = None
    bank_account_name: Str255 | None = None
    bank_account_number: str | None = Field(default=None, max_length=100)
    bank_account_currency: str | None = Field(default=None, max_length=50)
    swift_bic: str | None = Field(default=None, max_length=11)
    bank_address: str | None = None
    intermediary_bank_name: Str255 | None = None
    intermediary_bank_swift: str | None = Field(default=None, max_length=11)
    intermediary_bank_account: str | None = Field(default=None, max_length=100)
    payment_charge_instruction: Literal["OUR", "SHA", "BEN"] | None = None
    authorized_person_name: Str255 | None = None
    authorized_person_position: Str255 | None = None
    default_terms_conditions: str | None = None
