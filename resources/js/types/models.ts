/**
 * Domain model types for the Direct Auto Care frontend.
 *
 * Generated from the Laravel Eloquent models, their `casts()`, and the
 * authoritative database migrations (Phase 4 of the TSX migration — ADR-004).
 *
 * Conventions:
 * - `decimal:N` casts serialize to a `string` in JSON, so they are typed `string`.
 *   `float`/`integer` casts are typed `number`.
 * - Dates/datetimes/timestamps serialize to ISO `string`.
 * - Relationships are optional because they are only present when eager-loaded.
 * - Appended/computed attributes are flagged with a comment.
 */

/* ------------------------------------------------------------------ *
 * Enum string-literal unions (backing values from app/Enums/*.php)
 *
 * NOTE: these are hand-mirrored from the PHP enums — there is no build-time
 * link, so keep them in sync when an `app/Enums/*` backing value changes.
 * (Auto-generation from Eloquent is deferred to a future ADR.)
 * ------------------------------------------------------------------ */

export type CustomerType = 'individual' | 'company';

export type FuelType = 'essence' | 'diesel' | 'hybride' | 'electrique';

export type StockMovementType = 'in' | 'out' | 'adjustment';

export type InvoiceStatus = 'draft' | 'issued' | 'paid' | 'cancelled';

export type InvoiceType = 'quote' | 'invoice';

export type ProductCategory = 'huile' | 'filtre' | 'piece' | 'autre';

export type ProductUnit =
    | 'unite'
    | 'litre'
    | 'millilitre'
    | 'kg'
    | 'bidon'
    | 'flacon'
    | 'boite'
    | 'paire'
    | 'jeu'
    | 'kit';

export type AppointmentStatus = 'pending' | 'confirmed' | 'completed' | 'cancelled';

export type ReminderStatus = 'pending' | 'sent' | 'acknowledged' | 'cancelled';

export type ReminderType = 'vidange' | 'visite_technique' | 'entretien_periodique';

export type ServiceOrderStatus =
    | 'reception'
    | 'diagnosis'
    | 'quote_sent'
    | 'in_progress'
    | 'quality_control'
    | 'notified'
    | 'ready_pickup'
    | 'closed'
    | 'cancelled';

/* ------------------------------------------------------------------ *
 * Models
 * ------------------------------------------------------------------ */

/** `users` table. */
export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    is_active: boolean;
    locale: string;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
}

/** `customers` table. */
export interface Customer {
    id: number;
    type: CustomerType;
    /** Raison sociale — set for `company` clients. */
    company_name: string | null;
    /** Moroccan fiscal IDs — only meaningful for `company` clients. */
    ice: string | null;
    if: string | null;
    patente: string | null;
    ville: string | null;
    /** Optional contact person for companies; the client's name for individuals. */
    first_name: string | null;
    last_name: string | null;
    phone: string;
    email: string | null;
    address: string | null;
    notes: string | null;
    notify_email: boolean;
    notify_whatsapp: boolean;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;

    /** Computed accessor (appended): raison sociale for companies, else full name. */
    display_name: string;

    // Relationships
    vehicles?: Vehicle[];
    appointments?: Appointment[];
    invoices?: Invoice[];
}

/** `vehicles` table. */
export interface Vehicle {
    id: number;
    customer_id: number;
    make: string;
    model: string;
    year: number;
    license_plate: string;
    vin: string | null;
    mileage: number;
    fuel_type: FuelType;
    color: string | null;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;

    // Relationships
    customer?: Customer;
    serviceRecords?: ServiceRecord[];
    appointments?: Appointment[];
}

/** `invoices` table. */
export interface Invoice {
    id: number;
    type: InvoiceType;
    number: string;
    customer_id: number;
    vehicle_id: number | null;
    issue_date: string;
    due_date: string | null;
    fiscal_year: number;
    subtotal_ht: string;
    discount: string;
    tva_rate: string;
    tva_amount: string;
    total_ttc: string;
    status: InvoiceStatus;
    notes: string | null;
    created_by: number;
    converted_from_quote_id: number | null;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;

    // Relationships
    customer?: Customer;
    vehicle?: Vehicle;
    creator?: User;
    convertedFromQuote?: Invoice;
    convertedToInvoice?: Invoice;
    items?: InvoiceItem[];
    serviceRecords?: ServiceRecord[];
}

/** `invoice_items` table. */
export interface InvoiceItem {
    id: number;
    invoice_id: number;
    product_id: number | null;
    /** Sellable service (prestation) — mutually exclusive with product_id. */
    service_id: number | null;
    /** Optional per-line vehicle attribution (multi-vehicle invoices). */
    vehicle_id: number | null;
    description: string;
    /** Product stock unit snapshotted at line creation; null for service / free-text lines. */
    unit?: string | null;
    quantity: string;
    unit_price_ht: string;
    line_total_ht: string;
    created_at: string;
    updated_at: string;

