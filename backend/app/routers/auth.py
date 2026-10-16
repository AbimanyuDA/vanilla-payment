import time

from fastapi import APIRouter, Depends, HTTPException, Request
from sqlalchemy import func, select
from sqlalchemy.orm import Session

from ..db import get_db
from ..models import Admin
from ..schemas import LoginIn
from ..security import (
    REMEMBER_SECONDS, SESSION_EXPIRES_KEY, SESSION_KEY, SESSION_SECONDS, current_admin,
    verify_password,
)
from ..serializers import admin_out

router = APIRouter(prefix="/api/auth", tags=["auth"])


@router.post("/login")
def login(payload: LoginIn, request: Request, db: Session = Depends(get_db)) -> dict:
    admin = db.scalar(select(Admin).where(func.lower(Admin.email) == payload.email.lower()))
    if admin is None or not verify_password(payload.password, admin.password):
        raise HTTPException(422, "Email atau password salah.")
    request.session.clear()
    request.session[SESSION_KEY] = admin.id
    lifetime = REMEMBER_SECONDS if payload.remember else SESSION_SECONDS
    request.session[SESSION_EXPIRES_KEY] = int(time.time()) + lifetime
    return {"admin": admin_out(admin)}


@router.post("/logout")
def logout(request: Request) -> dict:
    request.session.clear()
    return {"message": "Berhasil logout."}


@router.get("/me")
def me(admin: Admin = Depends(current_admin)) -> dict:
    return {"admin": admin_out(admin)}
