import time

import bcrypt
from fastapi import Depends, HTTPException, Request, status
from sqlalchemy.orm import Session

from .db import get_db
from .models import Admin

SESSION_KEY = "admin_id"
SESSION_EXPIRES_KEY = "expires_at"
SESSION_SECONDS = 60 * 120  # Laravel SESSION_LIFETIME default
REMEMBER_SECONDS = 60 * 60 * 24 * 30


def hash_password(plain: str) -> str:
    return bcrypt.hashpw(plain.encode(), bcrypt.gensalt(rounds=12)).decode()


def verify_password(plain: str, hashed: str) -> bool:
    # Laravel writes "$2y$" hashes; bcrypt treats 2y and 2b identically.
    normalized = hashed.replace("$2y$", "$2b$", 1)
    try:
        return bcrypt.checkpw(plain.encode(), normalized.encode())
    except ValueError:
        return False


def current_admin(request: Request, db: Session = Depends(get_db)) -> Admin:
    admin_id = request.session.get(SESSION_KEY)
    if request.session.get(SESSION_EXPIRES_KEY, 0) < time.time():
        admin_id = None
    admin = db.get(Admin, admin_id) if admin_id else None
    if admin is None:
        request.session.clear()
        raise HTTPException(status.HTTP_401_UNAUTHORIZED, "Silakan login terlebih dahulu.")
    return admin
