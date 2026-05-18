import { BRAND_PRIMARY } from '@/constants/brand';
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

export default function BarChartCard({ title, data, dataKey = 'count', nameKey = 'name' }) {
    return (
        <div className="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
            <h3 className="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">
                {title}
            </h3>

            {data.length === 0 ? (
                <p className="text-sm text-gray-500">Aucune donnée.</p>
            ) : (
                <ResponsiveContainer width="100%" height={220}>
                    <BarChart data={data} margin={{ top: 4, right: 8, left: -16, bottom: 0 }}>
                        <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f0f0f0" />
                        <XAxis
                            dataKey={nameKey}
                            tick={{ fontSize: 11, fill: '#6b7280' }}
                            axisLine={false}
                            tickLine={false}
                        />
                        <YAxis
                            allowDecimals={false}
                            tick={{ fontSize: 11, fill: '#6b7280' }}
                            axisLine={false}
                            tickLine={false}
                        />
                        <Tooltip
                            cursor={{ fill: '#f3f4f6' }}
                            contentStyle={{
                                fontSize: 12,
                                borderRadius: 6,
                                border: '1px solid #e5e7eb',
                            }}
                        />
                        <Bar
                            dataKey={dataKey}
                            fill={BRAND_PRIMARY}
                            radius={[4, 4, 0, 0]}
                            maxBarSize={48}
                        />
                    </BarChart>
                </ResponsiveContainer>
            )}
        </div>
    );
}
