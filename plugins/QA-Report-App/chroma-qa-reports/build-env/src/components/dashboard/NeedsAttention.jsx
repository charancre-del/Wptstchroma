import React from 'react';
import { Link } from 'react-router-dom';
import { AlertTriangle, Plus, Building2 } from 'lucide-react';
import { formatDate } from '../../utils/helpers';

const NeedsAttention = ({ stats, isLoading }) => {

    if (isLoading) {
        return (
            <div className="bg-white p-6 rounded-xl border border-gray-100 shadow-sm h-full flex flex-col animate-pulse">
                <div className="h-6 w-1/3 bg-gray-200 rounded mb-4"></div>
                <div className="flex-1 space-y-3">
                    {[1, 2, 3].map(i => (
                        <div key={i} className="h-12 bg-gray-100 rounded-lg"></div>
                    ))}
                </div>
            </div>
        );
    }

    const schools = stats?.overdue_list || [];

    return (
        <div className="bg-white p-6 rounded-xl border border-gray-100 shadow-sm h-full flex flex-col">
            <h3 className="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <AlertTriangle className="w-5 h-5 text-amber-500" />
                Needs Attention
            </h3>

            <div className="flex-1 overflow-y-auto pr-2 space-y-3">
                {schools.length > 0 ? (
                    schools.map(school => (
                        <div key={school.id} className="group flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors border border-transparent hover:border-gray-100">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 bg-red-50 text-red-500 rounded-lg flex items-center justify-center shrink-0">
                                    <Building2 className="w-5 h-5" />
                                </div>
                                <div>
                                    <p className="font-medium text-gray-900 line-clamp-1">{school.name}</p>
                                    <p className="text-xs text-red-500 font-medium">
                                        Last visit: {school.last_visit ? formatDate(school.last_visit) : 'Never (No reports)'}
                                    </p>
                                </div>
                            </div>
                            <Link
                                to={`/create?school=${school.id}`}
                                className="p-2 text-gray-400 hover:text-cqa-primary hover:bg-primary-50 rounded-full transition-colors"
                                title="Create Report"
                            >
                                <Plus className="w-5 h-5" />
                            </Link>
                        </div>
                    ))
                ) : (
                    <div className="text-center py-8 text-gray-500 text-sm">
                        <p>All schools are up to date! 🎉</p>
                    </div>
                )}
            </div>
        </div>
    );
};

export default NeedsAttention;
