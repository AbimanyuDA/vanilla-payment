import re
from datetime import date
from urllib.parse import quote

from fastapi import APIRouter, Depends, HTTPException, Query, Response
from sqlalchemy import func, or_, select
from sqlalchemy.orm import Session, selectinload

from ..config import get_settings
from ..db import get_db, today_local
from ..documents.pdf_base import rupiah
from ..documents.renderers import invoice_pdf, report_excel, report_pdf
from ..mailer import send_invoice_email
from ..models import Admin, Invoice
from ..schemas import ConfirmPaymentIn, InvoiceIn, NotificationIn
from ..security import current_admin
from ..serializers import invoice_detail, invoice_summary
from ..services import BusinessRuleError, confirm_payment, create_invoice, update_invoice
from .common import file_response, get_or_404, like, message, paginate

router = APIRouter(prefix="/api", tags=["invoices"], dependencies=[Depends(current_admin)])


def _invoice(db: Session, invoice_id: int) -> Invoice:
    return get_or_404(db, Invoice, invoice_id, "Invoice tidak ditemukan.")


def payment_url(invoice: Invoice) -> str:
    return f"{get_settings().app_url}/pay/{invoice.payment_token}"


@router.get("/dashboard")
def dashboard(db: Session = Depends(get_db)) -> dict:
    counts = dict(db.execute(select(Invoice.status, func.count()).group_by(Invoice.status)).all())
    revenue = db.scalar(select(func.sum(Invoice.total_amount)).where(Invoice.status == "paid")) or 0
    recent = db.scalars(
        select(Invoice).options(selectinload(Invoice.creator))
        .order_by(Invoice.created_at.desc()).limit(10)
    ).all()
    pending = db.scalars(
        select(Invoice).options(selectinload(Invoice.payment_proofs))
        .where(Invoice.status == "pending_confirmation")
        .order_by(Invoice.created_at.desc()).limit(5)
    ).all()
    return {
        "stats": {
            "total": sum(counts.values()),
            "draft": counts.get("draft", 0),
            "sent": counts.get("sent", 0),
            "pending": counts.get("pending_confirmation", 0),
            "paid": counts.get("paid", 0),
            "total_revenue": float(revenue),
        },
        "recent_invoices": [invoice_summary(i) for i in recent],
        "pending_proofs": [
            invoice_summary(i) | {"proof_uploaded_at": i.latest_proof.created_at.isoformat()
                                  if i.latest_proof and i.latest_proof.created_at else None}
            for i in pending
        ],
    }


@router.get("/invoices")
def list_invoices(
    status: str | None = None,
    search: str | None = None,
    page: int = 1,
    db: Session = Depends(get_db),
) -> dict:
    stmt = select(Invoice).options(selectinload(Invoice.creator)).order_by(Invoice.created_at.desc())
    if status:
        stmt = stmt.where(Invoice.status == status)
    if search:
        pattern = like(search)
        stmt = stmt.where(or_(
            Invoice.invoice_number.ilike(pattern, escape="\\"),
            Invoice.customer_name.ilike(pattern, escape="\\"),
            Invoice.customer_phone.ilike(pattern, escape="\\"),
        ))
    return paginate(db, stmt, page, 15, invoice_summary)


@router.post("/invoices", status_code=201)
def store_invoice(payload: InvoiceIn, admin: Admin = Depends(current_admin),
                  db: Session = Depends(get_db)) -> dict:
    try:
        invoice = create_invoice(db, payload, admin.id, today_local())
    except BusinessRuleError as e:
        raise HTTPException(422, str(e)) from e
    return message("Invoice berhasil dibuat.", id=invoice.id)


@router.get("/invoices/{invoice_id}")
def show_invoice(invoice_id: int, db: Session = Depends(get_db)) -> dict:
    invoice = _invoice(db, invoice_id)
    return invoice_detail(invoice) | {"payment_url": payment_url(invoice)}


@router.put("/invoices/{invoice_id}")
def edit_invoice(invoice_id: int, payload: InvoiceIn, db: Session = Depends(get_db)) -> dict:
    try:
        update_invoice(db, _invoice(db, invoice_id), payload, today_local())
    except BusinessRuleError as e:
        raise HTTPException(422, str(e)) from e
    return message("Invoice berhasil diperbarui.")


@router.delete("/invoices/{invoice_id}")
def delete_invoice(invoice_id: int, db: Session = Depends(get_db)) -> dict:
    invoice = _invoice(db, invoice_id)
    if invoice.status == "paid":
        raise HTTPException(422, "Invoice yang sudah lunas tidak bisa dihapus.")
    db.delete(invoice)
    db.commit()
    return message("Invoice berhasil dihapus.")


@router.post("/invoices/{invoice_id}/mark-sent")
def mark_sent(invoice_id: int, db: Session = Depends(get_db)) -> dict:
    _invoice(db, invoice_id).status = "sent"
    db.commit()
    return message("Status invoice diubah menjadi Terkirim.")


