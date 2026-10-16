export type Admin = { id: number; name: string; email: string };

export type Paginated<T> = {
  data: T[];
  meta: { page: number; per_page: number; total: number; pages: number };
};

export type InvoiceStatus = 'draft' | 'sent' | 'pending_confirmation' | 'paid' | 'cancelled' | 'expired';

export type InvoiceSummary = {
  id: number;
  invoice_number: string;
  customer_name: string;
  customer_phone: string | null;
  customer_email: string | null;
  total_amount: number;
  payment_method: 'transfer' | 'qris' | null;
  status: InvoiceStatus;
  status_label: string;
  due_date: string | null;
  paid_at: string | null;
  created_at: string;
  creator: Admin | null;
};

export type PaymentProof = {
  id: number;
  file_name: string;
  file_url: string | null;
  is_pdf: boolean;
  status: 'pending' | 'confirmed' | 'rejected';
  admin_notes: string | null;
  confirmed_at: string | null;
  confirmed_by: Admin | null;
  created_at: string;
};

export type Courier = 'jne' | 'jnt';

export type Shipment = {
  courier: Courier;
  courier_name: string;
  tracking_number: string;
  tracking_url: string;
  shipped_at: string | null;
};

export type InvoiceDetail = InvoiceSummary & {
  shipment: Shipment | null;
  customer_address: string | null;
  subtotal: number;
  discount: number;
  notes: string | null;
  payment_token?: string;
  payment_url?: string;
  items: { id: number; product_name: string; description: string | null; quantity: number; unit_price: number; total_price: number }[];
  extras: { id: number; label: string; amount: number }[];
  latest_proof: PaymentProof | null;
  payment_proofs?: PaymentProof[];
};

export type Buyer = {
  id: number;
  company_name: string;
  contact_person: string | null;
  email: string | null;
  phone: string | null;
  address: string | null;
  city: string | null;
  state_province: string | null;
  postal_code: string | null;
  country: string | null;
  country_code: string | null;
  tax_vat_number: string | null;
  notes: string | null;
  quotations_count?: number;
};

export type Product = {
  id: number;
  name: string;
  species: string | null;
  category: 'raw' | 'value_added';
  hs_code: string | null;
  default_unit: string;
  default_country_of_origin: string;
  is_active: boolean;
  sort_order: number;
  attributes: Record<string, string>;
  attribute_list: { key: string; value: string }[];
};

export type QuotationStatus =
  | 'draft' | 'sent' | 'accepted' | 'rejected' | 'expired' | 'converted_to_proforma' | 'cancelled';

export type QuotationSummary = {
  id: number;
  quotation_number: string;
  buyer_company_name: string;
  buyer_country: string | null;
  currency: string;
  incoterm: string;
  grand_total: number;
  quotation_date: string;
  valid_until: string;
  status: QuotationStatus;
  status_label: string;
  is_expired: boolean;
  creator: Admin | null;
};

export type QuotationItem = {
  id?: number;
  product_id: number | null;
  product_name: string;
  species: string | null;
  grade: string | null;
  size: string | null;
  weight: string | null;
  moisture: string | null;
  packaging: string | null;
  condition: string | null;
  aroma: string | null;
  description: string | null;
  hs_code: string | null;
  country_of_origin: string | null;
  quantity: number;
  unit: string;
  unit_price: number;
  line_total?: number;
};

export type QuotationDetail = QuotationSummary & {
  buyer_id: number | null;
  buyer_contact_person: string | null;
  buyer_email: string | null;
  buyer_phone: string | null;
  buyer_address: string | null;
  buyer_city: string | null;
  buyer_state_province: string | null;
  buyer_postal_code: string | null;
  buyer_country_code: string | null;
  buyer_tax_vat_number: string | null;
  validity_days: number;
  incoterm_place: string | null;
  destination_country: string | null;
  destination_country_code: string | null;
  port_of_loading: string | null;
  port_of_discharge: string | null;
  final_destination: string | null;
  shipping_method: string | null;
  production_lead_time: string | null;
  estimated_shipment: string | null;
  payment_terms: string | null;
  moq: string | null;
  country_of_origin: string;
  subtotal: number;
  discount_amount: number;
  freight_amount: number | null;
  insurance_amount: number | null;
  other_charges_label: string | null;
  other_charges_amount: number | null;
  tax_label: string | null;
  tax_rate: number | null;
  tax_amount: number | null;
  notes: string | null;
  pdf_generated_at: string | null;
  items: QuotationItem[];
};

export type TradeMeta = {
  countries: { code: string; name: string }[];
  currencies: { code: string; name: string; symbol: string }[];
  incoterms: string[];
  quotation_statuses: Record<QuotationStatus, string>;
};

export type CompanySettings = {
  company_name: string;
  logo_path: string | null;
  address: string | null;
  country: string;
  email: string | null;
  phone: string | null;
  website: string | null;
  bank_name: string | null;
  bank_branch: string | null;
  bank_account_name: string | null;
  bank_account_number: string | null;
  bank_account_currency: string | null;
  swift_bic: string | null;
  bank_address: string | null;
  intermediary_bank_name: string | null;
  intermediary_bank_swift: string | null;
  intermediary_bank_account: string | null;
  payment_charge_instruction: 'OUR' | 'SHA' | 'BEN' | null;
  authorized_person_name: string | null;
  authorized_person_position: string | null;
  default_terms_conditions: string | null;
  has_uploaded_logo: boolean;
};
