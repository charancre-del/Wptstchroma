import React, { useMemo, useState } from 'react';
import { useReportWizardStore } from '@stores';
import StepChecklist from './StepChecklist';
import { useSchool } from '@hooks/useQueries';
import { formatDate, cn } from '@utils/helpers';
import {
    CheckCircle,
    AlertCircle,
    School,
    Calendar,
    FileText,
    Camera,
    Sparkles,
    ClipboardList,
    AlertTriangle,
} from 'lucide-react';

export function StepReview({ onBack, isViewMode = false }) {
    const report = useReportWizardStore(s => s.report);
    const responses = useReportWizardStore(s => s.responses);
    const photos = useReportWizardStore(s => s.photos);
    const [showFullDetails, setShowFullDetails] = useState(isViewMode);
    const { data: school } = useSchool(report?.school_id || 0);

    // Validation checks
    const validation = useMemo(() => {
        const issues = [];
        const warnings = [];

        // Required: school selected
        if (!report?.school_id) {
            issues.push({ field: 'school', message: 'No school selected' });
        }

        // Required: report type
        if (!report?.report_type) {
            issues.push({ field: 'type', message: 'Report type not selected' });
        }

        // Required: visit date
        if (!report?.visit_date) {
            issues.push({ field: 'date', message: 'Visit date not set' });
        }

        // Required: at least some checklist responses
        const responseCount = Object.keys(responses).length;
        if (responseCount === 0) {
            issues.push({ field: 'checklist', message: 'No checklist items completed' });
        } else if (responseCount < 10) {
            warnings.push({ field: 'checklist', message: `Only ${responseCount} items completed` });
        }

        // Optional but recommended: photos
        if (photos.length === 0) {
            warnings.push({ field: 'photos', message: 'No photos attached' });
        }

        // Optional: AI summary
        if (!report?.ai_summary) {
            warnings.push({ field: 'summary', message: 'AI summary not generated' });
        }

        return {
            issues,
            warnings,
            isValid: issues.length === 0,
            isComplete: issues.length === 0 && warnings.length === 0,
        };
    }, [report, responses, photos]);

    const checklistStats = useMemo(() => {
        let total = 0;
        let withNotes = 0;

        // responses is { section_key: { item_key: { rating, notes, ... } } }
        Object.values(responses || {}).forEach(section => {
            if (typeof section === 'object' && section !== null) {
                const items = Object.values(section);
                total += items.length;
                withNotes += items.filter(item => item?.notes?.trim()).length;
            }
        });

        return { total, withNotes };
    }, [responses]);

    return (
        <div className="space-y-6 max-w-3xl">
            <div>
                <h2 className="text-2xl font-bold text-gray-900 mb-2">Review & Submit</h2>
                <p className="text-gray-600">
                    Review your report before submitting. You can go back to make changes.
                </p>
            </div>

            {/* Validation Status */}
            {!validation.isValid && (
                <div className="bg-red-50 border border-red-200 rounded-xl p-4">
                    <div className="flex items-start gap-3">
                        <AlertCircle className="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
                        <div>
                            <p className="font-medium text-red-800">Cannot submit yet</p>
                            <ul className="mt-2 text-sm text-red-700 space-y-1">
                                {validation.issues.map((issue, i) => (
                                    <li key={i}>• {issue.message}</li>
                                ))}
                            </ul>
                        </div>
                    </div>
                </div>
            )}

            {validation.isValid && validation.warnings.length > 0 && (
                <div className="bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <div className="flex items-start gap-3">
                        <AlertTriangle className="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" />
                        <div>
                            <p className="font-medium text-amber-800">Recommendations</p>
                            <ul className="mt-2 text-sm text-amber-700 space-y-1">
                                {validation.warnings.map((warning, i) => (
                                    <li key={i}>• {warning.message}</li>
                                ))}
                            </ul>
                        </div>
                    </div>
                </div>
            )}

            {validation.isComplete && (
                <div className="bg-green-50 border border-green-200 rounded-xl p-4">
                    <div className="flex items-center gap-3">
                        <CheckCircle className="w-5 h-5 text-green-500" />
                        <p className="font-medium text-green-800">Report is complete and ready to submit</p>
                    </div>
                </div>
            )}

            {/* Summary Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {/* School */}
                <SummaryCard
                    icon={School}
                    label="School"
                    value={school?.name || report?.school_name || 'Not selected'}
                    status={report?.school_id ? 'complete' : 'error'}
                />

                {/* Report Type */}
                <SummaryCard
                    icon={FileText}
                    label="Report Type"
                    value={formatReportType(report?.report_type)}
                    status={report?.report_type ? 'complete' : 'error'}
                />

                {/* Visit Date */}
                <SummaryCard
                    icon={Calendar}
                    label="Visit Date"
                    value={report?.visit_date ? formatDate(report.visit_date) : 'Not set'}
                    status={report?.visit_date ? 'complete' : 'error'}
                />

                {/* Checklist */}
                <SummaryCard
                    icon={ClipboardList}
                    label="Checklist Items"
                    value={`${checklistStats.total} completed (${checklistStats.withNotes} with notes)`}
                    status={checklistStats.total > 0 ? 'complete' : 'error'}
                />

                {/* Photos */}
                <SummaryCard
                    icon={Camera}
                    label="Photos"
                    value={`${photos.length} attached`}
                    status={photos.length > 0 ? 'complete' : 'warning'}
                />

                {/* AI Summary */}
                <SummaryCard
                    icon={Sparkles}
                    label="AI Summary"
                    value={report?.ai_summary ? 'Generated' : 'Not generated'}
                    status={report?.ai_summary ? 'complete' : 'warning'}
                />
            </div>

            {/* AI Summary Preview */}
            {report?.ai_summary && (
                <div className="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 className="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                        <Sparkles className="w-5 h-5 text-primary-500" />
                        AI Summary Preview
                    </h3>
                    <div className="prose prose-sm max-w-none text-gray-700">
                        {report.ai_summary?.executive_summary ? (
                            <>
                                {report.ai_summary.executive_summary.split('\n').slice(0, 3).map((p, i) => (
                                    <p key={i} className="mb-2">{p}</p>
                                ))}
                                {report.ai_summary.executive_summary.split('\n').length > 3 && (
                                    <p className="text-gray-500 italic">... and more</p>
                                )}
                            </>
                        ) : (
                            <p className="text-gray-500 italic">No summary available.</p>
                        )}
                    </div>
                </div>
            )}

            {/* Notes */}
            {report?.closing_notes && (
                <div className="bg-gray-50 rounded-xl border border-gray-200 p-4">
                    <h3 className="font-medium text-gray-700 mb-2">Closing Notes</h3>
                    <p className="text-gray-600">{report.closing_notes}</p>
                </div>
            )}

            {/* Full Details Toggle */}
            <div className="pt-4 border-t border-brand-ink/5">
                <button
                    onClick={() => setShowFullDetails(!showFullDetails)}
                    className="flex items-center gap-2 text-primary-600 font-bold hover:text-primary-700 transition-colors"
                >
                    <ClipboardList className="w-5 h-5" />
                    {showFullDetails ? 'Hide Detailed Results' : 'Show Detailed Results'}
                </button>
            </div>

            {showFullDetails && (
                <div className="mt-6 p-6 bg-white rounded-3xl border border-brand-ink/10 shadow-sm animate-fade-in">
                    <h3 className="text-xl font-serif font-bold text-brand-ink mb-6">Detailed Inspection Results</h3>
                    <StepChecklist draft={report} readOnly={true} />
                </div>
            )}
        </div>
    );
}

function SummaryCard({ icon: Icon, label, value, status }) {
    const statusStyles = {
        complete: 'border-green-200 bg-green-50',
        warning: 'border-amber-200 bg-amber-50',
        error: 'border-red-200 bg-red-50',
        default: 'border-gray-200 bg-white',
    };

    const iconStyles = {
        complete: 'text-green-600',
        warning: 'text-amber-600',
        error: 'text-red-600',
        default: 'text-gray-400',
    };

    return (
        <div className={cn(
            'rounded-xl border p-4 flex items-start gap-3',
            statusStyles[status] || statusStyles.default
        )}>
            <Icon className={cn('w-5 h-5 mt-0.5', iconStyles[status] || iconStyles.default)} />
            <div>
                <p className="text-sm text-gray-500">{label}</p>
                <p className="font-medium text-gray-900">{value}</p>
            </div>
        </div>
    );
}

function formatReportType(type) {
    const labels = {
        'tier-1': 'Tier 1 Assessment',
        'tier-2': 'Tier 2 CQI',
        'follow-up': 'Follow-up Visit',
    };
    return labels[type] || type || 'Not selected';
}

export default StepReview;