@router.post("/invoices/{invoice_id}/confirm-payment")
def confirm(invoice_id: int, payload: ConfirmPaymentIn, admin: Admin = Depends(current_admin),
            db: Session = Depends(get_db)) -> dict:
    text = confirm_payment(db, _invoice(db, invoice_id), payload.action, payload.admin_notes, admin.id)
    return message(text)


@router.post("/invoices/{invoice_id}/cancel")
def cancel(invoice_id: int, db: Session = Depends(get_db)) -> dict:
    invoice = _invoice(db, invoice_id)
    if invoice.status == "paid":
        raise HTTPException(422, "Invoice yang sudah lunas tidak bisa dibatalkan.")
    invoice.status = "cancelled"
    db.commit()
    return message("Invoice berhasil dibatalkan.")


def _whatsapp_url(invoice: Invoice) -> str:
    phone = re.sub(r"\D", "", invoice.customer_phone or "")
    if phone.startswith("0"):
        phone = "62" + phone[1:]
    due = f"Jatuh Tempo: *{invoice.due_date:%d/%m/%Y}*\n\n" if invoice.due_date else "\n"
    text = (
        f"Halo *{invoice.customer_name}*,\n\n"
        "Berikut invoice pembayaran Anda dari *Vanilla Royal*:\n\n"
        f"No. Invoice: *{invoice.invoice_number}*\n"
        f"Total: *{rupiah(invoice.total_amount)}*\n"
        f"{due}"
        f"Klik link berikut untuk memilih metode pembayaran:\n{payment_url(invoice)}\n\n"
        "Terima kasih 🙏"
    )
    return f"https://wa.me/{phone}?text={quote(text)}"


@router.post("/invoices/{invoice_id}/send-notification")
def send_notification(invoice_id: int, payload: NotificationIn, db: Session = Depends(get_db)) -> dict:
    invoice = _invoice(db, invoice_id)
    # Re-sending a link must never downgrade a settled or in-review invoice.
    keeps_status = invoice.status in ("paid", "pending_confirmation", "cancelled")
    if payload.channel == "whatsapp":
        if not keeps_status:
            invoice.status = "sent"
        db.commit()
        return message("Membuka WhatsApp…", redirect_url=_whatsapp_url(invoice))
    if not invoice.customer_email:
        raise HTTPException(422, "Email customer tidak tersedia.")
    try:
        send_invoice_email(invoice, payment_url(invoice))
    except OSError as e:
        raise HTTPException(502, "Gagal mengirim email. Periksa konfigurasi mail.") from e
    if not keeps_status:
        invoice.status = "sent"
    db.commit()
    return message("Email invoice berhasil dikirim.")


@router.get("/invoices/{invoice_id}/pdf")
def download_pdf(invoice_id: int, db: Session = Depends(get_db)) -> Response:
    invoice = _invoice(db, invoice_id)
    return file_response(invoice_pdf(invoice), f"invoice-{invoice.invoice_number}.pdf", "application/pdf")


# ── Reports ─────────────────────────────────────────────────────────────────


def _report_rows(db: Session, status: str | None, date_from: date | None, date_to: date | None) -> list[Invoice]:
    stmt = select(Invoice).options(selectinload(Invoice.creator)).order_by(Invoice.created_at.desc())
    if status:
        stmt = stmt.where(Invoice.status == status)
    if date_from:
        stmt = stmt.where(func.date(Invoice.created_at) >= date_from)
    if date_to:
        stmt = stmt.where(func.date(Invoice.created_at) <= date_to)
    return list(db.scalars(stmt).all())


def _revenue(invoices: list[Invoice]) -> float:
    return float(sum(i.total_amount for i in invoices if i.status == "paid"))


@router.get("/reports")
def report(status: str | None = None, date_from: date | None = Query(None, alias="from"),
           date_to: date | None = Query(None, alias="to"), db: Session = Depends(get_db)) -> dict:
    rows = _report_rows(db, status, date_from, date_to)
    return {"invoices": [invoice_summary(i) for i in rows], "total_revenue": _revenue(rows)}


@router.get("/reports/export-pdf")
def report_export_pdf(status: str | None = None, date_from: date | None = Query(None, alias="from"),
                      date_to: date | None = Query(None, alias="to"), db: Session = Depends(get_db)) -> Response:
    rows = _report_rows(db, status, date_from, date_to)
    return file_response(report_pdf(rows, _revenue(rows)),
                         f"laporan-invoice-{today_local():%Y%m%d}.pdf", "application/pdf")


@router.get("/reports/export-excel")
def report_export_excel(status: str | None = None, date_from: date | None = Query(None, alias="from"),
                        date_to: date | None = Query(None, alias="to"), db: Session = Depends(get_db)) -> Response:
    rows = _report_rows(db, status, date_from, date_to)
    return file_response(
        report_excel(rows), f"laporan-invoice-{today_local():%Y%m%d}.xlsx",
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    )
