import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { ChartContainer, ChartTooltip, ChartTooltipContent } from '@/Components/ui/chart';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
import { useIsMobile } from '@/hooks/use-mobile';
import * as React from 'react';
import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from 'recharts';

const chartConfig = {
    revenue: {
        label: 'CA (DH)',
        color: 'hsl(var(--chart-1))',
    },
    appointments: {
        label: 'RDV',
        color: 'hsl(var(--chart-2))',
    },
};

const RANGES = [
    { value: '12m', label: '12 derniers mois', months: 12 },
    { value: '6m', label: '6 derniers mois', months: 6 },
    { value: '3m', label: '3 derniers mois', months: 3 },
];

function monthLabel(value) {
    return new Date(`${value}-01`).toLocaleDateString('fr-FR', {
        month: 'short',
        year: '2-digit',
    });
}

function fmt(value) {
    return Number(value).toLocaleString('fr-MA', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

export function ChartAreaInteractive({ data }) {
    const isMobile = useIsMobile();
    const [selectedRange, setSelectedRange] = React.useState(null);
    const timeRange = selectedRange ?? (isMobile ? '3m' : '12m');
    const setTimeRange = setSelectedRange;

    const months = RANGES.find((r) => r.value === timeRange)?.months ?? 12;
    const filteredData = data.slice(-months);

    return (
        <Card className="@container/card">
            <CardHeader className="relative">
                <CardTitle>Évolution mensuelle</CardTitle>
                <CardDescription>
                    <span className="hidden @[540px]/card:block">
                        Chiffre d'affaires et rendez-vous par mois
                    </span>
                    <span className="@[540px]/card:hidden">CA et RDV par mois</span>
                </CardDescription>
                <div className="absolute end-4 top-4">
                    <ToggleGroup
                        type="single"
                        value={timeRange}
                        onValueChange={(value) => value && setTimeRange(value)}
                        variant="outline"
                        className="hidden @[767px]/card:flex"
                    >
                        {RANGES.map((range) => (
                            <ToggleGroupItem
                                key={range.value}
                                value={range.value}
                                className="h-8 px-2.5"
                            >
                                {range.label}
                            </ToggleGroupItem>
                        ))}
                    </ToggleGroup>
                    <Select value={timeRange} onValueChange={setTimeRange}>
                        <SelectTrigger
                            className="flex w-44 @[767px]/card:hidden"
                            aria-label="Choisir une période"
                        >
                            <SelectValue placeholder="12 derniers mois" />
                        </SelectTrigger>
                        <SelectContent className="rounded-xl">
                            {RANGES.map((range) => (
                                <SelectItem
                                    key={range.value}
                                    value={range.value}
                                    className="rounded-lg"
                                >
                                    {range.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </CardHeader>
            <CardContent className="px-2 pt-4 sm:px-6 sm:pt-6">
                <ChartContainer config={chartConfig} className="aspect-auto h-[250px] w-full">
                    <AreaChart data={filteredData}>
                        <defs>
                            <linearGradient id="fillRevenue" x1="0" y1="0" x2="0" y2="1">
                                <stop
                                    offset="5%"
                                    stopColor="var(--color-revenue)"
                                    stopOpacity={0.8}
                                />
                                <stop
                                    offset="95%"
                                    stopColor="var(--color-revenue)"
                                    stopOpacity={0.1}
                                />
                            </linearGradient>
                            <linearGradient id="fillAppointments" x1="0" y1="0" x2="0" y2="1">
                                <stop
                                    offset="5%"
                                    stopColor="var(--color-appointments)"
                                    stopOpacity={0.8}
                                />
                                <stop
                                    offset="95%"
                                    stopColor="var(--color-appointments)"
                                    stopOpacity={0.1}
                                />
                            </linearGradient>
                        </defs>
                        <CartesianGrid vertical={false} />
                        <XAxis
                            dataKey="month"
                            tickLine={false}
                            axisLine={false}
                            tickMargin={8}
                            minTickGap={32}
                            tickFormatter={monthLabel}
                        />
                        <YAxis yAxisId="revenue" hide />
                        <YAxis yAxisId="appointments" hide />
                        <ChartTooltip
                            cursor={false}
                            content={
                                <ChartTooltipContent
                                    labelFormatter={monthLabel}
                                    formatter={(value, name) => (
                                        <>
                                            <span className="text-muted-foreground">
                                                {chartConfig[name]?.label ?? name}
                                            </span>
                                            <span className="ms-auto font-mono font-medium tabular-nums text-foreground">
                                                {name === 'revenue' ? `${fmt(value)} DH` : value}
                                            </span>
                                        </>
                                    )}
                                    indicator="dot"
                                />
                            }
                        />
                        <Area
                            yAxisId="appointments"
                            dataKey="appointments"
                            type="natural"
                            fill="url(#fillAppointments)"
                            stroke="var(--color-appointments)"
                        />
                        <Area
                            yAxisId="revenue"
                            dataKey="revenue"
                            type="natural"
                            fill="url(#fillRevenue)"
                            stroke="var(--color-revenue)"
                        />
                    </AreaChart>
                </ChartContainer>
            </CardContent>
        </Card>
    );
}
