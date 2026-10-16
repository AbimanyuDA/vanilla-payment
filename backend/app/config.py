"""Environment-driven settings.

Reads the same variable names the Laravel app used (DB_HOST, APP_KEY, MAIL_*),
so the existing Vercel environment keeps working without renaming anything.
"""

import os
from dataclasses import dataclass
from functools import lru_cache
from pathlib import Path
from urllib.parse import quote_plus
from zoneinfo import ZoneInfo


def _load_dotenv() -> None:
    """Minimal .env loader for local dev; real env vars always win."""
    env_file = Path(__file__).resolve().parents[2] / ".env"
    if not env_file.exists():
        return
    for raw in env_file.read_text().splitlines():
        line = raw.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, value = line.split("=", 1)
        os.environ.setdefault(key.strip(), value.strip().strip('"').strip("'"))


def _database_url() -> str:
    if url := os.getenv("DATABASE_URL"):
        return url.replace("postgres://", "postgresql+psycopg://", 1).replace(
            "postgresql://", "postgresql+psycopg://", 1
        )
    if os.getenv("DB_CONNECTION", "pgsql") != "pgsql" or not os.getenv("DB_HOST"):
        return "sqlite:///" + str(Path(__file__).resolve().parents[1] / "local.sqlite")
    user = quote_plus(os.getenv("DB_USERNAME", ""))
    password = quote_plus(os.getenv("DB_PASSWORD", ""))
    host = os.getenv("DB_HOST")
    port = os.getenv("DB_PORT", "5432")
    name = os.getenv("DB_DATABASE", "")
    sslmode = os.getenv("DB_SSLMODE", "require")
    return f"postgresql+psycopg://{user}:{password}@{host}:{port}/{name}?sslmode={sslmode}"


@dataclass(frozen=True)
class Settings:
    app_name: str
    app_url: str
    secret_key: str
    database_url: str
    debug: bool
    mail_mailer: str
    mail_host: str
    mail_port: int
    mail_username: str
    mail_password: str
    mail_from_address: str
    mail_from_name: str
    timezone: ZoneInfo


@lru_cache
def get_settings() -> Settings:
    _load_dotenv()
    secret = os.getenv("SECRET_KEY") or os.getenv("APP_KEY")
    if not secret:
        raise RuntimeError("SECRET_KEY (or APP_KEY) must be set")
    return Settings(
        app_name=os.getenv("APP_NAME", "Vanilla Royal Payment"),
        app_url=os.getenv("APP_URL", "http://localhost:5173").rstrip("/"),
        secret_key=secret,
        database_url=_database_url(),
        debug=os.getenv("APP_DEBUG", "false").lower() == "true",
        mail_mailer=os.getenv("MAIL_MAILER", "log"),
        mail_host=os.getenv("MAIL_HOST", "127.0.0.1"),
        mail_port=int(os.getenv("MAIL_PORT", "587")),
        mail_username=os.getenv("MAIL_USERNAME", "") if os.getenv("MAIL_USERNAME") != "null" else "",
        mail_password=os.getenv("MAIL_PASSWORD", "") if os.getenv("MAIL_PASSWORD") != "null" else "",
        mail_from_address=os.getenv("MAIL_FROM_ADDRESS", "hello@example.com"),
        mail_from_name=os.getenv("MAIL_FROM_NAME", "Vanilla Royal"),
        timezone=ZoneInfo("Asia/Jakarta"),
    )
