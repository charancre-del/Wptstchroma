import React from 'react';
import { Link } from 'react-router-dom';
import { useReports, useStats } from '@hooks/useQueries';
import { useAuthStore } from '@stores';
import ComplianceChart from '@components/dashboard/ComplianceChart';
import NeedsAttention from '@components/dashboard/NeedsAttention';
import {
    FileText,
    School,
    FilePlus,
    Clock,
    CheckCircle,
    AlertTriangle,
    ArrowRight,
    Settings,
    BarChart3
} from 'lucide-react';

function StatCard({ icon: Icon, label, value, color = 'cqa-primary', to }) {
    const content = (
        <div className="bg-white rounded-xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div className={`absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity text-${color}`}>
                <Icon size={48} />
            </div>
            <div className="flex items-start gap-4 relaltive z-10">
                <div className={`w-12 h-12 rounded-xl bg-${color}/10 flex items-center justify-center shrink-0`}>
                    <Icon className={`w-6 h-6 text-${color}`} />
                </div>
                <div>
                    <h3 className="text-3xl font-bold text-gray-900">{value}</h3>
                    <p className="text-sm text-gray-500 font-medium">{label}</p>
                </div>
            </div>
        </div>
    );

    return to ? <Link to={to} className="block h-full">{content}</Link> : content;
}

export function Dashboard() {
    const { user, hasCapability } = useAuthStore();
    const { data: reports, isLoading: reportsLoading } = useReports({ limit: 5 });
    const { data: stats, isLoading: statsLoading } = useStats();

    // Use stats data or fallback
    const totalSchools = stats?.total_schools || 0;
    const compliantSchools = stats?.compliant_schools || 0;
    const overdueVisits = stats?.overdue_visits || 0;
    const myReports = stats?.my_reports || 0;

    const recentReports = Array.isArray(reports) ? reports : (reports?.data || []);

    return (
        <div className="p-6 max-w-7xl mx-auto space-y-8">
            {/* Header */}
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">
                        Welcome back, {user?.name?.split(' ')[0] || 'User'}
                    </h1>
                    <p className="text-gray-500 mt-1">
                        Here's what's happening in your district today.
                    </p>
                </div>
                {/* Optional Date/Time Widget could go here */}
            </div>

            {/* Stat Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <StatCard
                    icon={School}
                    label="Total Schools"
                    value={statsLoading ? '...' : totalSchools}
                    color="indigo-600"
                    to="/schools"
                />
                <StatCard
                    icon={CheckCircle}
                    label="Compliant Schools"
                    value={statsLoading ? '...' : compliantSchools}
                    color="emerald-500"
                />
                <StatCard
                    icon={AlertTriangle}
                    label="Overdue Visits"
                    value={statsLoading ? '...' : overdueVisits}
                    color="amber-500"
                />
                <StatCard
                    icon={BarChart3}
                    label="My Total Reports"
                    value={statsLoading ? '...' : myReports}
                    color="blue-500"
                />
            </div>

            {/* Main Content Grid */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Left Column: Compliance Chart (2/3) */}
                <div className="lg:col-span-2">
                    <ComplianceChart stats={stats} isLoading={statsLoading} />
                </div>

                {/* Right Column: Needs Attention (1/3) */}
                <div className="lg:col-span-1">
                    <NeedsAttention stats={stats} isLoading={statsLoading} />
                </div>
            </div>

            {/* Bottom Row: Recent Reports & Quick Actions */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Recent Reports List (2 cols) */}
                <div className="lg:col-span-2 space-y-4">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <FileText className="w-5 h-5 text-gray-500" />
                            Recent Reports
                        </h2>
                        <Link to="/reports" className="text-sm font-medium text-cqa-primary hover:text-primary-700">
                            View All
                        </Link>
                    </div>

                    <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                        {reportsLoading ? (
                            <div className="p-8 text-center text-gray-400">Loading reports...</div>
                        ) : recentReports.length > 0 ? (
                            <div className="divide-y divide-gray-100">
                                {recentReports.map(report => (
                                    <div key={report.id} className="p-4 hover:bg-gray-50 transition-colors flex items-center justify-between group">
                                        <div>
                                            <h4 className="font-semibold text-gray-900">{report.school_name}</h4>
                                            <div className="flex items-center gap-2 text-sm text-gray-500 mt-1">
                                                <span>Tier {report.tier || 1}</span>
                                                <span>•</span>
                                                <span>{new Date(report.date || report.created_at).toLocaleDateString()}</span>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <span className={`px-2.5 py-0.5 rounded-full text-xs font-medium capitalize 
                                                ${report.status === 'approved' ? 'bg-green-100 text-green-700' :
                                                    report.status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600'}`}>
                                                {report.status}
                                            </span>
                                            {report.rating && (
                                                <span className={`px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                    ${report.rating.includes('Exceeds') ? 'bg-emerald-100 text-emerald-700' :
                                                        report.rating.includes('Meets') ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700'}`}>
                                                    {report.rating.replace(' Expectations', '')}
                                                </span>
                                            )}

                                            <div className="opacity-0 group-hover:opacity-100 transition-opacity flex gap-2">
                                                <Link to={`/reports/${report.id}`} className="px-3 py-1 bg-white border border-gray-200 rounded text-sm text-gray-600 hover:border-gray-400">
                                                    View
                                                </Link>
                                                {report.status === 'draft' && (
                                                    <Link to={`/edit/${report.id}`} className="px-3 py-1 bg-cqa-primary text-white rounded text-sm hover:bg-primary-700">
                                                        Edit
                                                    </Link>
                                                )}
                                                {report.status === 'approved' && report.rating !== 'Exceeds Expectations' && (
                                                    <Link to={`/create?school=${report.school_id}`} className="px-3 py-1 bg-cqa-primary text-white rounded text-sm hover:bg-primary-700">
                                                        Continue
                                                    </Link>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="p-8 text-center text-gray-500">
                                No recent reports found.
                            </div>
                        )}
                    </div>
                </div>

                {/* Quick Actions (1 col) */}
                <div className="lg:col-span-1 space-y-4">
                    <h2 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <Clock className="w-5 h-5 text-gray-500" />
                        Quick Actions
                    </h2>

                    <div className="bg-white rounded-xl border border-gray-100 shadow-sm p-4 space-y-3">
                        {hasCapability('cqa_create_reports') && (
                            <Link to="/create" className="flex items-center gap-4 p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                                <div className="w-10 h-10 rounded-lg bg-indigo-50 text-cqa-primary flex items-center justify-center group-hover:bg-cqa-primary group-hover:text-white transition-colors">
                                    <FilePlus size={20} />
                                </div>
                                <span className="font-medium text-gray-700 group-hover:text-gray-900">Start New Report</span>
                            </Link>
                        )}

                        <Link to="/schools" className="flex items-center gap-4 p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                            <div className="w-10 h-10 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center group-hover:bg-orange-600 group-hover:text-white transition-colors">
                                <School size={20} />
                            </div>
                            <span className="font-medium text-gray-700 group-hover:text-gray-900">Manage Schools</span>
                        </Link>

                        <Link to="/settings" className="flex items-center gap-4 p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                            <div className="w-10 h-10 rounded-lg bg-gray-50 text-gray-600 flex items-center justify-center group-hover:bg-gray-600 group-hover:text-white transition-colors">
                                <Settings size={20} />
                            </div>
                            <span className="font-medium text-gray-700 group-hover:text-gray-900">Settings</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default Dashboard;
