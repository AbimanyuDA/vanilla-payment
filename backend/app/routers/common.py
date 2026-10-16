import math
from collections.abc import Callable
from typing import Any, TypeVar
from urllib.parse import quote

from fastapi import HTTPException, Response
from sqlalchemy import Select, func, select
from sqlalchemy.orm import Session

T = TypeVar("T")


def get_or_404(db: Session, model: type[T], obj_id: int, message: str = "Data tidak ditemukan.") -> T:
    obj = db.get(model, obj_id)
    if obj is None:
        raise HTTPException(404, message)
    return obj


def paginate(
    db: Session, stmt: Select, page: int, per_page: int, serialize: Callable[[Any], dict]
) -> dict[str, Any]:
    total = db.scalar(select(func.count()).select_from(stmt.order_by(None).subquery())) or 0
    page = max(page, 1)
    rows = db.scalars(stmt.limit(per_page).offset((page - 1) * per_page)).all()
    return {
        "data": [serialize(r) for r in rows],
        "meta": {
            "page": page,
            "per_page": per_page,
            "total": total,
            "pages": max(math.ceil(total / per_page), 1),
        },
    }


def like(term: str) -> str:
    escaped = term.replace("\\", "\\\\").replace("%", "\\%").replace("_", "\\_")
    return f"%{escaped}%"


def file_response(content: bytes, filename: str, media_type: str, inline: bool = False) -> Response:
    disposition = "inline" if inline else "attachment"
    return Response(
        content,
        media_type=media_type,
        headers={"Content-Disposition": f"{disposition}; filename*=UTF-8''{quote(filename)}"},
    )


def message(text: str, **extra: Any) -> dict[str, Any]:
    return {"message": text, **extra}
