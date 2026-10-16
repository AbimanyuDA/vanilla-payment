"""Shared PDF primitives in the Vanilla Royal visual language.

Mirrors the website tokens: ink text on paper, frost surfaces, a single amber
accent, moss for settled states. Core fonts only (Times for display, Helvetica
for body, Courier for labels) — pure Python, so it runs on Vercel's runtime.
"""

import base64
import io
from decimal import Decimal
from pathlib import Path
from typing import Any

from fpdf import FPDF

INK = (26, 18, 16)
INK_SOFT = (61, 47, 40)
CLAY = (112, 99, 87)
FROST = (242, 236, 226)
FROST_DEEP = (230, 220, 205)
AMBER = (184, 104, 26)
MOSS = (74, 93, 58)
FLAG = (163, 50, 31)
PAPER = (251, 250, 248)

ASSETS = Path(__file__).resolve().parents[1] / "assets"

_REPLACEMENTS = {
    "•": "·", "€": "EUR ", "—": "-", "–": "-", "‘": "'", "’": "'",
    "“": '"', "”": '"', "…": "...", "→": "->", "←": "<-", " ": " ",
}


def t(value: Any) -> str:
    """Coerce to text the latin-1 core fonts can render."""
    text = "" if value is None else str(value)
    for src, dst in _REPLACEMENTS.items():
        text = text.replace(src, dst)
    return text.encode("latin-1", "replace").decode("latin-1")


def rupiah(value: Decimal | float | None) -> str:
    return "Rp " + f"{float(value or 0):,.0f}".replace(",", ".")


def money(value: Decimal | float | None) -> str:
    return f"{float(value or 0):,.2f}"


def trim_qty(value: Decimal | float) -> str:
    return f"{float(value):,.2f}".rstrip("0").rstrip(".")


def logo_source(data_url: str | None, fallback: str = "logo.png") -> Any:
    if data_url and data_url.startswith("data:") and "," in data_url:
        try:
            return io.BytesIO(base64.b64decode(data_url.split(",", 1)[1]))
        except ValueError:
            pass
    path = ASSETS / fallback
    return str(path) if path.exists() else None


