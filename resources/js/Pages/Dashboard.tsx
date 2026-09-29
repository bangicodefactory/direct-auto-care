import BarChartCard from '@/Components/Charts/BarChartCard';
import LineChartCard from '@/Components/Charts/LineChartCard';
import { useShowUnits } from '@/hooks/useShowUnits';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatQuantity } from '@/lib/utils';
import { Head, Link } from '@inertiajs/react';
import { ReactNode } from 'react';

interface AppointmentToday {
    id: number;
    customer: string;
    vehicle: string;
    scheduled_at: string;
    status: string;
    services: string[];
}

interface LowStockProduct {
    id: number;
    name: string;
    quantity_on_hand: number;
    low_stock_threshold: number;
    unit: string;
}

interface WorkshopPhase {
    value: string;
    label: string;
    color: string;
    count: number;
}

interface WorkshopStats {
    active: number;
    closed_today: number;
    by_status: WorkshopPhase[];
}

interface TopService {
    name: string;
    count: number;
}

interface MonthlyStat {
    month: string;
    revenue: number;
    appointments: number;
}

interface DashboardKpis {
    customers_total: number;
    customers_this_month: number;
    customers_delta: number;
    active_vehicles: number;
    pending_reminders: number;
    appointments_today: AppointmentToday[];
    low_stock: LowStockProduct[];
    workshop: WorkshopStats;
    revenue_month?: number;
    revenue_year?: number;
    revenue_last_year?: number;
    top_services?: TopService[];
    monthly_stats?: MonthlyStat[];
}

interface DashboardProps {
    kpis: DashboardKpis;
    isAdmin: boolean;
}

