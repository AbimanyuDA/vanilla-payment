"""Normalise uploaded payment proofs into small files.

Any image Pillow can decode (JPEG, PNG, WebP, GIF, BMP, TIFF, HEIC/HEIF…) is
re-encoded as a downscaled JPEG; PDFs get their embedded images recompressed.
Proofs are stored base64 in Postgres, so every kilobyte saved is kept forever.
"""

import io
from dataclasses import dataclass

from PIL import Image, ImageOps, UnidentifiedImageError
from pillow_heif import register_heif_opener
from pypdf import PdfReader, PdfWriter
from pypdf.errors import PyPdfError

register_heif_opener()

MAX_SIDE = 1600  # keeps transfer amounts and account numbers legible
JPEG_QUALITY = 68
# Second, harsher pass when a busy photo is still large after the first one.
FALLBACK_SIDE = 1280
FALLBACK_QUALITY = 55
TARGET_BYTES = 350 * 1024


UNSUPPORTED_FORMAT = (
    "Format file tidak didukung. Mohon unggah bukti transfer dalam bentuk gambar "
    "(JPG, PNG, HEIC, WebP, dan sejenisnya) atau dokumen PDF."
)
UNREADABLE_PDF = (
    "File PDF tidak dapat dibaca atau rusak. Mohon unggah ulang PDF yang valid, "
    "atau kirim screenshot bukti transfer dalam bentuk gambar."
)


class InvalidProof(ValueError):
    pass


@dataclass(frozen=True)
class CompressedFile:
    content: bytes
    mime: str
    extension: str


def compress_proof(content: bytes) -> CompressedFile:
    if content.startswith(b"%PDF"):
        return CompressedFile(_compress_pdf(content), "application/pdf", "pdf")
    return CompressedFile(_compress_image(content), "image/jpeg", "jpg")


def _to_rgb(image: Image.Image) -> Image.Image:
    image = ImageOps.exif_transpose(image)
    if image.mode in ("RGBA", "LA", "P"):
        image = image.convert("RGBA")
        background = Image.new("RGB", image.size, (255, 255, 255))
        background.paste(image, mask=image.split()[-1])
        return background
    return image.convert("RGB")


def _encode_jpeg(image: Image.Image, side: int, quality: int) -> bytes:
    resized = image.copy()
    resized.thumbnail((side, side), Image.Resampling.LANCZOS)
    buffer = io.BytesIO()
    resized.save(buffer, "JPEG", quality=quality, optimize=True, progressive=True)
    return buffer.getvalue()


def _compress_image(content: bytes) -> bytes:
    try:
        with Image.open(io.BytesIO(content)) as opened:
            opened.seek(0)  # animated formats: first frame only
            image = _to_rgb(opened)
    except (UnidentifiedImageError, Image.DecompressionBombError, OSError, ValueError) as e:
        raise InvalidProof(UNSUPPORTED_FORMAT) from e

    result = _encode_jpeg(image, MAX_SIDE, JPEG_QUALITY)
    if len(result) > TARGET_BYTES:
        result = _encode_jpeg(image, FALLBACK_SIDE, FALLBACK_QUALITY)
    return result


def _compress_pdf(content: bytes) -> bytes:
    try:
        writer = PdfWriter(clone_from=PdfReader(io.BytesIO(content)))
    except (PyPdfError, ValueError, OSError) as e:
        raise InvalidProof(UNREADABLE_PDF) from e

    for page in writer.pages:
        for embedded in page.images:
            try:
                image = embedded.image
                if image is None or image.width * image.height < 200 * 200:
                    continue
                image = image.convert("RGB") if image.mode not in ("RGB", "L") else image.copy()
                image.thumbnail((MAX_SIDE, MAX_SIDE), Image.Resampling.LANCZOS)
                embedded.replace(image, quality=60)
            except Exception:  # an exotic image encoding is left as-is, not fatal
                continue
        page.compress_content_streams()
    writer.compress_identical_objects(remove_identicals=True, remove_orphans=True)

    buffer = io.BytesIO()
    writer.write(buffer)
    compressed = buffer.getvalue()
    return compressed if len(compressed) < len(content) else content
