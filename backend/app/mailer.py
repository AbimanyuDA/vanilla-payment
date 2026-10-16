"""Invoice email — SMTP when MAIL_MAILER=smtp, otherwise logged (Laravel's `log` mailer)."""

import logging
import smtplib
from email.message import EmailMessage
from html import escape

from .config import get_settings
from .documents.pdf_base import rupiah
from .models import Invoice
from .serializers import shipment_out

logger = logging.getLogger(__name__)


BUTTON = "display:inline-block;background:#1a1210;color:#fbfaf8;padding:12px 22px;text-decoration:none;border-radius:4px"


def _rows(pairs: list[tuple[str, str]]) -> str:
    cells = "".join(f"<tr><td>{escape(k)}</td><td><strong>{escape(v)}</strong></td></tr>" for k, v in pairs)
    return f'<table cellpadding="8" style="border-collapse:collapse;background:#f2ece2;margin:16px 0">{cells}</table>'


def _body(invoice: Invoice, payment_url: str) -> tuple[str, str]:
    """Subject and HTML body — a payment request, or a receipt once paid."""
    if invoice.status != "paid":
        pairs = [("No. Invoice", invoice.invoice_number), ("Total", rupiah(invoice.total_amount))]
        if invoice.due_date:
            pairs.append(("Jatuh Tempo", f"{invoice.due_date:%d %b %Y}"))
        return f"Invoice {invoice.invoice_number} - Vanilla Royal", (
            "<p>Berikut adalah invoice pembayaran dari Vanilla Royal:</p>" + _rows(pairs)
            + f'<p><a href="{escape(payment_url)}" style="{BUTTON}">Bayar Sekarang</a></p>'
            "<p>Klik tombol di atas untuk memilih metode pembayaran.</p>"
        )

    shipment = shipment_out(invoice)
    pairs = [("No. Invoice", invoice.invoice_number), ("Total dibayar", rupiah(invoice.total_amount))]
    shipping = "<p>Pesanan Anda sedang kami siapkan untuk pengiriman.</p>"
    if shipment:
        pairs += [("Kurir", shipment["courier_name"]), ("No. Resi", shipment["tracking_number"])]
        shipping = (
            "<p>Pesanan Anda sudah dikirim. Salin nomor resi lalu lacak di "
            f'<a href="{escape(shipment["tracking_url"])}">halaman pelacakan {escape(shipment["courier_name"])}</a>.</p>'
        )
    return f"Pembayaran diterima - {invoice.invoice_number} - Vanilla Royal", (
        "<p>Terima kasih, pembayaran Anda telah kami terima dan dikonfirmasi.</p>" + _rows(pairs) + shipping
        + f'<p><a href="{escape(payment_url)}" style="{BUTTON}">Lihat invoice lunas</a></p>'
    )


def send_invoice_email(invoice: Invoice, payment_url: str) -> None:
    s = get_settings()
    subject, content = _body(invoice, payment_url)
    html = f"""
<div style="font-family:Helvetica,Arial,sans-serif;background:#fbfaf8;color:#1a1210;padding:32px">
  <p style="font:700 11px monospace;letter-spacing:.14em;color:#b8681a;text-transform:uppercase">Invoice {escape(invoice.invoice_number)}</p>
  <h1 style="font-family:Georgia,serif;font-size:24px;margin:0 0 16px">Halo {escape(invoice.customer_name)},</h1>
  {content}
  <p>Terima kasih telah berbelanja di Vanilla Royal!<br>Salam,<br>Tim Vanilla Royal</p>
</div>"""

    msg = EmailMessage()
    msg["Subject"] = subject
    msg["From"] = f"{s.mail_from_name} <{s.mail_from_address}>"
    msg["To"] = invoice.customer_email
    msg.set_content(f"{subject}. Detail: {payment_url}")
    msg.add_alternative(html, subtype="html")

    if s.mail_mailer != "smtp":
        logger.info("MAIL_MAILER=%s, email not sent: %s -> %s", s.mail_mailer, msg["Subject"], msg["To"])
        return
    with smtplib.SMTP(s.mail_host, s.mail_port, timeout=15) as smtp:
        smtp.starttls()
        if s.mail_username:
            smtp.login(s.mail_username, s.mail_password)
        smtp.send_message(msg)
