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

def _png(size: tuple[int, int] = (2400, 1800)) -> bytes:
    """A large, noisy RGBA PNG — the worst case for size, with transparency."""
    import io

    from PIL import Image

    image = Image.frombytes("RGBA", size, os.urandom(size[0] * size[1] * 4))
    buffer = io.BytesIO()
    image.save(buffer, "PNG")
    return buffer.getvalue()


PNG = _png((64, 64))


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


def test_proof_upload_compresses_any_image_and_pdf() -> None:
    from app.documents.renderers import report_pdf
    from app.uploads import UNSUPPORTED_FORMAT, compress_proof

    big = _png((1600, 1200))
    out = compress_proof(big)
    assert out.mime == "image/jpeg" and out.content.startswith(b"\xff\xd8\xff")
    assert len(out.content) < len(big) / 4

    pdf = compress_proof(report_pdf([], 0))
    assert pdf.mime == "application/pdf" and pdf.content.startswith(b"%PDF")

    with pytest.raises(ValueError, match="Format file tidak didukung"):
        compress_proof(b"PK\x03\x04 this is a zip, not a receipt")
    assert "PDF" in UNSUPPORTED_FORMAT


def test_proof_endpoint_rejects_non_image(client: TestClient) -> None:
    r = client.post("/api/invoices", json={
        "customer_name": "Sari", "items": [{"product_name": "X", "quantity": 1, "unit_price": 1000}],
    })
    token = client.get(f"/api/invoices/{r.json()['id']}").json()["payment_token"]
    pay = TestClient(app)
    pay.post(f"/api/pay/{token}/method", json={"payment_method": "transfer"})
    bad = pay.post(f"/api/pay/{token}/proof", files={"proof": ("bukti.docx", b"PK\x03\x04docx", "image/png")})
    assert bad.status_code == 422 and "Format file tidak didukung" in bad.json()["detail"]
    ok = pay.post(f"/api/pay/{token}/proof", files={"proof": ("IMG_0001.heic.png", _png((900, 700)), "image/png")})
    assert ok.status_code == 200, ok.text


def test_overdue_invoice_with_pending_proof_can_be_extended_and_confirmed(client: TestClient) -> None:
    from app.db import SessionLocal
    from app.models import Invoice

    body = {"customer_name": "Dimas", "items": [{"product_name": "Vanilla", "quantity": 1, "unit_price": 175000}]}
    inv_id = client.post("/api/invoices", json=body).json()["id"]
    token = client.get(f"/api/invoices/{inv_id}").json()["payment_token"]
    pay = TestClient(app)
    pay.post(f"/api/pay/{token}/method", json={"payment_method": "transfer"})
    pay.post(f"/api/pay/{token}/proof", files={"proof": ("nota.png", PNG, "image/png")})

    # The admin forgets to confirm and the due date passes.
    with SessionLocal() as db:
        db.get(Invoice, inv_id).due_date = date.today() - timedelta(days=2)
        db.commit()
    assert pay.get(f"/api/pay/{token}").json()["view"] == "show"  # paid already: must not expire
    assert client.get(f"/api/invoices/{inv_id}").json()["status"] == "pending_confirmation"

    # An invoice that did expire (legacy data) reopens when the due date is extended.
    with SessionLocal() as db:
        db.get(Invoice, inv_id).status = "expired"
        db.commit()
    future = (date.today() + timedelta(days=5)).isoformat()
    assert client.put(f"/api/invoices/{inv_id}", json=body | {"due_date": future}).status_code == 200
    assert client.get(f"/api/invoices/{inv_id}").json()["status"] == "pending_confirmation"

    # And the pending proof can still be confirmed even straight from "expired".
    with SessionLocal() as db:
        db.get(Invoice, inv_id).status = "expired"
        db.commit()
    client.post(f"/api/invoices/{inv_id}/confirm-payment", json={"action": "confirm"})
    assert client.get(f"/api/invoices/{inv_id}").json()["status"] == "paid"


def test_shipment_after_payment_flows_into_whatsapp_and_customer_page(client: TestClient) -> None:
    from urllib.parse import unquote

    body = {"customer_name": "Dimas", "customer_phone": "0822277", "items": [{"product_name": "V", "quantity": 1, "unit_price": 1000}]}
    inv_id = client.post("/api/invoices", json=body).json()["id"]
    shipment = {"courier": "jne", "tracking_number": "cgk1234567890"}
    assert client.put(f"/api/invoices/{inv_id}/shipment", json=shipment).status_code == 422  # not paid yet

    client.post(f"/api/invoices/{inv_id}/confirm-payment", json={"action": "confirm"})
    paid_msg = unquote(client.post(f"/api/invoices/{inv_id}/send-notification", json={"channel": "whatsapp"}).json()["redirect_url"])
    assert "telah kami terima" in paid_msg and "sedang kami siapkan" in paid_msg

    assert client.put(f"/api/invoices/{inv_id}/shipment", json=shipment).status_code == 200
    assert client.put(f"/api/invoices/{inv_id}/shipment", json={"courier": "dhl", "tracking_number": "X"}).status_code == 422
    msg = unquote(client.post(f"/api/invoices/{inv_id}/send-notification", json={"channel": "whatsapp"}).json()["redirect_url"])
    inv = client.get(f"/api/invoices/{inv_id}").json()
    assert "CGK1234567890" in msg and "jne.co.id/tracking-package" in msg and inv["payment_url"] in msg
    assert inv["status"] == "paid"

    customer = TestClient(app).get(f"/api/pay/{inv['payment_token']}").json()
    assert customer["view"] == "paid" and customer["invoice"]["shipment"]["courier_name"] == "JNE"
