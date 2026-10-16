import { useState, type FormEvent } from 'react';

import { Field } from '../../components/ui/primitives';
import { api, type Message } from '../../lib/api';
import { formatDate } from '../../lib/format';
import { fieldErrors, useAction } from '../../lib/hooks';
import type { Courier, Shipment } from '../../lib/types';

const COURIERS: { value: Courier; label: string }[] = [
  { value: 'jne', label: 'JNE' },
  { value: 'jnt', label: 'J&T Express' },
];

interface ShipmentPanelProps {
  invoiceId: number;
  shipment: Shipment | null;
  onSaved: () => void;
}

/** Courier + airway bill for a paid invoice; feeds the WhatsApp receipt and the customer page. */
export function ShipmentPanel({ invoiceId, shipment, onSaved }: ShipmentPanelProps) {
  const [editing, setEditing] = useState(!shipment);
  const [courier, setCourier] = useState<Courier>(shipment?.courier ?? 'jne');
  const [resi, setResi] = useState(shipment?.tracking_number ?? '');

  const save = useAction(
    () => api.put<Message>(`/api/invoices/${invoiceId}/shipment`, { courier, tracking_number: resi.trim() }),
    {
      invalidate: [['invoice']],
      onSuccess: () => {
        setEditing(false);
        onSaved();
      },
    },
  );
  const errors = fieldErrors(save.error);
  const submit = (e: FormEvent) => {
    e.preventDefault();
    save.mutate();
  };

  return (
    <section className="panel p-5">
      <div className="flex items-baseline justify-between gap-3">
        <div>
          <p className="eyebrow">Pengiriman</p>
          <h2 className="mt-1 text-lg">{shipment ? 'Sudah dikirim' : 'Input nomor resi'}</h2>
        </div>
        {shipment && !editing && (
          <button className="link text-sm" onClick={() => setEditing(true)}>Ubah</button>
        )}
      </div>

      {shipment && !editing ? (
        <dl className="mt-4 space-y-2 text-sm">
          <div className="flex justify-between gap-3"><dt className="eyebrow">Kurir</dt><dd>{shipment.courier_name}</dd></div>
          <div className="flex justify-between gap-3"><dt className="eyebrow">No. resi</dt><dd className="font-mono">{shipment.tracking_number}</dd></div>
          <div className="flex justify-between gap-3"><dt className="eyebrow">Diinput</dt><dd className="text-clay">{formatDate(shipment.shipped_at, true)}</dd></div>
          <a href={shipment.tracking_url} target="_blank" rel="noreferrer" className="btn-secondary btn-sm mt-2 w-full">
            Buka halaman lacak {shipment.courier_name} ↗
          </a>
        </dl>
      ) : (
        <form onSubmit={submit} noValidate className="mt-4 space-y-3">
          <Field label="Kurir" error={errors.courier}>
            <div className="grid grid-cols-2 gap-2" role="radiogroup" aria-label="Kurir">
              {COURIERS.map((c) => (
                <button key={c.value} type="button" role="radio" aria-checked={courier === c.value}
                  onClick={() => setCourier(c.value)}
                  className={`rounded-md border-2 px-3 py-2 text-sm font-medium transition-colors ${courier === c.value ? 'border-amber bg-[#fbf3e8] text-amber-deep' : 'border-frost-deep hover:border-clay'}`}>
                  {c.label}
                </button>
              ))}
            </div>
          </Field>
          <Field label="Nomor resi" error={errors.tracking_number}>
            <input className="input font-mono uppercase" value={resi} autoComplete="off" spellCheck={false}
              aria-invalid={!!errors.tracking_number} placeholder="mis. CGK1234567890"
              onChange={(e) => setResi(e.target.value)} />
          </Field>
          <div className="flex gap-2">
            <button className="btn-primary flex-1" disabled={save.isPending || resi.trim().length < 6}>
              {save.isPending ? 'Menyimpan…' : 'Simpan resi'}
            </button>
            {shipment && <button type="button" className="btn-secondary" onClick={() => setEditing(false)}>Batal</button>}
          </div>
        </form>
      )}
    </section>
  );
}
