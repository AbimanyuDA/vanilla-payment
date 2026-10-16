import logging
from collections.abc import AsyncIterator
from contextlib import asynccontextmanager

from fastapi import FastAPI, Request
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse
from sqlalchemy import inspect, text
from starlette.middleware.sessions import SessionMiddleware

from .config import get_settings
from .db import engine
from .routers import auth, invoices, master, payment, quotations
from .security import REMEMBER_SECONDS

logger = logging.getLogger(__name__)
settings = get_settings()


def ensure_schema() -> None:
    """Additive, idempotent schema patches on top of the Laravel tables."""
    try:
        columns = {c["name"] for c in inspect(engine).get_columns("company_settings")}
        if "logo_data" not in columns:
            with engine.begin() as conn:
                conn.execute(text("ALTER TABLE company_settings ADD COLUMN logo_data TEXT"))
    except Exception:  # never block boot; the settings page will surface the error
        logger.exception("ensure_schema failed")


@asynccontextmanager
async def lifespan(_: FastAPI) -> AsyncIterator[None]:
    ensure_schema()
    yield


app = FastAPI(
    title="Vanilla Royal Payment API",
    lifespan=lifespan,
    docs_url="/api/docs" if settings.debug else None,
    redoc_url=None,
    openapi_url="/api/openapi.json" if settings.debug else None,
)
app.add_middleware(
    SessionMiddleware,
    secret_key=settings.secret_key,
    session_cookie="vr_admin_session",
    max_age=REMEMBER_SECONDS,
    same_site="lax",
    https_only=settings.app_url.startswith("https"),
)


@app.middleware("http")
async def security_headers(request: Request, call_next):  # type: ignore[no-untyped-def]
    response = await call_next(request)
    response.headers.setdefault("X-Content-Type-Options", "nosniff")
    response.headers.setdefault("Referrer-Policy", "strict-origin-when-cross-origin")
    response.headers.setdefault("X-Frame-Options", "SAMEORIGIN")
    return response


@app.exception_handler(RequestValidationError)
async def validation_error(_: Request, exc: RequestValidationError) -> JSONResponse:
    errors = {}
    for err in exc.errors():
        loc = [str(p) for p in err["loc"] if p not in ("body", "query", "path")]
        msg = err["msg"].removeprefix("Value error, ")
        errors.setdefault(".".join(loc) or "_", msg)
    first = next(iter(errors.values()), "Data tidak valid.")
    return JSONResponse({"detail": first, "errors": errors}, status_code=422)


@app.get("/api/health")
def health() -> dict:
    return {"ok": True}


for module in (auth, invoices, payment, quotations, master):
    app.include_router(module.router)