    // Relationships
    invoice?: Invoice;
    vehicle?: Vehicle;
    product?: Product;
}

/** `products` table. */
export interface Product {
    id: number;
    category: ProductCategory;
    name: string;
    sku: string | null;
    unit: ProductUnit;
    price_buy: string | null;
    price_sell: string;
    quantity_on_hand: number;
    low_stock_threshold: number;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;

    /** Computed accessor (`isLowStock`): quantity_on_hand < low_stock_threshold. */
    is_low_stock?: boolean;

    // Relationships
    stockMovements?: StockMovement[];
}

/** `services` table — sellable prestations (main d'œuvre) with a default price. */
export interface Service {
    id: number;
    name: string;
    description: string | null;
    default_price_ht: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
}

/** `stock_movements` table. */
export interface StockMovement {
    id: number;
    product_id: number;
    type: StockMovementType;
    quantity: number;
    reason: string | null;
    invoice_id: number | null;
    user_id: number;
    created_at: string;
    updated_at: string;

    // Relationships
    product?: Product;
    user?: User;
    invoice?: Invoice;
}

/** `service_records` table. */
export interface ServiceRecord {
    id: number;
    vehicle_id: number;
    invoice_id: number | null;
    mileage_at_service: number | null;
    notes: string | null;
    serviced_at: string;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;

    // Relationships
    vehicle?: Vehicle;
    invoice?: Invoice;
    serviceTypes?: ServiceType[];
}

/** `service_types` table. */
export interface ServiceType {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;

    // Relationships
    serviceRecords?: ServiceRecord[];
}

/** `appointments` table. */
export interface Appointment {
    id: number;
    customer_id: number;
    vehicle_id: number | null;
    created_by: number;
    scheduled_at: string;
    duration: number;
    status: AppointmentStatus;
    notes: string | null;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;

    /** Computed accessor (appended): scheduled_at + duration minutes, ISO string. */
    ends_at: string | null;
    /** Computed accessor (`expectedServices`): service type names; requires serviceTypes eager-loaded. */
    expected_services?: string[];

    // Relationships
    customer?: Customer;
    vehicle?: Vehicle;
    createdBy?: User;
    serviceTypes?: ServiceType[];
    serviceOrder?: ServiceOrder;
}

/** `reminders` table. */
export interface Reminder {
    id: number;
    vehicle_id: number;
    customer_id: number;
    type: ReminderType;
    scheduled_for: string;
    status: ReminderStatus;
    trigger_km: number | null;
    sent_at: string | null;
    notes: string | null;
    error_message: string | null;
    created_at: string;
    updated_at: string;

    // Relationships
    vehicle?: Vehicle;
    customer?: Customer;
}

/** `service_orders` table. */
export interface ServiceOrder {
    id: number;
    number: string;
    appointment_id: number | null;
    customer_id: number;
    vehicle_id: number;
    mileage_in: number | null;
    /** JSON `array` cast: a `{ "PART_ID": "#hexcolor" }` map of marked body parts. */
    reception_condition: Record<string, string> | null;
    /** JSON `array` cast: list of stored photo paths. */
    reception_photos: string[] | null;
    fuel_level: number | null;
    signature_path: string | null;
    status: ServiceOrderStatus;
    diagnosis_notes: string | null;
    internal_notes: string | null;
    quote_id: number | null;
    invoice_id: number | null;
    received_at: string | null;
    diagnosed_at: string | null;
    quote_sent_at: string | null;
    in_progress_at: string | null;
    quality_control_at: string | null;
    notified_at: string | null;
    ready_pickup_at: string | null;
    closed_at: string | null;
    cancelled_at: string | null;
    created_by: number;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;

    // Relationships
    customer?: Customer;
    vehicle?: Vehicle;
    appointment?: Appointment;
    quote?: Invoice;
    invoice?: Invoice;
    createdBy?: User;
    items?: ServiceOrderItem[];
}

/** `service_order_items` table. */
export interface ServiceOrderItem {
    id: number;
    service_order_id: number;
    product_id: number | null;
    /** Sellable service (prestation) — mutually exclusive with product_id. */
    service_id: number | null;
    description: string;
    /** Product stock unit snapshotted at line creation; null for service / free-text lines. */
    unit?: string | null;
    quantity: number;
    unit_price_ht: string;
    line_total_ht: string;
    created_at: string;
    updated_at: string;

    // Relationships
    serviceOrder?: ServiceOrder;
    product?: Product;
}

/** `settings` table (single-row singleton). */
export interface Settings {
    id: number;
    company_name: string | null;
    company_address: string | null;
    company_ice: string | null;
    company_if: string | null;
    company_rc: string | null;
    tva_rate: number;
    logo_path: string | null;
    color_primary: string;
    color_accent: string;
    color_bg: string;
    color_text_primary: string;
    color_text_muted: string;
    color_success: string;
    color_warning: string;
    color_error: string;
    created_at: string;
    updated_at: string;
}
