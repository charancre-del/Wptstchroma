import React, { useMemo } from 'react';
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip, Legend } from 'recharts';
import { Loader2, TrendingUp } from 'lucide-react';

const ComplianceChart = ({ stats, isLoading }) => {

    const data = useMemo(() => {
        if (!stats?.compliance) return [];
        return [
            { name: 'Exceeds', value: Number(stats.compliance.exceeds || 0), color: '#10B981' }, // Emerald-500
            { name: 'Meets', value: Number(stats.compliance.meets || 0), color: '#3B82F6' },    // Blue-500
            { name: 'Needs Improvement', value: Number(stats.compliance.improvement || 0), color: '#EF4444' }, // Red-500
        ].filter(item => item.value > 0);
    }, [stats]);

    if (isLoading) {
        return (
            <div className="bg-white p-6 rounded-xl border border-gray-100 shadow-sm h-80 flex items-center justify-center">
                <Loader2 className="w-8 h-8 text-primary-600 animate-spin" />
            </div>
        );
    }

    const totalReports = data.reduce((acc, curr) => acc + curr.value, 0);

    return (
        <div className="bg-white p-6 rounded-xl border border-gray-100 shadow-sm h-full flex flex-col">
            <h3 className="text-lg font-bold text-gray-900 mb-2 flex items-center gap-2">
                <TrendingUp className="w-5 h-5 text-gray-500" />
                Compliance Overview
            </h3>

            <div className="flex-1 w-full min-h-[250px] relative">
                {totalReports > 0 ? (
                    <ResponsiveContainer width="100%" height="100%">
                        <PieChart>
                            <Pie
                                data={data}
                                cx="50%"
                                cy="50%"
                                innerRadius={60}
                                outerRadius={80}
                                paddingAngle={5}
                                dataKey="value"
                            >
                                {data.map((entry, index) => (
                                    <Cell key={`cell-${index}`} fill={entry.color} strokeWidth={0} />
                                ))}
                            </Pie>
                            <Tooltip
                                contentStyle={{ borderRadius: '8px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }}
                                itemStyle={{ fontWeight: 600 }}
                            />
                            <Legend
                                verticalAlign="middle"
                                align="right"
                                layout="vertical"
                                iconType="circle"
                                formatter={(value, entry) => (
                                    <span className="text-sm text-gray-600 font-medium ml-1">
                                        {value} ({entry.payload.value})
                                    </span>
                                )}
                            />
                        </PieChart>
                    </ResponsiveContainer>
                ) : (
                    <div className="absolute inset-0 flex items-center justify-center text-gray-400 text-sm flex-col gap-2">
                        <div className="w-32 h-32 rounded-full border-4 border-gray-100 border-dashed"></div>
                        <p>No data available</p>
                    </div>
                )}
            </div>
        </div>
    );
};

export default ComplianceChart;
