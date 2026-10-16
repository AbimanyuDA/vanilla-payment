"""Invoice email — SMTP when MAIL_MAILER=smtp, otherwise logged (Laravel's `log` mailer)."""

import logging
import smtplib
from email.message import EmailMessage
from html import escape

from .config import get_settings
from .documents.pdf_base import rupiah
from .models import Invoice

logger = logging.getLogger(__name__)


def send_invoice_email(invoice: Invoice, payment_url: str) -> None:
    s = get_settings()
    due = f"<tr><td>Jatuh Tempo</td><td>{invoice.due_date:%d %b %Y}</td></tr>" if invoice.due_date else ""
    html = f"""
<div style="font-family:Helvetica,Arial,sans-serif;background:#fbfaf8;color:#1a1210;padding:32px">
  <p style="font:700 11px monospace;letter-spacing:.14em;color:#b8681a;text-transform:uppercase">Invoice {escape(invoice.invoice_number)}</p>
  <h1 style="font-family:Georgia,serif;font-size:24px;margin:0 0 16px">Halo {escape(invoice.customer_name)},</h1>
  <p>Berikut adalah invoice pembayaran dari Vanilla Royal:</p>
  <table cellpadding="8" style="border-collapse:collapse;background:#f2ece2;margin:16px 0">
    <tr><td>No. Invoice</td><td><strong>{escape(invoice.invoice_number)}</strong></td></tr>
    <tr><td>Total</td><td><strong>{rupiah(invoice.total_amount)}</strong></td></tr>{due}
  </table>
  <p><a href="{escape(payment_url)}" style="display:inline-block;background:#1a1210;color:#fbfaf8;padding:12px 22px;text-decoration:none;border-radius:4px">Bayar Sekarang</a></p>
  <p>Klik tombol di atas untuk memilih metode pembayaran.</p>
  <p>Terima kasih telah berbelanja di Vanilla Royal!<br>Salam,<br>Tim Vanilla Royal</p>
</div>"""

    msg = EmailMessage()
    msg["Subject"] = f"Invoice {invoice.invoice_number} - Vanilla Royal"
    msg["From"] = f"{s.mail_from_name} <{s.mail_from_address}>"
    msg["To"] = invoice.customer_email
    msg.set_content(f"Invoice {invoice.invoice_number}. Bayar di: {payment_url}")
    msg.add_alternative(html, subtype="html")

    if s.mail_mailer != "smtp":
        logger.info("MAIL_MAILER=%s, email not sent: %s -> %s", s.mail_mailer, msg["Subject"], msg["To"])
        return
    with smtplib.SMTP(s.mail_host, s.mail_port, timeout=15) as smtp:
        smtp.starttls()
        if s.mail_username:
            smtp.login(s.mail_username, s.mail_password)
        smtp.send_message(msg)
