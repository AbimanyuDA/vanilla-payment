"""Local setup helpers.

    python -m app.cli init-db        # create tables (fresh/local DB only)
    python -m app.cli seed           # default admin + company settings, if missing
"""

import sys

from sqlalchemy import select

from .db import Base, SessionLocal, engine
from .models import Admin, CompanySettings
from .security import hash_password

DEFAULT_TERMS = "\n\n".join([
    "1. Price & Currency: Prices quoted are in the currency stated on this quotation and are subject to change without prior notice unless otherwise agreed in writing.",
    "2. Incoterms: Trade terms follow Incoterms 2020 as specified on this quotation.",
    "3. Payment Terms: As specified on this quotation. Payment must be made in accordance with the agreed schedule.",
    "4. Quotation Validity: This quotation is valid until the date stated above.",
    "5. Lead Time: Production and shipment lead times are estimates and may vary depending on order volume and logistics conditions.",
    "6. Packaging: Products are packed as specified per item unless otherwise agreed.",
    "7. Country of Origin: Indonesia, unless otherwise stated.",
])


def seed() -> None:
    with SessionLocal() as db:
        if db.scalar(select(Admin).limit(1)) is None:
            db.add(Admin(name="Admin Vanilla Royal", email="admin@vanillaroyal.com",
                         password=hash_password("admin123")))
        if db.scalar(select(CompanySettings).limit(1)) is None:
            db.add(CompanySettings(
                company_name="Vanilla Royal (PT Coffee Nation Prosperity)",
                logo_path="images/logo/logo.webp",
                address="Jl. Raya Bukit Kweni No 40, Sukodono, Kab. Sidoarjo, Jawa Timur, Indonesia",
                country="Indonesia", email="info@vanillaroyal.id", phone="+62 858-5366-9568",
                website="www.vanillaroyal.id", bank_name="Bank Rakyat Indonesia (BRI)",
                swift_bic="BRINIDJA", bank_account_name="PT COFFEE NATION PROSPERITY",
                bank_account_number="115601002957562", bank_account_currency="USD / IDR",
                payment_charge_instruction="OUR", default_terms_conditions=DEFAULT_TERMS,
            ))
        db.commit()


if __name__ == "__main__":
    command = sys.argv[1] if len(sys.argv) > 1 else ""
    if command == "init-db":
        Base.metadata.create_all(engine)
    elif command == "seed":
        seed()
    else:
        sys.exit(__doc__)
