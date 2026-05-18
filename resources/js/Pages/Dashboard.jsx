import BarChartCard from '@/Components/Charts/BarChartCard';
import LineChartCard from '@/Components/Charts/LineChartCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Dashboard({ kpis, isAdmin }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
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
                        <KpiCard label="Rappels en attente" value={kpis.pending_reminders} />
                        <KpiCard label="RDV aujourd'hui" value={kpis.appointments_today.length} />
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

                    {/* ── Lists — 1 col mobile, 2 col desktop ── */}
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <Section title="RDV du jour">
                            {kpis.appointments_today.length === 0 ? (
                                <p className="text-sm text-gray-500">
                                    Aucun rendez-vous aujourd'hui.
                                </p>
                            ) : (
                                <ul className="divide-y divide-gray-100">
                                    {kpis.appointments_today.map((a) => (
                                        <li
                                            key={a.id}
                                            className="flex items-center justify-between py-2 text-sm"
                                        >
                                            <div>
                                                <p className="font-medium text-gray-800">
                                                    {a.customer}
                                                </p>
                                                <p className="text-xs text-gray-500">{a.vehicle}</p>
                                                {a.services.length > 0 && (
                                                    <p className="text-xs text-gray-400">
                                                        {a.services.join(', ')}
                                                    </p>
                                                )}
                                            </div>
                                            <span className="ml-4 text-gray-600">
                                                {a.scheduled_at}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Section>

                        <Section title="Stock bas">
                            {kpis.low_stock.length === 0 ? (
                                <p className="text-sm text-gray-500">Aucun produit en stock bas.</p>
                            ) : (
                                <ul className="divide-y divide-gray-100">
                                    {kpis.low_stock.map((p) => (
                                        <li
                                            key={p.id}
                                            className="flex items-center justify-between py-2 text-sm"
                                        >
                                            <Link
                                                href={route('products.edit', p.id)}
                                                className="font-medium text-gray-800 hover:text-blue-600"
                                            >
                                                {p.name}
                                            </Link>
                                            <span className="text-red-600">
                                                {p.quantity_on_hand} / {p.low_stock_threshold}{' '}
                                                {p.unit}
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
                                data={kpis.top_services}
                                dataKey="count"
                                nameKey="name"
                            />
                            <div className="lg:col-span-2">
                                <LineChartCard
                                    title="Évolution mensuelle (12 mois)"
                                    data={kpis.monthly_stats}
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

function fmt(value) {
    return Number(value).toLocaleString('fr-MA', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function Delta({ value }) {
    if (value === 0) return <span className="text-xs text-gray-400">= ce mois</span>;
    const positive = value > 0;
    return (
        <span className={`text-xs ${positive ? 'text-green-600' : 'text-red-500'}`}>
            {positive ? '+' : ''}
            {value} ce mois
        </span>
    );
}

function KpiCard({ label, value, sub }) {
    return (
        <div className="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
            <p className="text-xs font-medium uppercase tracking-wide text-gray-500">{label}</p>
            <p className="mt-1 text-2xl font-bold text-gray-900">{value}</p>
            {sub && <div className="mt-1">{sub}</div>}
        </div>
    );
}

function Section({ title, children }) {
    return (
        <div className="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
            <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">
                {title}
            </h3>
            {children}
        </div>
    );
}