export default function Dashboard({ kpis, isAdmin }: DashboardProps) {
    const showUnits = useShowUnits();
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-foreground">
                    Tableau de bord
                </h2>
            }
        >
            <Head title="Tableau de bord" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                    {/* ── KPI cards — 2 col mobile, 4 col desktop ── */}
                    <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                        <KpiCard
                            label="Clients"
                            value={kpis.customers_total}
                            sub={<Delta value={kpis.customers_delta} />}
                        />
                        <KpiCard label="Véhicules actifs" value={kpis.active_vehicles} />
                        <KpiCard
                            label="Rappels en attente"
                            value={kpis.pending_reminders}
                            href={route('reminders.index')}
                        />
                        <KpiCard
                            label="RDV aujourd'hui"
                            value={kpis.appointments_today.length}
                            href={route('appointments.today-page')}
                        />
                        {isAdmin && (
                            <>
                                <KpiCard label="CA ce mois (DH)" value={fmt(kpis.revenue_month)} />
                                <KpiCard
                                    label="CA cette année (DH)"
                                    value={fmt(kpis.revenue_year)}
                                />
                                <KpiCard
                                    label="CA année dernière (DH)"
                                    value={fmt(kpis.revenue_last_year)}
                                />
                            </>
                        )}
                    </div>

                    {/* ── Atelier — active service orders per phase ── */}
                    <WorkshopSection workshop={kpis.workshop} />

                    {/* ── Lists — 1 col mobile, 2 col desktop ── */}
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <Section title="RDV du jour" href={route('appointments.today-page')}>
                            {kpis.appointments_today.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Aucun rendez-vous aujourd'hui.
                                </p>
                            ) : (
                                <ul className="divide-y divide-border">
                                    {kpis.appointments_today.map((a) => (
                                        <li
                                            key={a.id}
                                            className="flex items-center justify-between py-2 text-sm"
                                        >
                                            <div>
                                                <p className="font-medium text-foreground">
                                                    {a.customer}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {a.vehicle}
                                                </p>
                                                {a.services.length > 0 && (
                                                    <p className="text-xs text-muted-foreground">
                                                        {a.services.join(', ')}
                                                    </p>
                                                )}
                                            </div>
                                            <span className="ms-4 text-muted-foreground">
                                                {a.scheduled_at}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Section>

                        <Section title="Stock bas" href={route('products.index')}>
                            {kpis.low_stock.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Aucun produit en stock bas.
                                </p>
                            ) : (
                                <ul className="divide-y divide-border">
                                    {kpis.low_stock.map((p) => (
                                        <li
                                            key={p.id}
                                            className="flex items-center justify-between py-2 text-sm"
                                        >
                                            <Link
                                                href={route('products.edit', p.id)}
                                                className="font-medium text-foreground hover:text-primary"
                                            >
                                                {p.name}
                                            </Link>
                                            <span className="text-destructive">
                                                {p.quantity_on_hand} /{' '}
                                                {formatQuantity(
                                                    p.low_stock_threshold,
                                                    p.unit,
                                                    showUnits,
                                                )}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Section>
                    </div>

                    {/* ── Admin-only charts — 1 col mobile, 3 col desktop ── */}
                    {isAdmin && (
                        <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                            <BarChartCard
                                title="Top prestations"
                                data={kpis.top_services!}
                                dataKey="count"
                                nameKey="name"
                            />
                            <div className="lg:col-span-2">
                                <LineChartCard
                                    title="Évolution mensuelle (12 mois)"
                                    data={kpis.monthly_stats!}
                                />
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

// ── Helpers ──────────────────────────────────────────────────────────────────

function fmt(value: number | undefined) {
    return Number(value).toLocaleString('fr-MA', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function Delta({ value }: { value: number }) {
    if (value === 0) return <span className="text-xs text-muted-foreground">= ce mois</span>;
    const positive = value > 0;
    return (
        <span className={`text-xs ${positive ? 'text-green-600' : 'text-red-500'}`}>
            {positive ? '+' : ''}
            {value} ce mois
        </span>
    );
}

// Phase colour → dot classes, matching ServiceOrderStatus::color().
const PHASE_DOT: Record<string, string> = {
    gray: 'bg-gray-400',
    blue: 'bg-blue-500',
    yellow: 'bg-yellow-500',
    orange: 'bg-orange-500',
    purple: 'bg-purple-500',
    cyan: 'bg-cyan-500',
    green: 'bg-green-500',
    red: 'bg-red-500',
};

function WorkshopSection({ workshop }: { workshop: WorkshopStats }) {
    const workshopUrl = route('workshop.index');

    return (
        <div className="rounded-lg border border-border bg-card p-5 shadow-sm">
            <div className="mb-4 flex items-center justify-between">
                <h3 className="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                    Atelier
                    <span className="ms-2 text-muted-foreground/70">
                        {workshop.active} actif{workshop.active > 1 ? 's' : ''}
                    </span>
                </h3>
                <Link
                    href={workshopUrl}
                    className="inline-flex min-h-[44px] items-center text-xs font-medium text-primary hover:underline"
                >
                    Voir l'atelier →
                </Link>
            </div>

            {/* 2 col mobile/tablet, 4 col desktop */}
            <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                {workshop.by_status.map((phase) => (
                    <Link
                        key={phase.value}
                        href={`${workshopUrl}?status=${phase.value}`}
                        className="rounded-lg border border-border bg-muted/40 p-3 transition hover:bg-muted"
                    >
                        <div className="flex items-center gap-2">
                            <span
                                className={`h-2 w-2 flex-shrink-0 rounded-full ${PHASE_DOT[phase.color] ?? PHASE_DOT.gray}`}
                            />
                            <span className="truncate text-xs font-medium text-muted-foreground">
                                {phase.label}
                            </span>
                        </div>
                        <p className="mt-1 text-2xl font-bold text-foreground">{phase.count}</p>
                    </Link>
                ))}
                <div className="rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-900 dark:bg-green-950">
                    <div className="flex items-center gap-2">
                        <span className="h-2 w-2 flex-shrink-0 rounded-full bg-green-500" />
                        <span className="truncate text-xs font-medium text-green-700 dark:text-green-400">
                            Clôturés aujourd'hui
                        </span>
                    </div>
                    <p className="mt-1 text-2xl font-bold text-foreground">
                        {workshop.closed_today}
                    </p>
                </div>
            </div>
        </div>
    );
}

interface KpiCardProps {
    label: string;
    value: ReactNode;
    sub?: ReactNode;
    href?: string;
}

function KpiCard({ label, value, sub, href }: KpiCardProps) {
    const inner = (
        <>
            <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                {label}
            </p>
            <p className="mt-1 text-2xl font-bold tabular-nums text-foreground">{value}</p>
            {sub && <div className="mt-1">{sub}</div>}
        </>
    );

    if (href) {
        return (
            <Link
                href={href}
                className="block rounded-lg border border-border bg-card p-5 shadow-sm transition-shadow hover:shadow-md"
            >
                {inner}
            </Link>
        );
    }

    return <div className="rounded-lg border border-border bg-card p-5 shadow-sm">{inner}</div>;
}

interface SectionProps {
    title: string;
    href?: string;
    children: ReactNode;
}

function Section({ title, href, children }: SectionProps) {
    return (
        <div className="rounded-lg border border-border bg-card p-5 shadow-sm">
            <div className="mb-3 flex items-center justify-between">
                <h3 className="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                    {title}
                </h3>
                {href && (
                    <Link
                        href={href}
                        className="inline-flex min-h-[44px] items-center text-xs font-medium text-primary hover:underline"
                    >
                        Voir tout →
                    </Link>
                )}
            </div>
            {children}
        </div>
    );
}