class BrandPDF(FPDF):
    footer_text: str = ""
    show_page_numbers: bool = False

    def __init__(self, orientation: str = "P") -> None:
        super().__init__(orientation=orientation, unit="mm", format="A4")
        self.set_margins(18, 16, 18)
        self.set_auto_page_break(True, margin=22)
        self.set_text_color(*INK)

    # ── text helpers ────────────────────────────────────────────────────
    def eyebrow(self, text: str, color: tuple[int, int, int] = AMBER, size: float = 7) -> None:
        self.set_font("Courier", "B", size)
        self.set_text_color(*color)
        self.cell(0, 4, t(text.upper()), new_x="LMARGIN", new_y="NEXT")
        self.set_text_color(*INK)

    def display(self, text: str, size: float = 16) -> None:
        self.set_font("Times", "B", size)
        self.set_text_color(*INK)
        self.multi_cell(0, size * 0.45, t(text), new_x="LMARGIN", new_y="NEXT")

    def body(self, text: str, size: float = 9, color: tuple[int, int, int] = INK_SOFT,
             width: float = 0, style: str = "") -> None:
        self.set_font("Helvetica", style, size)
        self.set_text_color(*color)
        self.multi_cell(width, size * 0.48, t(text), new_x="LMARGIN", new_y="NEXT")
        self.set_text_color(*INK)

    def rule(self, color: tuple[int, int, int] = AMBER, weight: float = 0.6) -> None:
        self.set_draw_color(*color)
        self.set_line_width(weight)
        self.line(self.l_margin, self.get_y(), self.w - self.r_margin, self.get_y())
        self.set_line_width(0.2)

    @property
    def content_width(self) -> float:
        return self.w - self.l_margin - self.r_margin

    # ── shared blocks ───────────────────────────────────────────────────
    def letterhead(self, logo: Any, name: str, lines: list[str], doc_label: str,
                   doc_number: str, meta: list[str]) -> None:
        top = self.get_y()
        text_x = self.l_margin
        if logo is not None:
            try:
                self.image(logo, x=self.l_margin, y=top, h=18)
                text_x = self.l_margin + 22
            except Exception:  # unreadable image should never block a document
                text_x = self.l_margin
        self.set_xy(text_x, top)
        self.set_font("Times", "B", 15)
        self.cell(95, 7, t(name), new_x="LEFT", new_y="NEXT")
        self.set_font("Helvetica", "", 7.5)
        self.set_text_color(*CLAY)
        for line in lines:
            if line:
                self.set_x(text_x)
                self.multi_cell(95, 3.6, t(line), new_x="LEFT", new_y="NEXT")
        left_bottom = self.get_y()

        right_x = self.w - self.r_margin - 70
        self.set_xy(right_x, top)
        self.set_font("Courier", "B", 7.5)
        self.set_text_color(*AMBER)
        self.cell(70, 4, t(doc_label.upper()), align="R", new_x="LEFT", new_y="NEXT")
        self.set_font("Times", "B", 14)
        self.set_text_color(*INK)
        self.cell(70, 7, t(doc_number), align="R", new_x="LEFT", new_y="NEXT")
        self.set_font("Helvetica", "", 8)
        self.set_text_color(*INK_SOFT)
        for line in meta:
            self.cell(70, 4, t(line), align="R", new_x="LEFT", new_y="NEXT")
        self.set_text_color(*INK)

        self.set_y(max(left_bottom, self.get_y(), top + 20) + 4)
        self.rule()
        self.ln(6)

    def items_table(self, headers: list[str], widths: list[float], aligns: list[str],
                    rows: list[list[tuple[str, str]]]) -> None:
        """rows: list of cells, each cell a (main, sub) pair; sub may be empty."""
        self.set_fill_color(*FROST)
        self.set_font("Courier", "B", 7)
        self.set_text_color(*CLAY)
        for header, width, align in zip(headers, widths, aligns):
            self.cell(width, 7, t(header.upper()), fill=True, align=align)
        self.ln(7)
        self.set_text_color(*INK)
        self.set_draw_color(*FROST_DEEP)
        for row in rows:
            start_y = self.get_y()
            heights = []
            x = self.l_margin
            for (main, sub), width, align in zip(row, widths, aligns):
                self.set_xy(x, start_y + 2)
                self.set_font("Helvetica", "B" if sub or align == "L" else "", 8.5)
                self.multi_cell(width - 2, 4.2, t(main), align=align, new_x="LEFT", new_y="NEXT")
                if sub:
                    self.set_font("Helvetica", "", 7.2)
                    self.set_text_color(*CLAY)
                    self.multi_cell(width - 2, 3.5, t(sub), align=align, new_x="LEFT", new_y="NEXT")
                    self.set_text_color(*INK)
                heights.append(self.get_y() - start_y)
                x += width
            end_y = start_y + max(heights) + 2
            self.line(self.l_margin, end_y, self.l_margin + sum(widths), end_y)
            self.set_y(end_y)
            if self.get_y() > self.h - 40:
                self.add_page()

    def totals_box(self, rows: list[tuple[str, str]], grand: tuple[str, str],
                   x: float, width: float = 78) -> None:
        self.set_font("Helvetica", "", 8.5)
        for label, value in rows:
            self.set_x(x)
            self.set_text_color(*INK_SOFT)
            self.cell(width * 0.5, 5.5, t(label))
            self.set_text_color(*INK)
            self.cell(width * 0.5, 5.5, t(value), align="R", new_x="LMARGIN", new_y="NEXT")
        self.ln(1)
        self.set_x(x)
        self.set_fill_color(*INK)
        self.set_text_color(*PAPER)
        self.set_font("Courier", "B", 8)
        self.cell(width * 0.4, 9, "  " + t(grand[0].upper()), fill=True)
        self.set_font("Times", "B", 12)
        self.cell(width * 0.6, 9, t(grand[1]) + "  ", fill=True, align="R",
                  new_x="LMARGIN", new_y="NEXT")
        self.set_text_color(*INK)

    def footer(self) -> None:
        self.set_y(-14)
        self.set_draw_color(*FROST_DEEP)
        self.line(self.l_margin, self.get_y(), self.w - self.r_margin, self.get_y())
        self.ln(2)
        self.set_font("Courier", "", 6.5)
        self.set_text_color(*CLAY)
        self.cell(self.content_width * 0.8, 4, t(self.footer_text))
        if self.show_page_numbers:
            self.cell(self.content_width * 0.2, 4, f"Page {self.page_no()} of {{nb}}", align="R")
        self.set_text_color(*INK)

    def output_bytes(self) -> bytes:
        return bytes(self.output())
