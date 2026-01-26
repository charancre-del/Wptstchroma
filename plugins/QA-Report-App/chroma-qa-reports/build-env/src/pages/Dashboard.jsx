import React from 'react';
import { Link } from 'react-router-dom';
import { useReports, useSchools } from '../hooks/useQueries';
import { useAuthStore } from '../stores';
import {
    FileText,
    School,
    FilePlus,
    Clock,
    CheckCircle,
    AlertCircle,
    ArrowRight
} from 'lucide-react';

function StatCard({ icon: Icon, label, value, color = 'primary', to }) {
    const content = (
        <div className={`bg-white rounded-xl p-6 border border-gray-200 hover:shadow-md transition-shadow`}>
            <div className="flex items-start justify-between">
                <div>
                    <p className="text-sm text-gray-600 mb-1">{label}</p>
                    <p className="text-3xl font-bold text-gray-900">{value}</p>
                </div>
                <div className={`w-12 h-12 rounded-xl bg-${color}-100 flex items-center justify-center`}>
                    <Icon className={`w-6 h-6 text-${color}-600`} />
                </div>
            </div>
        </div>
    );

    return to ? <Link to={to}>{content}</Link> : content;
}

export function Dashboard() {
    const { user, hasCapability } = useAuthStore();
    const { data: reports, isLoading: reportsLoading } = useReports({ limit: 10 });
    const { data: schools, isLoading: schoolsLoading } = useSchools();

    const recentReports = reports?.data || reports || [];
    const schoolCount = schools?.length || 0;

    const pendingCount = recentReports.filter(r => r.status === 'pending').length;
    const approvedCount = recentReports.filter(r => r.status === 'approved').length;

    return (
        <div className="p-6 max-w-7xl mx-auto">
            {/* Header */}
            <div className="mb-8">
                <h1 className="text-2xl font-bold text-gray-900">
                    Welcome back, {user?.name?.split(' ')[0] || 'User'}
                </h1>
                <p className="text-gray-600 mt-1">
                    Here's an overview of your QA reporting activity.
                </p>
            </div>

            {/* Stats Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <StatCard
                    icon={School}
                    label="Total Schools"
                    value={schoolsLoading ? '...' : schoolCount}
                    color="blue"
                    to="/schools"
                />
                <StatCard
                    icon={FileText}
                    label="Total Reports"
                    value={reportsLoading ? '...' : recentReports.length}
                    color="purple"
                    to="/reports"
                />
                <StatCard
                    icon={Clock}
                    label="Pending Review"
                    value={reportsLoading ? '...' : pendingCount}
                    color="amber"
                />
                <StatCard
                    icon={CheckCircle}
                    label="Approved"
                    value={reportsLoading ? '...' : approvedCount}
                    color="green"
                />
            </div>

            {/* Quick Actions */}
            <div className="mb-8">
                <h2 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    {hasCapability('cqa_create_reports') && (
                        <Link
                            to="/create"
                            className="flex items-center gap-4 p-4 bg-gradient-to-r from-primary-500 to-primary-600 text-white rounded-xl hover:from-primary-600 hover:to-primary-700 transition-all"
                        >
                            <FilePlus className="w-8 h-8" />
                            <div>
                                <p className="font-semibold">Create New Report</p>
                                <p className="text-sm text-primary-100">Start a new QA assessment</p>
                            </div>
                            <ArrowRight className="w-5 h-5 ml-auto" />
                        </Link>
                    )}

                    <Link
                        to="/reports"
                        className="flex items-center gap-4 p-4 bg-white border border-gray-200 rounded-xl hover:border-primary-300 hover:shadow-md transition-all"
                    >
                        <FileText className="w-8 h-8 text-gray-600" />
                        <div>
                            <p className="font-semibold text-gray-900">View All Reports</p>
                            <p className="text-sm text-gray-500">Browse and manage reports</p>
                        </div>
                        <ArrowRight className="w-5 h-5 ml-auto text-gray-400" />
                    </Link>

                    <Link
                        to="/schools"
                        className="flex items-center gap-4 p-4 bg-white border border-gray-200 rounded-xl hover:border-primary-300 hover:shadow-md transition-all"
                    >
                        <School className="w-8 h-8 text-gray-600" />
                        <div>
                            <p className="font-semibold text-gray-900">Manage Schools</p>
                            <p className="text-sm text-gray-500">View and edit schools</p>
                        </div>
                        <ArrowRight className="w-5 h-5 ml-auto text-gray-400" />
                    </Link>
                </div>
            </div>

            {/* Recent Reports */}
            <div>
                <div className="flex items-center justify-between mb-4">
                    <h2 className="text-lg font-semibold text-gray-900">Recent Reports</h2>
                    <Link to="/reports" className="text-sm text-primary-600 hover:text-primary-700">
                        View all →
                    </Link>
                </div>

                {reportsLoading ? (
                    <div className="bg-white rounded-xl border border-gray-200 p-8 text-center">
                        <div className="w-8 h-8 border-2 border-primary-600 border-t-transparent rounded-full animate-spin mx-auto" />
                        <p className="text-gray-500 mt-4">Loading recent reports...</p>
                    </div>
                ) : recentReports.length === 0 ? (
                    <div className="bg-white rounded-xl border border-gray-200 p-8 text-center">
                        <FileText className="w-12 h-12 text-gray-300 mx-auto mb-4" />
                        <p className="text-gray-500">No reports found. Create your first report to get started!</p>
                    </div>
                ) : (
                    <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">School</th>
                                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {recentReports.slice(0, 5).map((report) => (
                                    <tr key={report.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 text-sm text-gray-900">{report.school_name || 'Unknown School'}</td>
                                        <td className="px-6 py-4 text-sm text-gray-600">{report.report_type || 'Standard'}</td>
                                        <td className="px-6 py-4">
                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${report.status === 'approved' ? 'bg-green-100 text-green-800' :
                                                    report.status === 'pending' ? 'bg-amber-100 text-amber-800' :
                                                        'bg-gray-100 text-gray-800'
                                                }`}>
                                                {report.status || 'draft'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-500">
                                            {new Date(report.created_at || report.date).toLocaleDateString()}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </div>
    );
}

export default Dashboard;
