"""Public customer payment flow, addressed by the invoice's payment token."""

import base64

from fastapi import APIRouter, Depends, File, HTTPException, Response, UploadFile
from sqlalchemy import select
from sqlalchemy.orm import Session
from starlette.concurrency import run_in_threadpool

from ..db import get_db, now_local
from ..documents.renderers import invoice_pdf
from ..models import Invoice, PaymentProof
from ..schemas import ChooseMethodIn
from ..serializers import invoice_detail
from ..uploads import InvalidProof, compress_proof
from .common import file_response, message

router = APIRouter(tags=["payment"])

# Domestic transfer destination shown to customers (was fixed copy in the Blade view).
TRANSFER_ACCOUNT = {
    "account_name": "PT Coffee Nation Prosperity",
    "bank": "BRI",
    "account_number": "115601002957562",
}
# Vercel rejects request bodies above 4.5 MB before they reach the app; the
# browser compresses images first, so only large PDFs/HEICs get near this.
MAX_PROOF_BYTES = 4 * 1024 * 1024
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
        raise HTTPException(422, "Ukuran file maksimal 4MB.")
    try:
        # Type is decided by decoding the bytes, never by the client's filename/MIME.
        compressed = await run_in_threadpool(compress_proof, content)
    except InvalidProof as e:
        raise HTTPException(422, str(e)) from e

    filename = f"proof_{invoice.invoice_number}_{int(now_local().timestamp())}.{compressed.extension}"
    original = (proof.filename or "bukti").rsplit(".", 1)[0][:240]
    invoice.payment_proofs.append(PaymentProof(
        file_path=f"payment_proofs/{filename}",
        file_name=f"{original}.{compressed.extension}",
        file_content=base64.b64encode(compressed.content).decode(),
        file_mime=compressed.mime,
        status="pending",
    ))
    invoice.status = "pending_confirmation"
    db.commit()
    return message("Bukti transfer berhasil dikirim. Menunggu konfirmasi admin.")


@router.get("/api/pay/{token}/invoice.pdf")
@router.get("/pay/{token}/invoice.pdf")
def download_paid_invoice(token: str, db: Session = Depends(get_db)) -> Response:
    invoice = _by_token(db, token)
    if invoice.status != "paid":
        raise HTTPException(403, "Invoice belum lunas.")
    return file_response(invoice_pdf(invoice), f"invoice-lunas-{invoice.invoice_number}.pdf", "application/pdf")
