import React, { useMemo } from 'react';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { Loader2, TrendingUp } from 'lucide-react';

const ComplianceChart = ( { stats, isLoading } ) => {
    const data = useMemo( () => {
        const trend = Array.isArray( stats?.trend )
            ? stats.trend
            : Array.isArray( stats?.trend?.data )
            ? stats.trend.data
            : [];

        return trend
            .map( ( point ) => ( {
                name: point?.name || '',
                score: Number( point?.score ),
            } ) )
            .filter( ( point ) => point.name && Number.isFinite( point.score ) );
    }, [ stats ] );

    const hasTrendData = data.length > 0;

    if ( isLoading ) {
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
                    <TrendingUp size={ 18 } />
                </div>
                Compliance Trend
            </h3>

            <div className="flex-1 w-full min-w-0">
                { hasTrendData ? (
                    <div className="h-[320px] md:h-[380px] w-full min-w-0" style={ { position: 'relative' } }>
                        <ResponsiveContainer width="100%" height="100%">
                            <AreaChart data={ data } margin={ { top: 10, right: 10, left: -20, bottom: 0 } }>
                                <defs>
                                    <linearGradient id="colorScore" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="5%" stopColor="#4A6C7C" stopOpacity={ 0.2 } />
                                        <stop offset="95%" stopColor="#4A6C7C" stopOpacity={ 0 } />
                                    </linearGradient>
                                </defs>
                                <CartesianGrid strokeDasharray="3 3" vertical={ false } stroke="#f0f0f0" />
                                <XAxis
                                    dataKey="name"
                                    axisLine={ false }
                                    tickLine={ false }
                                    tick={ { fill: '#9CA3AF', fontSize: 12, fontFamily: 'Outfit' } }
                                    dy={ 10 }
                                />
                                <YAxis
                                    axisLine={ false }
                                    tickLine={ false }
                                    tick={ { fill: '#9CA3AF', fontSize: 12, fontFamily: 'Outfit' } }
                                    domain={ [ 0, 100 ] }
                                />
                                <Tooltip
                                    contentStyle={ {
                                        backgroundColor: '#fff',
                                        border: '1px solid #f3f4f6',
                                        borderRadius: '12px',
                                        boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)',
                                        fontFamily: 'Outfit',
                                    } }
                                    formatter={ ( value ) => [ `${ value }%`, 'Compliance' ] }
                                    itemStyle={ { color: '#263238', fontWeight: 600 } }
                                    cursor={ { stroke: '#4A6C7C', strokeWidth: 1, strokeDasharray: '4 4' } }
                                />
                                <Area
                                    type="monotone"
                                    dataKey="score"
                                    stroke="#4A6C7C"
                                    strokeWidth={ 3 }
                                    fillOpacity={ 1 }
                                    fill="url(#colorScore)"
                                />
                            </AreaChart>
                        </ResponsiveContainer>
                    </div>
                ) : (
                    <div className="h-[320px] md:h-[380px] rounded-2xl border border-dashed border-brand-ink/10 bg-brand-cream/40 flex items-center justify-center text-center px-6">
                        <div>
                            <p className="text-lg font-bold text-brand-ink/70">No trend data available yet.</p>
                            <p className="text-sm text-brand-ink/45 mt-2">
                                Compliance trends will appear once approved reports with ratings are available.
                            </p>
                        </div>
                    </div>
                ) }
            </div>
        </div>
    );
};

export default ComplianceChart;
