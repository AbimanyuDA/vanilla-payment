"""Buyers, products and company settings."""

import base64

from fastapi import APIRouter, Depends, File, HTTPException, Response, UploadFile
from sqlalchemy import exists, func, or_, select
from sqlalchemy.orm import Session, selectinload

from ..data import COUNTRIES
from ..db import get_db
from ..models import Admin, Buyer, Product, Quotation, QuotationItem
from ..schemas import BuyerIn, CompanySettingsIn, ProductIn
from ..security import current_admin
from ..serializers import buyer_out, product_out, settings_out
from ..services import current_company_settings, save_product
from .common import get_or_404, like, message, paginate

router = APIRouter(prefix="/api", tags=["master"], dependencies=[Depends(current_admin)])

MAX_LOGO_BYTES = 2 * 1024 * 1024
LOGO_SIGNATURES = {b"\x89PNG": "image/png", b"\xff\xd8\xff": "image/jpeg", b"RIFF": "image/webp"}


# ── Buyers ──────────────────────────────────────────────────────────────────


@router.get("/buyers")
def list_buyers(search: str | None = None, page: int = 1, db: Session = Depends(get_db)) -> dict:
    count = select(func.count()).where(Quotation.buyer_id == Buyer.id).scalar_subquery()
    stmt = select(Buyer, count.label("quotations_count")).order_by(Buyer.created_at.desc())
    if search:
        pattern = like(search)
        stmt = stmt.where(or_(
            Buyer.company_name.ilike(pattern, escape="\\"),
            Buyer.contact_person.ilike(pattern, escape="\\"),
            Buyer.country.ilike(pattern, escape="\\"),
        ))
    total = db.scalar(select(func.count()).select_from(stmt.order_by(None).subquery())) or 0
    page = max(page, 1)
    rows = db.execute(stmt.limit(15).offset((page - 1) * 15)).all()
    return {
        "data": [buyer_out(b) | {"quotations_count": n} for b, n in rows],
        "meta": {"page": page, "per_page": 15, "total": total, "pages": max(-(-total // 15), 1)},
    }


@router.get("/buyers/search")
def search_buyers(q: str = "", db: Session = Depends(get_db)) -> list[dict]:
    stmt = select(Buyer).order_by(Buyer.company_name).limit(20)
    if q.strip():
        stmt = stmt.where(Buyer.company_name.ilike(like(q.strip()), escape="\\"))
    return [buyer_out(b) for b in db.scalars(stmt).all()]


def _apply_buyer(buyer: Buyer, payload: BuyerIn) -> None:
    for key, value in payload.model_dump().items():
        setattr(buyer, key, value)
    buyer.country = COUNTRIES.get(payload.country_code or "")


@router.post("/buyers", status_code=201)
def store_buyer(payload: BuyerIn, admin: Admin = Depends(current_admin), db: Session = Depends(get_db)) -> dict:
    buyer = Buyer(created_by=admin.id)
    _apply_buyer(buyer, payload)
    db.add(buyer)
    db.commit()
    return message("Buyer berhasil ditambahkan.", id=buyer.id)


@router.get("/buyers/{buyer_id}")
def show_buyer(buyer_id: int, db: Session = Depends(get_db)) -> dict:
    return buyer_out(get_or_404(db, Buyer, buyer_id))


@router.put("/buyers/{buyer_id}")
def update_buyer(buyer_id: int, payload: BuyerIn, db: Session = Depends(get_db)) -> dict:
    _apply_buyer(get_or_404(db, Buyer, buyer_id), payload)
    db.commit()
    return message("Buyer berhasil diperbarui.")


@router.delete("/buyers/{buyer_id}")
def delete_buyer(buyer_id: int, db: Session = Depends(get_db)) -> dict:
    buyer = get_or_404(db, Buyer, buyer_id)
    if db.scalar(select(exists().where(Quotation.buyer_id == buyer.id))):
        raise HTTPException(422, "Buyer tidak bisa dihapus karena memiliki riwayat quotation.")
    db.delete(buyer)
    db.commit()
    return message("Buyer berhasil dihapus.")


# ── Products ────────────────────────────────────────────────────────────────


@router.get("/products")
def list_products(search: str | None = None, category: str | None = None, page: int = 1,
                  db: Session = Depends(get_db)) -> dict:
    stmt = (select(Product).options(selectinload(Product.product_attributes))
            .order_by(Product.sort_order, Product.name))
    if search:
        stmt = stmt.where(Product.name.ilike(like(search), escape="\\"))
    if category:
        stmt = stmt.where(Product.category == category)
    return paginate(db, stmt, page, 20, product_out)


@router.get("/products/search")
def search_products(q: str = "", db: Session = Depends(get_db)) -> list[dict]:
    stmt = (select(Product).options(selectinload(Product.product_attributes))
            .where(Product.is_active.is_(True)).order_by(Product.sort_order, Product.name).limit(20))
    if q.strip():
        stmt = stmt.where(Product.name.ilike(like(q.strip()), escape="\\"))
    return [product_out(p) for p in db.scalars(stmt).all()]


@router.post("/products", status_code=201)
def store_product(payload: ProductIn, db: Session = Depends(get_db)) -> dict:
    product = save_product(db, None, payload)
    return message("Produk berhasil ditambahkan.", id=product.id)


@router.get("/products/{product_id}")
def show_product(product_id: int, db: Session = Depends(get_db)) -> dict:
    return product_out(get_or_404(db, Product, product_id))


@router.put("/products/{product_id}")
def update_product(product_id: int, payload: ProductIn, db: Session = Depends(get_db)) -> dict:
    save_product(db, get_or_404(db, Product, product_id), payload)
    return message("Produk berhasil diperbarui.")


@router.delete("/products/{product_id}")
def delete_product(product_id: int, db: Session = Depends(get_db)) -> dict:
    product = get_or_404(db, Product, product_id)
    if db.scalar(select(exists().where(QuotationItem.product_id == product.id))):
        raise HTTPException(
            422, "Produk tidak bisa dihapus karena sudah dipakai di quotation. Nonaktifkan saja produk ini."
        )
    db.delete(product)
    db.commit()
    return message("Produk berhasil dihapus.")


# ── Company settings ────────────────────────────────────────────────────────


@router.get("/settings/company")
def get_company(db: Session = Depends(get_db)) -> dict:
    return settings_out(current_company_settings(db))


@router.put("/settings/company")
def update_company(payload: CompanySettingsIn, db: Session = Depends(get_db)) -> dict:
    settings = current_company_settings(db)
    for key, value in payload.model_dump().items():
        setattr(settings, key, value)
    db.commit()
    return message("Pengaturan perusahaan berhasil disimpan.")


@router.post("/settings/company/logo")
async def upload_logo(logo: UploadFile = File(...), db: Session = Depends(get_db)) -> dict:
    content = await logo.read(MAX_LOGO_BYTES + 1)
    if len(content) > MAX_LOGO_BYTES:
        raise HTTPException(422, "Ukuran logo maksimal 2MB.")
    mime = next((m for sig, m in LOGO_SIGNATURES.items() if content.startswith(sig)), None)
    if mime is None:
        raise HTTPException(422, "Logo harus berupa PNG, JPG, atau WebP.")
    settings = current_company_settings(db)
    settings.logo_data = f"data:{mime};base64,{base64.b64encode(content).decode()}"
    settings.logo_path = "uploaded"
    db.commit()
    return message("Logo berhasil diperbarui.")


@router.get("/settings/company/logo")
def company_logo(db: Session = Depends(get_db)) -> Response:
    settings = current_company_settings(db)
    if not settings.logo_data:
        raise HTTPException(404, "Belum ada logo yang diupload.")
    header, encoded = settings.logo_data.split(",", 1)
    return Response(base64.b64decode(encoded), media_type=header[5:].split(";")[0])
