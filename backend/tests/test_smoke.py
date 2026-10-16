"""End-to-end smoke test of the core flows against a throwaway SQLite DB."""

import os
import sys
import tempfile
from datetime import date, timedelta
from pathlib import Path

_tmp = tempfile.mkdtemp()
os.environ["DATABASE_URL"] = f"sqlite:///{_tmp}/test.db"
os.environ["SECRET_KEY"] = "test-secret"
os.environ["APP_URL"] = "http://testserver"
sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

import pytest  # noqa: E402
from fastapi.testclient import TestClient  # noqa: E402

from app.cli import seed  # noqa: E402
from app.db import Base, engine  # noqa: E402
from app.main import app  # noqa: E402

PNG = b"\x89PNG\r\n\x1a\n" + b"0" * 64


@pytest.fixture(scope="module")
def client() -> TestClient:
    Base.metadata.create_all(engine)
    seed()
    c = TestClient(app)
    r = c.post("/api/auth/login", json={"email": "admin@vanillaroyal.com", "password": "admin123"})
    assert r.status_code == 200, r.text
    return c


def test_unauthenticated_is_rejected() -> None:
    assert TestClient(app).get("/api/invoices").status_code == 401


def test_invoice_payment_flow(client: TestClient) -> None:
    r = client.post("/api/invoices", json={
        "customer_name": "Budi", "customer_phone": "0812345", "discount": 1000,
        "due_date": (date.today() + timedelta(days=7)).isoformat(),
        "items": [{"product_name": "Vanilla A", "quantity": 2, "unit_price": 50000}],
        "extras": [{"label": "Ongkir", "amount": 15000}, {"label": "", "amount": None}],
    })
    assert r.status_code == 201, r.text
    inv = client.get(f"/api/invoices/{r.json()['id']}").json()
    assert inv["invoice_number"].startswith("INV-") and inv["status"] == "sent"
    assert inv["total_amount"] == 100000 - 1000 + 15000
    token = inv["payment_token"]

    pay = TestClient(app)  # customer, no session
    assert pay.get(f"/api/pay/{token}").json()["view"] == "show"
    assert pay.post(f"/api/pay/{token}/method", json={"payment_method": "transfer"}).status_code == 200
    r = pay.post(f"/api/pay/{token}/proof", files={"proof": ("bukti.png", PNG, "image/png")})
    assert r.status_code == 200, r.text
    assert pay.post(f"/api/pay/{token}/reset-method").status_code == 422  # proof under review
    assert pay.get(f"/pay/{token}/invoice.pdf").status_code == 403  # not paid yet

    r = client.post(f"/api/invoices/{inv['id']}/confirm-payment", json={"action": "confirm"})
    assert r.status_code == 200
    assert pay.get(f"/api/pay/{token}").json()["view"] == "paid"
    pdf = pay.get(f"/pay/{token}/invoice.pdf")
    assert pdf.status_code == 200 and pdf.content.startswith(b"%PDF")
    assert client.delete(f"/api/invoices/{inv['id']}").status_code == 422

    wa = client.post(f"/api/invoices/{inv['id']}/send-notification", json={"channel": "whatsapp"})
    assert wa.json()["redirect_url"].startswith("https://wa.me/62812345")
    assert client.get("/api/dashboard").json()["stats"]["paid"] == 1
    assert client.get("/api/reports/export-excel").status_code == 200
    assert client.get("/api/reports/export-pdf").content.startswith(b"%PDF")


def test_quotation_numbering_totals_and_pdf(client: TestClient) -> None:
    base = {
        "buyer_company_name": "Acme GmbH", "quotation_date": date.today().isoformat(),
        "validity_days": 14, "currency": "EUR", "incoterm": "CIF",
        "freight_amount": 100, "insurance_amount": 20, "discount_amount": 10,
        "items": [{"product_name": "Vanilla Planifolia • Gourmet", "quantity": 1.5, "unit_price": 200,
                   "grade": "A", "hs_code": "090510"}],
    }
    r = client.post("/api/quotations", json=base)
    assert r.status_code == 201, r.text
    q = client.get(f"/api/quotations/{r.json()['id']}").json()
    assert "-XXX-" in q["quotation_number"]
    assert q["grand_total"] == 300 - 10 + 100 + 20
    assert q["valid_until"] == (date.today() + timedelta(days=14)).isoformat()

    r = client.put(f"/api/quotations/{q['id']}", json=base | {"destination_country_code": "deu"})
    assert r.status_code == 200, r.text
    q = client.get(f"/api/quotations/{q['id']}").json()
    assert "-DEU-" in q["quotation_number"] and q["destination_country"] == "Germany"

    assert client.get(f"/api/quotations/{q['id']}/pdf-preview").content.startswith(b"%PDF")
    assert client.get(f"/api/quotations/{q['id']}").json()["pdf_generated_at"] is None
    assert client.get(f"/api/quotations/{q['id']}/pdf").status_code == 200
    assert client.get(f"/api/quotations/{q['id']}").json()["pdf_generated_at"] is not None

    bad = client.post("/api/quotations", json=base | {"incoterm": "XYZ"})
    assert bad.status_code == 422


def test_master_data(client: TestClient) -> None:
    b = client.post("/api/buyers", json={"company_name": "Kyoto Foods", "country_code": "JPN"})
    assert b.status_code == 201
    assert client.get("/api/buyers/search?q=kyo").json()[0]["country"] == "Japan"
    p = client.post("/api/products", json={
        "name": "Vanilla Tahitensis", "category": "raw", "default_unit": "KG",
        "default_country_of_origin": "Indonesia",
        "attributes": [{"key": "grade", "value": "Gourmet"}, {"key": "", "value": ""}],
    })
    assert p.status_code == 201
    assert client.get("/api/products/search?q=tahi").json()[0]["attributes"] == {"grade": "Gourmet"}
    assert client.post("/api/settings/company/logo", files={"logo": ("l.png", PNG, "image/png")}).status_code == 200
