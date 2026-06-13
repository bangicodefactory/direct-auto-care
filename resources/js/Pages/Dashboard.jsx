import { ChartAreaInteractive } from '@/Components/chart-area-interactive';
import { SectionCards } from '@/Components/section-cards';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { ChartContainer } from '@/Components/ui/chart';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { Bar, BarChart, XAxis, YAxis } from 'recharts';

export default function Dashboard({ kpis, isAdmin }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-foreground">
                    Tableau de bord
                </h2>
            }
        >
            <Head title="Tableau de bord" />

            <div className="@container/main flex flex-1 flex-col gap-2">
                <div className="flex flex-col gap-4 py-4 md:gap-6 md:py-6">
                    <SectionCards kpis={kpis} isAdmin={isAdmin} />

                    {isAdmin && (
                        <div className="px-4 lg:px-6">
                            <ChartAreaInteractive data={kpis.monthly_stats} />
                        </div>
                    )}

                    <div className="grid grid-cols-1 gap-4 px-4 @4xl/main:grid-cols-2 lg:px-6">
                        <TodayAppointmentsCard appointments={kpis.appointments_today} />
                        <LowStockCard products={kpis.low_stock} />
                    </div>

                    {isAdmin && (
                        <div className="px-4 lg:px-6">
                            <TopServicesCard services={kpis.top_services} />
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

// ── Sections ─────────────────────────────────────────────────────────────────

function SectionCard({ title, description, href, children }) {
    return (
        <Card className="@container/card">
            <CardHeader className="relative">
                <CardTitle className="text-base">{title}</CardTitle>
                {description && <CardDescription>{description}</CardDescription>}
                {href && (
                    <Link
                        href={href}
                        className="absolute end-4 top-4 text-xs font-medium text-secondary hover:underline"
                    >
                        Voir tout →
                    </Link>
                )}
            </CardHeader>
            <CardContent>{children}</CardContent>
        </Card>
    );
}

function TodayAppointmentsCard({ appointments }) {
    return (
        <SectionCard
            title="RDV du jour"
            description="Rendez-vous planifiés aujourd'hui"
            href={route('appointments.today-page')}
        >
            {appointments.length === 0 ? (
                <p className="text-sm text-muted-foreground">Aucun rendez-vous aujourd'hui.</p>
            ) : (
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Client</TableHead>
                            <TableHead>Véhicule</TableHead>
                            <TableHead className="text-end">Heure</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {appointments.map((a) => (
                            <TableRow key={a.id}>
                                <TableCell className="font-medium">{a.customer}</TableCell>
                                <TableCell className="text-muted-foreground">
                                    {a.vehicle}
                                    {a.services.length > 0 && (
                                        <span className="block text-xs">
                                            {a.services.join(', ')}
                                        </span>
                                    )}
                                </TableCell>
                                <TableCell className="text-end text-muted-foreground">
                                    {a.scheduled_at}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            )}
        </SectionCard>
    );
}

function LowStockCard({ products }) {
    return (
        <SectionCard
            title="Stock bas"
            description="Produits sous le seuil d'alerte"
            href={route('products.index')}
        >
            {products.length === 0 ? (
                <p className="text-sm text-muted-foreground">Aucun produit en stock bas.</p>
            ) : (
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Produit</TableHead>
                            <TableHead className="text-end">Stock / Seuil</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {products.map((p) => (
                            <TableRow key={p.id}>
                                <TableCell>
                                    <Link
                                        href={route('products.edit', p.id)}
                                        className="font-medium hover:text-primary"
                                    >
                                        {p.name}
                                    </Link>
                                </TableCell>
                                <TableCell className="text-end">
                                    <Badge variant="destructive" className="tabular-nums">
                                        {p.quantity_on_hand} / {p.low_stock_threshold} {p.unit}
                                    </Badge>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            )}
        </SectionCard>
    );
}

const topServicesConfig = {
    count: {
        label: 'Prestations',
        color: 'hsl(var(--chart-2))',
    },
};

function TopServicesCard({ services }) {
    return (
        <SectionCard title="Top prestations" description="Les 5 prestations les plus réalisées">
            {services.length === 0 ? (
                <p className="text-sm text-muted-foreground">Aucune donnée.</p>
            ) : (
                <ChartContainer config={topServicesConfig} className="aspect-auto h-[200px] w-full">
                    <BarChart data={services} layout="vertical" margin={{ left: 0, right: 16 }}>
                        <XAxis type="number" hide />
                        <YAxis
                            dataKey="name"
                            type="category"
                            tickLine={false}
                            axisLine={false}
                            width={140}
                            tick={{ fontSize: 12 }}
                        />
                        <Bar
                            dataKey="count"
                            fill="var(--color-count)"
                            radius={4}
                            barSize={20}
                            label={{ position: 'right', fontSize: 12 }}
                        />
                    </BarChart>
                </ChartContainer>
            )}
        </SectionCard>
    );
}
