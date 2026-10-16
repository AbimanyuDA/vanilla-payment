# Vanilla Royal Payment

Admin invoice domestik, halaman pembayaran customer (`/pay/{token}`), dan quotation ekspor internasional.

- **Backend:** FastAPI + SQLAlchemy (`backend/app`), dijalankan di Vercel sebagai Python function (`api/index.py`)
- **Frontend:** React 19 + TypeScript + Vite + Tailwind v4 (`frontend/`), dengan design token dari `vanilla-website-new`
- **Database:** Neon Postgres yang sama dengan versi Laravel. Tidak ada migrasi data. Satu kolom tambahan `company_settings.logo_data` dibuat otomatis saat startup.

Versi Laravel lama tersimpan di tag git `laravel-final`.

## Development lokal

```bash
# Backend (port 8001)
uv venv -p 3.12 .venv && uv pip install -p .venv -r requirements.txt uvicorn pytest httpx
cp .env.example .env   # isi SECRET_KEY; kosongkan DB_* untuk memakai SQLite lokal
cd backend && ../.venv/bin/python -m app.cli init-db && ../.venv/bin/python -m app.cli seed   # khusus SQLite lokal
../.venv/bin/uvicorn app.main:app --reload --port 8001

# Frontend (port 5173, request /api di-proxy ke 8001)
cd frontend && npm install && npm run dev
```

Login default hasil seed: `admin@vanillaroyal.com` / `admin123`.

> ⚠️ `.env` di repo ini menunjuk ke database **produksi**. Untuk eksperimen, kosongkan `DB_HOST` agar memakai SQLite.

## Tes

```bash
.venv/bin/python -m pytest backend/tests
cd frontend && npm run build
```

## Struktur

```
api/index.py                Entry Vercel
backend/app/
  models.py                 Pemetaan 1:1 ke tabel Laravel
  schemas.py                Validasi request (Pydantic)
  services.py               Penomoran, perhitungan total, transisi status, snapshot PDF
  routers/                  auth, invoices (+dashboard, laporan), payment (publik), quotations, master
  documents/                PDF (fpdf2) dan Excel (openpyxl)
frontend/src/
  styles/global.css         Design token dan kelas komponen
  features/<area>/          Halaman per fitur
```
