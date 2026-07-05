import { brandAccent, brandPrimary } from '@/constants/brand';
import { useMemo } from 'react';
import {
    CartesianGrid,
    Legend,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

interface MonthlyDatum {
    month: string;
    revenue: number;
    appointments: number;
}

interface LineChartCardProps {
    title: string;
    data: MonthlyDatum[];
}

function revenueFormatter(value: number | string): string {
    const num = Number(value);
    if (num >= 1000)
        return (
            Number(num / 1000).toLocaleString('fr-MA', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 1,
            }) + 'k'
        );
    return String(value);
}

export default function LineChartCard({ title, data }: LineChartCardProps) {
    // Theme is fixed per page load, so read the CSS-var colours once.
    const LINES = useMemo(
        () => [
            { key: 'revenue', label: 'CA (DH)', color: brandPrimary(), yAxisId: 'revenue' },
            { key: 'appointments', label: 'RDV', color: brandAccent(), yAxisId: 'appointments' },
        ],
        [],
    );

    return (
        <div className="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
            <h3 className="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">
                {title}
            </h3>

            {data.length === 0 ? (
                <p className="text-sm text-gray-500">Aucune donnée.</p>
            ) : (
                <ResponsiveContainer width="100%" height={220}>
                    <LineChart data={data} margin={{ top: 4, right: 8, left: 0, bottom: 0 }}>
                        <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                        <XAxis
                            dataKey="month"
                            tick={{ fontSize: 10, fill: '#6b7280' }}
                            axisLine={false}
                            tickLine={false}
                            interval="preserveStartEnd"
                        />
                        <YAxis
                            yAxisId="revenue"
                            orientation="left"
                            tickFormatter={revenueFormatter}
                            tick={{ fontSize: 10, fill: '#6b7280' }}
                            axisLine={false}
                            tickLine={false}
                            width={40}
                        />
                        <YAxis
                            yAxisId="appointments"
                            orientation="right"
                            allowDecimals={false}
                            tick={{ fontSize: 10, fill: '#6b7280' }}
                            axisLine={false}
                            tickLine={false}
                            width={28}
                        />
                        <Tooltip
                            contentStyle={{
                                fontSize: 12,
                                borderRadius: 6,
                                border: '1px solid #e5e7eb',
                            }}
                            formatter={(value, name, item) =>
                                item?.dataKey === 'revenue'
                                    ? [
                                          Number(value).toLocaleString('fr-MA', {
                                              minimumFractionDigits: 2,
                                              maximumFractionDigits: 2,
                                          }) + ' DH',
                                          name,
                                      ]
                                    : [value, name]
                            }
                        />
                        <Legend
                            wrapperStyle={{ fontSize: 11, paddingTop: 8 }}
                            iconType="circle"
                            iconSize={8}
                        />
                        {LINES.map(({ key, label, color, yAxisId }) => (
                            <Line
                                key={key}
                                yAxisId={yAxisId}
                                type="monotone"
                                dataKey={key}
                                name={label}
                                stroke={color}
                                strokeWidth={2}
                                dot={false}
                                activeDot={{ r: 4 }}
                            />
                        ))}
                    </LineChart>
                </ResponsiveContainer>
            )}
        </div>
    );
}
