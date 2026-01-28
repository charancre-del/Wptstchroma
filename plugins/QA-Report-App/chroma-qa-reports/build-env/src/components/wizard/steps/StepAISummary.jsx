import React, { useState } from 'react';
import { useReportWizardStore } from '@stores';
import { useGenerateAISummary } from '@hooks/useQueries';
import { cn } from '../../../utils/helpers';
import {
    Sparkles,
    RefreshCw,
    Copy,
    Check,
    AlertCircle,
    Loader2,
    Info,
    Edit3,
} from 'lucide-react';
import { toast } from 'sonner';

export function StepAISummary() {
    const report = useReportWizardStore(s => s.report);
    const responses = useReportWizardStore(s => s.responses);
    const setReport = useReportWizardStore(s => s.setReport);
    const generateSummary = useGenerateAISummary();

    const [isEditing, setIsEditing] = useState(false);
    const [editedSummary, setEditedSummary] = useState('');
    const [copied, setCopied] = useState(false);

    const handleGenerate = async () => {
        if (!report?.id) {
            toast.error('Please save the report first before generating a summary.');
            return;
        }

        try {
            const result = await generateSummary.mutateAsync({
                reportId: report.id,
                checklistData: responses
            });

            // Standardize result structure - expect an object with executive_summary
            const summaryObj = typeof result.summary === 'string'
                ? { executive_summary: result.summary }
                : result.summary;

            setReport({
                ...report,
                ai_summary: summaryObj,
            });
            toast.success('AI Summary generated successfully!');
        } catch (error) {
            console.error('AI Summary error:', error);
            toast.error('Failed to generate summary. Please try again.');
        }
    };

    const handleEdit = () => {
        const currentSummary = typeof report?.ai_summary === 'object'
            ? report.ai_summary?.executive_summary
            : report?.ai_summary;

        setEditedSummary(currentSummary || '');
        setIsEditing(true);
    };

    const handleSaveEdit = () => {
        const existingSummary = typeof report?.ai_summary === 'object' ? report.ai_summary : {};

        setReport({
            ...report,
            ai_summary: {
                ...existingSummary,
                executive_summary: editedSummary,
            },
        });
        setIsEditing(false);
        toast.success('Summary updated');
    };

    const handleCancelEdit = () => {
        setEditedSummary('');
        setIsEditing(false);
    };

    const handleCopy = async () => {
        const textToCopy = typeof report?.ai_summary === 'object'
            ? report.ai_summary?.executive_summary
            : report?.ai_summary;

        if (textToCopy) {
            await navigator.clipboard.writeText(textToCopy);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
            toast.success('Copied to clipboard');
        }
    };

    const hasResponses = Object.keys(responses).length > 0;
    const hasSummary = !!report?.ai_summary;

    return (
        <div className="space-y-6 max-w-3xl">
            <div>
                <h2 className="text-2xl font-bold text-gray-900 mb-2">AI Summary</h2>
                <p className="text-gray-600">
                    Generate an executive summary of your assessment using AI. You can edit the summary after generation.
                </p>
            </div>

            {/* Info Banner */}
            <div className="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start gap-3">
                <Info className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
                <div className="text-sm text-blue-800">
                    <p className="font-medium mb-1">How it works</p>
                    <p>
                        The AI will analyze your checklist responses and photos to generate a
                        comprehensive summary highlighting key findings, areas of compliance,
                        and recommendations for improvement.
                    </p>
                </div>
            </div>

            {/* Generate Button */}
            {!hasSummary && (
                <div className="bg-white rounded-xl border border-gray-200 p-6 text-center">
                    {!hasResponses ? (
                        <div className="text-gray-500">
                            <AlertCircle className="w-12 h-12 mx-auto mb-4 opacity-50" />
                            <p>Complete the checklist first to generate an AI summary.</p>
                        </div>
                    ) : (
                        <>
                            <div className="w-16 h-16 bg-gradient-to-br from-purple-100 to-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <Sparkles className="w-8 h-8 text-primary-600" />
                            </div>
                            <h3 className="font-semibold text-gray-900 mb-2">
                                Ready to Generate Summary
                            </h3>
                            <p className="text-gray-600 mb-6">
                                {Object.keys(responses).length} checklist items completed
                            </p>
                            <button
                                onClick={handleGenerate}
                                disabled={generateSummary.isPending}
                                className="btn btn-primary flex items-center gap-2 mx-auto"
                            >
                                {generateSummary.isPending ? (
                                    <>
                                        <Loader2 className="w-5 h-5 animate-spin" />
                                        Generating...
                                    </>
                                ) : (
                                    <>
                                        <Sparkles className="w-5 h-5" />
                                        Generate AI Summary
                                    </>
                                )}
                            </button>
                            {generateSummary.isPending && (
                                <p className="text-sm text-gray-500 mt-4">
                                    This may take 15-30 seconds...
                                </p>
                            )}
                        </>
                    )}
                </div>
            )}

            {/* Summary Display/Edit */}
            {hasSummary && (
                <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    {/* Header */}
                    <div className="px-6 py-4 bg-gradient-to-r from-primary-50 to-purple-50 border-b border-gray-200 flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <Sparkles className="w-5 h-5 text-primary-600" />
                            <span className="font-semibold text-gray-900">Executive Summary</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <button
                                onClick={handleCopy}
                                className="p-2 text-gray-500 hover:text-gray-700 hover:bg-white/50 rounded-lg transition-colors"
                                title="Copy to clipboard"
                            >
                                {copied ? <Check className="w-4 h-4 text-green-600" /> : <Copy className="w-4 h-4" />}
                            </button>
                            {!isEditing && (
                                <button
                                    onClick={handleEdit}
                                    className="p-2 text-gray-500 hover:text-gray-700 hover:bg-white/50 rounded-lg transition-colors"
                                    title="Edit summary"
                                >
                                    <Edit3 className="w-4 h-4" />
                                </button>
                            )}
                            <button
                                onClick={handleGenerate}
                                disabled={generateSummary.isPending}
                                className="p-2 text-gray-500 hover:text-gray-700 hover:bg-white/50 rounded-lg transition-colors"
                                title="Regenerate"
                            >
                                <RefreshCw className={cn('w-4 h-4', generateSummary.isPending && 'animate-spin')} />
                            </button>
                        </div>
                    </div>

                    {/* Content */}
                    <div className="p-6">
                        {isEditing ? (
                            <div className="space-y-4">
                                <textarea
                                    value={editedSummary}
                                    onChange={(e) => setEditedSummary(e.target.value)}
                                    rows={12}
                                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none"
                                />
                                <div className="flex items-center justify-end gap-3">
                                    <button
                                        onClick={handleCancelEdit}
                                        className="btn btn-secondary"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        onClick={handleSaveEdit}
                                        className="btn btn-primary"
                                    >
                                        Save Changes
                                    </button>
                                </div>
                            </div>
                        ) : (
                            <div className="prose prose-sm max-w-none">
                                {(report.ai_summary?.executive_summary || report.ai_summary || '').split('\n').map((paragraph, idx) => (
                                    paragraph.trim() && (
                                        <p key={idx} className="text-gray-700 mb-4 last:mb-0">
                                            {paragraph}
                                        </p>
                                    )
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            )}

            {/* Error State */}
            {generateSummary.isError && (
                <div className="bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
                    <AlertCircle className="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
                    <div>
                        <p className="font-medium text-red-800">Generation Failed</p>
                        <p className="text-sm text-red-700 mt-1">
                            {generateSummary.error?.message || 'An error occurred. Please try again.'}
                        </p>
                        <button
                            onClick={handleGenerate}
                            className="mt-3 text-sm text-red-700 font-medium hover:text-red-800"
                        >
                            Try Again →
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}

export default StepAISummary;
