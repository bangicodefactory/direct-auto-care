import { Badge } from '@/Components/ui/badge';
import { Card, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import { Link } from '@inertiajs/react';
import { TrendingDown, TrendingUp } from 'lucide-react';

const cardClass = '@container/card bg-gradient-to-t from-primary/5 to-card shadow-xs';

function fmt(value) {
    return Number(value).toLocaleString('fr-MA', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function TrendBadge({ value, suffix }) {
    if (value === null || value === undefined) return null;
    const positive = value >= 0;
    const Icon = positive ? TrendingUp : TrendingDown;
    return (
        <Badge variant="outline" className="flex gap-1 rounded-lg text-xs">
            <Icon className="size-3" />
            {positive ? '+' : ''}
            {value}
            {suffix}
        </Badge>
    );
}

function KpiCard({ label, value, badge, footer, footerHref }) {
    return (
        <Card className={cardClass}>
            <CardHeader className="relative">
                <CardDescription>{label}</CardDescription>
                <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                    {value}
                </CardTitle>
                {badge && <div className="absolute end-4 top-4">{badge}</div>}
            </CardHeader>
            {footer && (
                <CardFooter className="flex-col items-start gap-1 text-sm">
                    {footerHref ? (
                        <Link
                            href={footerHref}
                            className="line-clamp-1 flex gap-2 font-medium text-secondary hover:underline"
                        >
                            {footer} →
                        </Link>
                    ) : (
                        <div className="line-clamp-1 flex gap-2 font-medium text-muted-foreground">
                            {footer}
                        </div>
                    )}
                </CardFooter>
            )}
        </Card>
    );
}

export function SectionCards({ kpis, isAdmin }) {
    const yearDelta =
        kpis.revenue_last_year > 0
            ? Math.round(
                  ((kpis.revenue_year - kpis.revenue_last_year) / kpis.revenue_last_year) * 100,
              )
            : null;

    return (
        <div className="grid grid-cols-1 gap-4 px-4 @xl/main:grid-cols-2 @5xl/main:grid-cols-4 lg:px-6">
            <KpiCard
                label="Clients"
                value={kpis.customers_total}
                badge={<TrendBadge value={kpis.customers_delta} suffix=" ce mois" />}
                footer="Total clients enregistrés"
            />
            <KpiCard
                label="Véhicules actifs"
                value={kpis.active_vehicles}
                footer="Véhicules suivis au garage"
            />
            <KpiCard
                label="Rappels en attente"
                value={kpis.pending_reminders}
                footer="Voir les rappels"
                footerHref={route('reminders.index')}
            />
            <KpiCard
                label="RDV aujourd'hui"
                value={kpis.appointments_today.length}
                footer="Voir le planning du jour"
                footerHref={route('appointments.today-page')}
            />
            {isAdmin && (
                <>
                    <KpiCard
                        label="CA ce mois (DH)"
                        value={fmt(kpis.revenue_month)}
                        footer="Factures émises et payées"
                    />
                    <KpiCard
                        label="CA cette année (DH)"
                        value={fmt(kpis.revenue_year)}
                        badge={yearDelta !== null && <TrendBadge value={yearDelta} suffix="%" />}
                        footer="Par rapport à l'année dernière"
                    />
                    <KpiCard
                        label="CA année dernière (DH)"
                        value={fmt(kpis.revenue_last_year)}
                        footer="Année civile précédente"
                    />
                </>
            )}
        </div>
    );
}
