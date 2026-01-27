import React, { useMemo } from 'react';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { Loader2, TrendingUp } from 'lucide-react';

const ComplianceChart = ({ stats, isLoading }) => {

    // Safe Mock Data generator since backend API might not return timeseries yet
    const data = useMemo(() => {
        if (stats?.trend) return stats.trend;

        // Mock data for UI development availability
        return [
            { name: 'Jan', score: 85 },
            { name: 'Feb', score: 88 },
            { name: 'Mar', score: 82 },
            { name: 'Apr', score: 90 },
            { name: 'May', score: 95 },
            { name: 'Jun', score: 94 },
            { name: 'Jul', score: 98 },
        ];
    }, [stats]);

    if (isLoading) {
        return (
            <div className="bg-white p-8 rounded-3xl border border-brand-ink/5 shadow-sm h-96 flex items-center justify-center">
                <Loader2 className="w-8 h-8 text-chroma-blue animate-spin" />
            </div>
        );
    }

    return (
        <div className="bg-white p-8 rounded-3xl border border-brand-ink/5 shadow-sm h-full flex flex-col">
            <h3 className="text-2xl font-serif font-bold text-brand-ink mb-6 flex items-center gap-2">
                <div className="w-8 h-8 rounded-lg bg-chroma-green/10 flex items-center justify-center text-chroma-green">
                    <TrendingUp size={18} />
                </div>
                Compliance Trend
            </h3>

            <div className="flex-1 w-full min-h-[300px]">
                <ResponsiveContainer width="100%" height={300}>
                    <AreaChart data={data} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                        <defs>
                            <linearGradient id="colorScore" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%" stopColor="#4A6C7C" stopOpacity={0.2} />
                                <stop offset="95%" stopColor="#4A6C7C" stopOpacity={0} />
                            </linearGradient>
                        </defs>
                        <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f0f0f0" />
                        <XAxis
                            dataKey="name"
                            axisLine={false}
                            tickLine={false}
                            tick={{ fill: '#9CA3AF', fontSize: 12, fontFamily: 'Outfit' }}
                            dy={10}
                        />
                        <YAxis
                            axisLine={false}
                            tickLine={false}
                            tick={{ fill: '#9CA3AF', fontSize: 12, fontFamily: 'Outfit' }}
                        />
                        <Tooltip
                            contentStyle={{
                                backgroundColor: '#fff',
                                border: '1px solid #f3f4f6',
                                borderRadius: '12px',
                                boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)',
                                fontFamily: 'Outfit'
                            }}
                            itemStyle={{ color: '#263238', fontWeight: 600 }}
                            cursor={{ stroke: '#4A6C7C', strokeWidth: 1, strokeDasharray: '4 4' }}
                        />
                        <Area
                            type="monotone"
                            dataKey="score"
                            stroke="#4A6C7C"
                            strokeWidth={3}
                            fillOpacity={1}
                            fill="url(#colorScore)"
                        />
                    </AreaChart>
                </ResponsiveContainer>
            </div>
        </div>
    );
};

export default ComplianceChart;
