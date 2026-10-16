/**
 * Client-side handling of payment-proof uploads: accept only images and PDFs,
 * and shrink images before they leave the phone (Vercel caps request bodies at
 * 4.5 MB, and smaller uploads finish faster on mobile data).
 */

export const MAX_UPLOAD_BYTES = 4 * 1024 * 1024;
const MAX_SIDE = 1600;
const JPEG_QUALITY = 0.7;

// Some browsers report HEIC/HEIF with an empty MIME type, so fall back to the extension.
const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'tif', 'tiff', 'heic', 'heif', 'avif'];

export const UNSUPPORTED_MESSAGE =
  'Format file tidak didukung. Mohon unggah bukti transfer dalam bentuk gambar (JPG, PNG, HEIC, WebP, dan sejenisnya) atau dokumen PDF.';

export type ProofKind = 'image' | 'pdf' | 'unsupported';

export function proofKind(file: File): ProofKind {
  const ext = file.name.split('.').pop()?.toLowerCase() ?? '';
  if (file.type === 'application/pdf' || ext === 'pdf') return 'pdf';
  if (file.type.startsWith('image/') || IMAGE_EXTENSIONS.includes(ext)) return 'image';
  return 'unsupported';
}

/**
 * Downscale and re-encode as JPEG. Returns the original file when the browser
 * cannot decode it (e.g. HEIC on Chrome) — the server converts those instead —
 * or when re-encoding would not make it smaller.
 */
export async function compressImage(file: File): Promise<File> {
  let bitmap: ImageBitmap;
  try {
    bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
  } catch {
    return file;
  }

  const scale = Math.min(1, MAX_SIDE / Math.max(bitmap.width, bitmap.height));
  const canvas = document.createElement('canvas');
  canvas.width = Math.round(bitmap.width * scale);
  canvas.height = Math.round(bitmap.height * scale);
  const ctx = canvas.getContext('2d');
  if (!ctx) return file;
  ctx.fillStyle = '#ffffff'; // transparent PNGs become white, not black, in JPEG
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  ctx.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
  bitmap.close();

  const blob = await new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, 'image/jpeg', JPEG_QUALITY));
  if (!blob || blob.size >= file.size) return file;
  const base = file.name.replace(/\.[^.]+$/, '') || 'bukti-transfer';
  return new File([blob], `${base}.jpg`, { type: 'image/jpeg' });
}

export function formatBytes(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`;
  return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}
