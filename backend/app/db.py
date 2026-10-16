from collections.abc import Iterator
from datetime import date, datetime

from sqlalchemy import create_engine
from sqlalchemy.orm import DeclarativeBase, Session, sessionmaker
from sqlalchemy.pool import NullPool

from .config import get_settings


def now_local() -> datetime:
    """Naive Asia/Jakarta time — matches how Laravel stored timestamps."""
    return datetime.now(get_settings().timezone).replace(tzinfo=None)


def today_local() -> date:
    return now_local().date()


class Base(DeclarativeBase):
    pass


_settings = get_settings()
# Serverless: no pooling across invocations; Neon's pooler handles reuse.
engine = create_engine(
    _settings.database_url,
    poolclass=NullPool if _settings.database_url.startswith("postgresql") else None,
)
SessionLocal = sessionmaker(bind=engine, autoflush=False, expire_on_commit=False)


def get_db() -> Iterator[Session]:
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()
