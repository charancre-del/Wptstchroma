import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { useReport, useSchool } from '@hooks/useQueries';
import { formatDate } from '@utils/helpers';
import {
    CheckCircle,
    AlertCircle,
    AlertTriangle,
    ClipboardList,
    Camera,
    MapPin,
    Calendar,
    FileText,
    School
} from 'lucide-react';

const PrintReport = () => {
    const { id } = useParams();
    const { data: report, isLoading, isError } = useReport(id);
    const { data: school } = useSchool(report?.school_id || 0);
    const [isReady, setIsReady] = useState(false);

    // Filter valid photos
    const photos = report?.photos || [];

    // Organize checklist by section
    const checklist = report?.responses || {};

    // Auto-trigger print when data is loaded
    useEffect(() => {
        if (!isLoading && report && school && !isReady) {
            // Small delay to ensure DOM is fully painted specifically for images
            const timer = setTimeout(() => {
                setIsReady(true);
                window.print();
            }, 800);
            return () => clearTimeout(timer);
        }
    }, [isLoading, report, school, isReady]);

    if (isLoading) {
        return (
            <div className="flex items-center justify-center min-h-screen">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-ink mx-auto mb-4"></div>
                    <p className="text-gray-600">Preparing Report for Print...</p>
                </div>
            </div>
        );
    }

    if (isError || !report) {
        return (
            <div className="p-8 text-center text-red-600">
                <h1 className="text-2xl font-bold mb-2">Error Loading Report</h1>
                <p>Unable to load report data. Please try again.</p>
            </div>
        );
    }

    return (
        <div className="bg-white min-h-screen font-outfit print:bg-white print:text-black">
            {/* Print Header */}
            <div className="max-w-4xl mx-auto p-8 print:p-0">
                <div className="flex justify-between items-start border-b-2 border-brand-ink/10 pb-6 mb-8">
                    <div>
                        <h1 className="text-3xl font-serif font-bold text-brand-ink mb-2">
                            Quality Assurance Report
                        </h1>
                        <div className="text-gray-600 flex items-center gap-2">
                            <School className="w-4 h-4" />
                            <span className="font-semibold">{school?.name || report.school_name}</span>
                        </div>
                    </div>
                    <div className="text-right">
                        <div className="inline-flex items-center gap-2 bg-brand-cream/50 px-4 py-2 rounded-lg border border-brand-ink/5 mb-2">
                            <span className="text-sm font-medium text-gray-500 uppercase tracking-wide">Status</span>
                            <span className={`font-bold ${report.status === 'approved' ? 'text-green-700' :
                                    report.status === 'submitted' ? 'text-blue-700' : 'text-amber-700'
                                }`}>
                                {report.status ? report.status.charAt(0).toUpperCase() + report.status.slice(1) : 'Draft'}
                            </span>
                        </div>
                        <p className="text-sm text-gray-500 mt-1">
                            Generated: {new Date().toLocaleDateString()}
                        </p>
                    </div>
                </div>

                {/* Report Metadata Grid */}
                <div className="grid grid-cols-2 gap-x-12 gap-y-4 mb-10 text-sm">
                    <div className="flex justify-between border-b border-gray-100 py-2">
                        <span className="text-gray-500">Visit Date:</span>
                        <span className="font-semibold">{formatDate(report.visit_date)}</span>
                    </div>
                    <div className="flex justify-between border-b border-gray-100 py-2">
                        <span className="text-gray-500">Report Type:</span>
                        <span className="font-semibold capitalize">{report.report_type?.replace('_', ' ') || 'Standard'}</span>
                    </div>
                    <div className="flex justify-between border-b border-gray-100 py-2">
                        <span className="text-gray-500">Author:</span>
                        <span className="font-semibold">{report.author_name || 'Corporate Chroma'}</span>
                    </div>
                    <div className="flex justify-between border-b border-gray-100 py-2">
                        <span className="text-gray-500">Overall Rating:</span>
                        <span className="font-semibold capitalize">{report.overall_rating?.replace('_', ' ') || 'Pending'}</span>
                    </div>
                </div>

                {/* Summary Section */}
                {(report.ai_summary || report.closing_notes) && (
                    <div className="mb-10 break-inside-avoid">
                        <h2 className="text-xl font-bold text-brand-ink border-l-4 border-brand-secondary pl-3 mb-4 flex items-center gap-2">
                            <FileText className="w-5 h-5 text-gray-400" />
                            Summary
                        </h2>

                        {report.ai_summary?.executive_summary && (
                            <div className="bg-gray-50 p-6 rounded-xl border border-gray-100 mb-4 text-gray-800 leading-relaxed text-sm">
                                {report.ai_summary.executive_summary.split('\n').map((para, i) => (
                                    <p key={i} className="mb-2 last:mb-0">{para}</p>
                                ))}
                            </div>
                        )}

                        {report.closing_notes && (
                            <div className="mt-4">
                                <h3 className="text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Closing Notes</h3>
                                <p className="text-gray-600 italic border-l-2 border-gray-200 pl-4 py-1">
                                    "{report.closing_notes}"
                                </p>
                            </div>
                        )}
                    </div>
                )}

                {/* Inspection Results */}
                <div className="mb-10">
                    <h2 className="text-xl font-bold text-brand-ink border-l-4 border-brand-secondary pl-3 mb-6 flex items-center gap-2">
                        <ClipboardList className="w-5 h-5 text-gray-400" />
                        Inspection Results
                    </h2>

                    {Object.entries(checklist).map(([sectionKey, items]) => {
                        const hasItems = Object.keys(items || {}).length > 0;
                        if (!hasItems) return null;

                        return (
                            <div key={sectionKey} className="mb-8 break-inside-avoid">
                                <h3 className="text-lg font-bold text-gray-800 mb-3 border-b border-gray-200 pb-2 capitalize">
                                    {sectionKey.replace(/([A-Z])/g, ' $1').trim()}
                                </h3>
                                <div className="space-y-4">
                                    {Object.entries(items).map(([itemKey, response]) => (
                                        <div key={itemKey} className="bg-white p-3 rounded-lg border border-gray-100">
                                            <div className="flex justify-between items-start mb-2">
                                                <span className="font-medium text-gray-900 text-sm flex-1 mr-4">
                                                    {itemKey.replace(/_/g, ' ')}
                                                </span>
                                                <span className={`
                                                    px-2 py-1 rounded text-xs font-bold uppercase tracking-wider
                                                    ${response.rating === 'yes' ? 'bg-green-100 text-green-700' :
                                                        response.rating === 'no' ? 'bg-red-100 text-red-700' :
                                                            response.rating === 'sometimes' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600'}
                                                `}>
                                                    {response.rating}
                                                </span>
                                            </div>
                                            {(response.notes || response.rating !== 'yes') && (
                                                <div className="text-xs text-gray-600 bg-gray-50 p-2 rounded mt-1">
                                                    {response.notes ? response.notes : 'No notes provided.'}
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        );
                    })}
                </div>

                {/* Photos Appendix */}
                {photos.length > 0 && (
                    <div className="break-before-page">
                        <h2 className="text-xl font-bold text-brand-ink border-l-4 border-brand-secondary pl-3 mb-6 flex items-center gap-2">
                            <Camera className="w-5 h-5 text-gray-400" />
                            Report Photos
                        </h2>

                        <div className="grid grid-cols-2 gap-6">
                            {photos.map((photo, index) => (
                                <div key={index} className="break-inside-avoid border border-gray-200 rounded-lg overflow-hidden bg-white shadow-sm">
                                    <div className="aspect-video bg-gray-100 relative overflow-hidden text-center flex items-center justify-center">
                                        {/* Use thumbnail URL if available, fallback to full */}
                                        <img
                                            src={photo.url || photo.thumbnail_url}
                                            alt={photo.caption || 'Report photo'}
                                            className="object-contain h-full w-full"
                                            onError={(e) => {
                                                e.target.style.display = 'none';
                                                e.target.nextSibling.style.display = 'flex';
                                            }}
                                        />
                                        <div className="hidden absolute inset-0 flex items-center justify-center text-gray-400">
                                            <AlertCircle className="w-8 h-8 opacity-50" />
                                        </div>
                                    </div>
                                    <div className="p-3 bg-gray-50 border-t border-gray-100">
                                        {photo.location_tag && (
                                            <div className="flex items-center gap-1.5 text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">
                                                <MapPin className="w-3 h-3" />
                                                {photo.location_tag}
                                            </div>
                                        )}
                                        <p className="text-sm text-gray-800 leading-snug">
                                            {photo.caption || 'No caption provided'}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Print Footer */}
                <div className="border-t-2 border-brand-ink/10 mt-12 pt-6 text-center text-xs text-gray-400">
                    <p>&copy; {new Date().getFullYear()} Chroma Early Learning Academy. All rights reserved.</p>
                </div>
            </div>
        </div>
    );
};

export default PrintReport;
