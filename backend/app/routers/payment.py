"""Public customer payment flow, addressed by the invoice's payment token."""

import base64

from fastapi import APIRouter, Depends, File, HTTPException, Response, UploadFile
from sqlalchemy import select
from sqlalchemy.orm import Session

from ..db import get_db, now_local
from ..documents.renderers import invoice_pdf
from ..models import Invoice, PaymentProof
from ..schemas import ChooseMethodIn
from ..serializers import invoice_detail
from .common import file_response, message

router = APIRouter(tags=["payment"])

# Domestic transfer destination shown to customers (was fixed copy in the Blade view).
TRANSFER_ACCOUNT = {
    "account_name": "PT Coffee Nation Prosperity",
    "bank": "BRI",
    "account_number": "115601002957562",
}
MAX_PROOF_BYTES = 5 * 1024 * 1024
ALLOWED_PROOF_TYPES = {
    "image/jpeg": "jpg", "image/png": "png", "application/pdf": "pdf",
}
PAYABLE = ("sent", "pending_confirmation")


def _by_token(db: Session, token: str) -> Invoice:
    invoice = db.scalar(select(Invoice).where(Invoice.payment_token == token))
    if invoice is None:
        raise HTTPException(404, "Invoice tidak ditemukan.")
    return invoice


@router.get("/api/pay/{token}")
def show(token: str, db: Session = Depends(get_db)) -> dict:
    invoice = _by_token(db, token)
    if invoice.status == "cancelled":
        view = "cancelled"
    elif invoice.status == "paid":
        view = "paid"
    elif invoice.is_expired():
        invoice.status = "expired"
        db.commit()
        view = "expired"
    elif invoice.status == "draft":
        raise HTTPException(404, "Invoice tidak ditemukan.")
    else:
        view = "show"
    data = invoice_detail(invoice, include_proofs=False)
    data.pop("payment_token", None)
    return {"view": view, "invoice": data, "transfer_account": TRANSFER_ACCOUNT}


@router.post("/api/pay/{token}/method")
def choose_method(token: str, payload: ChooseMethodIn, db: Session = Depends(get_db)) -> dict:
    invoice = _by_token(db, token)
    if invoice.status not in PAYABLE:
        raise HTTPException(422, "Invoice ini tidak bisa diproses.")
    invoice.payment_method = payload.payment_method
    db.commit()
    return message("Silakan upload bukti transfer Anda.")


@router.post("/api/pay/{token}/reset-method")
def reset_method(token: str, db: Session = Depends(get_db)) -> dict:
    invoice = _by_token(db, token)
    latest = invoice.latest_proof
    if (invoice.status == "pending_confirmation" and invoice.payment_method == "transfer"
            and latest and latest.status == "pending"):
        raise HTTPException(422, "Bukti transfer sedang diverifikasi, tidak bisa ganti metode.")
    if invoice.status not in PAYABLE:
        raise HTTPException(422, "Tidak bisa mengubah metode pembayaran.")
    invoice.payment_method = None
    invoice.status = "sent"
    db.commit()
    return message("Silakan pilih metode pembayaran.")


@router.post("/api/pay/{token}/proof")
async def upload_proof(token: str, proof: UploadFile = File(...), db: Session = Depends(get_db)) -> dict:
    invoice = _by_token(db, token)
    if invoice.status not in PAYABLE:
        raise HTTPException(422, "Invoice ini tidak bisa diproses.")
    if invoice.payment_method not in ("transfer", "qris"):
        raise HTTPException(422, "Metode pembayaran tidak valid.")

    content = await proof.read(MAX_PROOF_BYTES + 1)
    if len(content) > MAX_PROOF_BYTES:
        raise HTTPException(422, "Ukuran file maksimal 5MB.")
    mime = _sniff_mime(content)
    if mime not in ALLOWED_PROOF_TYPES:
        raise HTTPException(422, "File harus berupa JPG, PNG, atau PDF.")

    filename = f"proof_{invoice.invoice_number}_{int(now_local().timestamp())}.{ALLOWED_PROOF_TYPES[mime]}"
    invoice.payment_proofs.append(PaymentProof(
        file_path=f"payment_proofs/{filename}",
        file_name=(proof.filename or filename)[:255],
        file_content=base64.b64encode(content).decode(),
        file_mime=mime,
        status="pending",
    ))
    invoice.status = "pending_confirmation"
    db.commit()
    return message("Bukti transfer berhasil dikirim. Menunggu konfirmasi admin.")


def _sniff_mime(content: bytes) -> str | None:
    """Trust the bytes, not the client-supplied content type."""
    if content.startswith(b"\xff\xd8\xff"):
        return "image/jpeg"
    if content.startswith(b"\x89PNG\r\n\x1a\n"):
        return "image/png"
    if content.startswith(b"%PDF"):
        return "application/pdf"
    return None


@router.get("/api/pay/{token}/invoice.pdf")
@router.get("/pay/{token}/invoice.pdf")
def download_paid_invoice(token: str, db: Session = Depends(get_db)) -> Response:
    invoice = _by_token(db, token)
    if invoice.status != "paid":
        raise HTTPException(403, "Invoice belum lunas.")
    return file_response(invoice_pdf(invoice), f"invoice-lunas-{invoice.invoice_number}.pdf", "application/pdf")
