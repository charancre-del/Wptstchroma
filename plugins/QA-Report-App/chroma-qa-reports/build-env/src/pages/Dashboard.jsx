import React from 'react';
import { Link } from 'react-router-dom';
import { useReports, useStats } from '@hooks/useQueries';
import { useAuthStore } from '@stores';
import ComplianceChart from '@components/dashboard/ComplianceChart';
import ActionItems from '@components/dashboard/ActionItems';
import StatCard from '@components/dashboard/StatCard';
import Skeleton from '@components/common/Skeleton';
import {
    FileText,
    School,
    CheckCircle,
    AlertTriangle,
    BarChart3,
    ArrowRight
} from 'lucide-react';

export function Dashboard() {
    const { user } = useAuthStore();
    const { data: reports, isLoading: reportsLoading } = useReports({ limit: 5 });
    const { data: stats, isLoading: statsLoading } = useStats();

    // Use stats data or fallback
    const totalSchools = stats?.total_schools || 0;
    const compliantSchools = stats?.compliant_schools || 0;
    const overdueVisits = stats?.overdue_visits || 0;
    const myReports = stats?.my_reports || 0;

    const recentReports = Array.isArray(reports) ? reports : (reports?.data || []);

    return (
        <div className="space-y-8">
            {/* Header */}
            <div className="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h1 className="text-4xl font-serif font-bold text-brand-ink">
                        Welcome back, {user?.name?.split(' ')[0] || 'User'}
                    </h1>
                    <p className="text-brand-ink/60 mt-2 font-medium">
                        Here's what's happening in your district today.
                    </p>
                </div>
                <div className="text-right hidden md:block">
                    <p className="text-sm font-bold text-brand-ink/40 uppercase tracking-widest">{new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' })}</p>
                </div>
            </div>

            {/* Stat Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 h-60">
                <StatCard
                    icon={School}
                    label="Total Schools"
                    value={statsLoading ? <Skeleton className="h-10 w-16" /> : totalSchools}
                    variant="blue"
                    to="/schools"
                />
                <StatCard
                    icon={CheckCircle}
                    label="Compliant Schools"
                    value={statsLoading ? <Skeleton className="h-10 w-16" /> : compliantSchools}
                    variant="green"
                    to="/reports?status=approved&rating=meets"
                />
                <StatCard
                    icon={AlertTriangle}
                    label="Overdue Visits"
                    value={statsLoading ? <Skeleton className="h-10 w-16" /> : overdueVisits}
                    variant="yellow"
                    to="/schools?status=overdue"
                />
                <StatCard
                    icon={BarChart3}
                    label="My Reports"
                    value={statsLoading ? <Skeleton className="h-10 w-16" /> : myReports}
                    variant="red"
                    to="/reports?author=me"
                />
            </div>

            {/* Main Content Grid */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 h-[500px]">
                {/* Left Column: Compliance Chart (2/3) */}
                <div className="lg:col-span-2 h-full">
                    <ComplianceChart stats={stats} isLoading={statsLoading} />
                </div>

                {/* Right Column: Action Items (1/3) */}
                <div className="lg:col-span-1 h-full">
                    <ActionItems stats={stats} isLoading={statsLoading} />
                </div>
            </div>

            {/* Bottom Row: Recent Reports */}
            <section className="bg-white rounded-3xl border border-brand-ink/5 shadow-sm p-8">
                <div className="flex items-center justify-between mb-6">
                    <h2 className="text-2xl font-serif font-bold text-brand-ink flex items-center gap-3">
                        <div className="w-10 h-10 rounded-xl bg-brand-ink/5 flex items-center justify-center text-brand-ink">
                            <FileText size={20} />
                        </div>
                        Recent Reports
                    </h2>
                    <Link to="/reports" className="flex items-center gap-2 text-sm font-bold text-chroma-blue hover:text-chroma-blue/80 transition-colors">
                        View All Reports <ArrowRight size={16} />
                    </Link>
                </div>

                <div className="overflow-hidden">
                    {reportsLoading ? (
                        <div className="space-y-4">
                            {[1, 2, 3].map(i => (
                                <div key={i} className="p-4 rounded-xl border border-brand-ink/5 flex items-center justify-between">
                                    <div className="flex items-center gap-4">
                                        <Skeleton className="w-12 h-12 rounded-full" />
                                        <div className="space-y-2">
                                            <Skeleton className="h-5 w-48" />
                                            <Skeleton className="h-3 w-32" />
                                        </div>
                                    </div>
                                    <Skeleton className="h-8 w-24 rounded-full" />
                                </div>
                            ))}
                        </div>
                    ) : recentReports.length > 0 ? (
                        <div className="grid gap-4">
                            {recentReports.map(report => (
                                <div key={report.id} className="p-4 rounded-xl bg-brand-cream/50 border border-brand-ink/5 hover:border-chroma-blue/20 hover:shadow-md transition-all flex items-center justify-between group">
                                    <div className="flex items-center gap-4">
                                        <div className={`w-12 h-12 rounded-full flex items-center justify-center font-bold text-white text-lg ${report.status === 'approved' ? 'bg-chroma-green' :
                                            report.status === 'pending' ? 'bg-chroma-yellow' : 'bg-brand-ink/20'
                                            }`}>
                                            {report.school_name.charAt(0)}
                                        </div>
                                        <div>
                                            <h4 className="font-bold text-brand-ink text-lg">{report.school_name}</h4>
                                            <div className="flex items-center gap-2 text-xs font-bold text-brand-ink/40 uppercase tracking-wide mt-1">
                                                <span>Tier {report.tier !== undefined ? report.tier : 1}</span>
                                                <span>•</span>
                                                <span>{new Date(report.date || report.created_at).toLocaleDateString()}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-4">
                                        {report.rating && (
                                            <span className={`px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider 
                                                ${report.rating.includes('Exceeds') ? 'bg-chroma-green/10 text-chroma-green' :
                                                    report.rating.includes('Meets') ? 'bg-chroma-blue/10 text-chroma-blue' : 'bg-chroma-red/10 text-chroma-red'}`}>
                                                {report.rating.replace(' Expectations', '')}
                                            </span>
                                        )}

                                        <Link to={`/reports/${report.id}`} className="w-10 h-10 rounded-full bg-white border border-brand-ink/10 flex items-center justify-center text-brand-ink/40 hover:text-chroma-blue hover:border-chroma-blue transition-all">
                                            <ArrowRight size={18} />
                                        </Link>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="p-12 text-center text-brand-ink/40 font-bold border-2 border-dashed border-brand-ink/5 rounded-2xl">
                            No recent reports found.
                        </div>
                    )}
                </div>
            </section>
        </div>
    );
}

export default Dashboard;
