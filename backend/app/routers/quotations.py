from fastapi import APIRouter, Depends, HTTPException, Response
from sqlalchemy import or_, select
from sqlalchemy.orm import Session, selectinload

from ..data import COUNTRIES, CURRENCIES, INCOTERMS
from ..db import get_db
from ..documents.renderers import quotation_pdf
from ..models import Admin, Quotation
from ..schemas import QuotationIn, QuotationStatusIn
from ..security import current_admin
from ..serializers import QUOTATION_STATUS_LABELS, quotation_detail, quotation_summary
from ..services import BusinessRuleError, current_company_settings, refresh_pdf_snapshot, save_quotation
from .common import file_response, get_or_404, like, message, paginate

router = APIRouter(prefix="/api", tags=["quotations"], dependencies=[Depends(current_admin)])


def _quotation(db: Session, quotation_id: int) -> Quotation:
    return get_or_404(db, Quotation, quotation_id, "Quotation tidak ditemukan.")


@router.get("/meta/trade")
def trade_options() -> dict:
    return {
        "countries": [{"code": c, "name": n} for c, n in COUNTRIES.items()],
        "currencies": [{"code": c} | v for c, v in CURRENCIES.items()],
        "incoterms": INCOTERMS,
        "quotation_statuses": QUOTATION_STATUS_LABELS,
    }


@router.get("/quotations")
def list_quotations(status: str | None = None, search: str | None = None, page: int = 1,
                    db: Session = Depends(get_db)) -> dict:
    stmt = (select(Quotation).options(selectinload(Quotation.creator))
            .order_by(Quotation.created_at.desc()))
    if status:
        stmt = stmt.where(Quotation.status == status)
    if search:
        pattern = like(search)
        stmt = stmt.where(or_(
            Quotation.quotation_number.ilike(pattern, escape="\\"),
            Quotation.buyer_company_name.ilike(pattern, escape="\\"),
        ))
    return paginate(db, stmt, page, 15, quotation_summary)


@router.post("/quotations", status_code=201)
def store_quotation(payload: QuotationIn, admin: Admin = Depends(current_admin),
                    db: Session = Depends(get_db)) -> dict:
    try:
        quotation = save_quotation(db, None, payload, admin.id)
    except BusinessRuleError as e:
        raise HTTPException(422, str(e)) from e
    return message("Quotation berhasil dibuat.", id=quotation.id)


@router.get("/quotations/{quotation_id}")
def show_quotation(quotation_id: int, db: Session = Depends(get_db)) -> dict:
    return quotation_detail(_quotation(db, quotation_id))


@router.put("/quotations/{quotation_id}")
def update_quotation(quotation_id: int, payload: QuotationIn, admin: Admin = Depends(current_admin),
                     db: Session = Depends(get_db)) -> dict:
    try:
        save_quotation(db, _quotation(db, quotation_id), payload, admin.id)
    except BusinessRuleError as e:
        raise HTTPException(422, str(e)) from e
    return message("Quotation berhasil diperbarui.")


@router.delete("/quotations/{quotation_id}")
def delete_quotation(quotation_id: int, db: Session = Depends(get_db)) -> dict:
    quotation = _quotation(db, quotation_id)
    if quotation.status in ("accepted", "converted_to_proforma"):
        raise HTTPException(422, "Quotation yang sudah diterima/dikonversi tidak bisa dihapus.")
    db.delete(quotation)
    db.commit()
    return message("Quotation berhasil dihapus.")


@router.post("/quotations/{quotation_id}/status")
def update_status(quotation_id: int, payload: QuotationStatusIn, db: Session = Depends(get_db)) -> dict:
    quotation = _quotation(db, quotation_id)
    quotation.status = payload.status
    db.commit()
    return message(f"Status quotation diubah menjadi {QUOTATION_STATUS_LABELS[payload.status]}.")


@router.get("/quotations/{quotation_id}/pdf")
def download_pdf(quotation_id: int, db: Session = Depends(get_db)) -> Response:
    quotation = _quotation(db, quotation_id)
    refresh_pdf_snapshot(db, quotation)
    content = quotation_pdf(quotation, current_company_settings(db))
    return file_response(content, f"{quotation.quotation_number}.pdf", "application/pdf")


@router.get("/quotations/{quotation_id}/pdf-preview")
def preview_pdf(quotation_id: int, db: Session = Depends(get_db)) -> Response:
    # Previewing is not issuing: the snapshot is deliberately left untouched.
    quotation = _quotation(db, quotation_id)
    content = quotation_pdf(quotation, current_company_settings(db))
    return file_response(content, f"{quotation.quotation_number}.pdf", "application/pdf", inline=True)
