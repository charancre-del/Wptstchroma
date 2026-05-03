import React, { useState } from 'react';
import { useReportWizardStore } from '@stores';
import { useGenerateAISummary } from '@hooks/useQueries';
import { cn } from '../../../utils/helpers';
import { Sparkles, RefreshCw, Copy, Check, AlertCircle, Loader2, Info, Edit3 } from 'lucide-react';
import { toast } from 'sonner';

const normalizeSummaryPayload = ( summary ) => {
    if ( ! summary || typeof summary !== 'object' ) {
        return summary;
    }

    const poiItems = summary.plan_of_improvement || summary.poi || summary.support_and_growth_plan || [];
    const generationWarnings = summary.generation_warnings || summary.warnings || [];

    return {
        ...summary,
        plan_of_improvement: Array.isArray( poiItems ) ? poiItems : [],
        generation_warnings: Array.isArray( generationWarnings ) ? generationWarnings.filter( Boolean ) : [],
    };
};

export function StepAISummary( { readOnly = false } ) {
    const report = useReportWizardStore( ( s ) => s.report );
    const responses = useReportWizardStore( ( s ) => s.responses );
    const setReport = useReportWizardStore( ( s ) => s.setReport );
    const generateSummary = useGenerateAISummary();

    const [ isEditing, setIsEditing ] = useState( false );
    const [ editedSummary, setEditedSummary ] = useState( '' );
    const [ copied, setCopied ] = useState( false );

    const handleGenerate = async () => {
        if ( ! report?.id ) {
            toast.error( 'Please save the report first before generating a summary.' );
            return;
        }

        try {
            const result = await generateSummary.mutateAsync( {
                reportId: report.id,
            } );

            // Handle both response formats:
            // 1. { summary: { executive_summary: ..., plan_of_improvement: [...] } }
            // 2. { executive_summary: ..., plan_of_improvement: [...] } (direct format)
            let summaryObj;
            if ( result?.summary ) {
                // Backend wrapped the response in a 'summary' key
                summaryObj =
                    typeof result.summary === 'string' ? { executive_summary: result.summary } : result.summary;
            } else if ( result?.executive_summary ) {
                // Direct format - use result as-is
                summaryObj = result;
            } else {
                // Fallback - treat result as summary
                summaryObj = typeof result === 'string' ? { executive_summary: result } : result;
            }

            summaryObj = normalizeSummaryPayload( summaryObj );

            setReport( {
                ...report,
                ai_summary: summaryObj,
            } );
            toast.success( 'AI Summary generated successfully!' );
        } catch ( error ) {
            console.error( 'AI Summary error:', error );
            toast.error( error?.message || 'Failed to generate summary. Please try again.' );
        }
    };

    const handleEdit = () => {
        const currentSummary =
            typeof report?.ai_summary === 'object' ? report.ai_summary?.executive_summary : report?.ai_summary;

        setEditedSummary( currentSummary || '' );
        setIsEditing( true );
    };

    const handleSaveEdit = () => {
        const existingSummary = typeof report?.ai_summary === 'object' ? report.ai_summary : {};

        setReport( {
            ...report,
            ai_summary: {
                ...existingSummary,
                executive_summary: editedSummary,
            },
        } );
        setIsEditing( false );
        toast.success( 'Summary updated' );
    };

    const handleCancelEdit = () => {
        setEditedSummary( '' );
        setIsEditing( false );
    };

    const handleCopy = async () => {
        const textToCopy =
            typeof report?.ai_summary === 'object' ? report.ai_summary?.executive_summary : report?.ai_summary;

        if ( textToCopy ) {
            await navigator.clipboard.writeText( textToCopy );
            setCopied( true );
            setTimeout( () => setCopied( false ), 2000 );
            toast.success( 'Copied to clipboard' );
        }
    };

    // Count total checklist items (not just sections)
    const responseCount = React.useMemo( () => {
        let total = 0;
        Object.values( responses || {} ).forEach( ( section ) => {
            if ( typeof section === 'object' && section !== null ) {
                total += Object.keys( section ).length;
            }
        } );
        return total;
    }, [ responses ] );

    const hasResponses = responseCount > 0;
    const hasSummary = !! report?.ai_summary;
    const generationWarnings = React.useMemo( () => {
        const warnings = report?.ai_summary?.generation_warnings || [];
        return Array.isArray( warnings ) ? warnings.filter( Boolean ) : [];
    }, [ report?.ai_summary ] );

    return (
        <div className="space-y-6 w-full">
            <div>
                <h2 className="text-2xl font-bold text-gray-900 mb-2">AI Summary</h2>
                <p className="text-gray-600">
                    Generate an executive summary of your assessment using AI. You can edit the summary after
                    generation.
                </p>
            </div>

            { /* Info Banner */ }
            <div className="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start gap-3">
                <Info className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
                <div className="text-sm text-blue-800">
                    <p className="font-medium mb-1">How it works</p>
                    <p>
                        The AI will analyze your checklist responses and photos to generate a comprehensive summary
                        highlighting key findings, areas of compliance, and recommendations for improvement.
                    </p>
                </div>
            </div>

            { hasSummary && generationWarnings.length > 0 && (
                <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
                    <AlertCircle className="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" />
                    <div className="text-sm text-amber-800">
                        <p className="font-medium mb-1">Review Before Sharing</p>
                        { generationWarnings.map( ( warning, index ) => (
                            <p key={ index } className={ index > 0 ? 'mt-1' : '' }>
                                { warning }
                            </p>
                        ) ) }
                    </div>
                </div>
            ) }

            { /* Generate Button */ }
            { ! hasSummary && (
                <div className="bg-white rounded-xl border border-gray-200 p-6 text-center">
                    { ! hasResponses ? (
                        <div className="text-gray-500">
                            <AlertCircle className="w-12 h-12 mx-auto mb-4 opacity-50" />
                            <p>Complete the checklist first to generate an AI summary.</p>
                        </div>
                    ) : (
                        <>
                            <div className="w-16 h-16 bg-gradient-to-br from-purple-100 to-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <Sparkles className="w-8 h-8 text-primary-600" />
                            </div>
                            <h3 className="font-semibold text-gray-900 mb-2">Ready to Generate Summary</h3>
                            <p className="text-gray-600 mb-6">{ responseCount } checklist items completed</p>
                            <button
                                onClick={ handleGenerate }
                                disabled={ generateSummary.isPending || readOnly }
                                className="btn btn-primary flex items-center gap-2 mx-auto"
                            >
                                { generateSummary.isPending ? (
                                    <>
                                        <Loader2 className="w-5 h-5 animate-spin" />
                                        Generating...
                                    </>
                                ) : (
                                    <>
                                        <Sparkles className="w-5 h-5" />
                                        Generate AI Summary
                                    </>
                                ) }
                            </button>
                            { generateSummary.isPending && (
                                <p className="text-sm text-gray-500 mt-4">This may take 15-30 seconds...</p>
                            ) }
                        </>
                    ) }
                </div>
            ) }

            { /* Summary Display/Edit */ }
            { hasSummary && (
                <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    { /* Header */ }
                    <div className="px-6 py-4 bg-gradient-to-r from-primary-50 to-purple-50 border-b border-gray-200 flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <Sparkles className="w-5 h-5 text-primary-600" />
                            <span className="font-semibold text-gray-900">Executive Summary</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <button
                                onClick={ handleCopy }
                                className="p-2 text-gray-500 hover:text-gray-700 hover:bg-white/50 rounded-lg transition-colors"
                                title="Copy to clipboard"
                            >
                                { copied ? <Check className="w-4 h-4 text-green-600" /> : <Copy className="w-4 h-4" /> }
                            </button>
                            { ! isEditing && ! readOnly && (
                                <button
                                    onClick={ handleEdit }
                                    className="p-2 text-gray-500 hover:text-gray-700 hover:bg-white/50 rounded-lg transition-colors"
                                    title="Edit summary"
                                >
                                    <Edit3 className="w-4 h-4" />
                                </button>
                            ) }
                            { ! readOnly && (
                                <button
                                    onClick={ handleGenerate }
                                    disabled={ generateSummary.isPending }
                                    className="p-2 text-gray-500 hover:text-gray-700 hover:bg-white/50 rounded-lg transition-colors"
                                    title="Regenerate"
                                >
                                    <RefreshCw
                                        className={ cn( 'w-4 h-4', generateSummary.isPending && 'animate-spin' ) }
                                    />
                                </button>
                            ) }
                        </div>
                    </div>

                    { /* Content */ }
                    <div className="p-6">
                        { isEditing ? (
                            <div className="space-y-4">
                                <textarea
                                    value={ editedSummary }
                                    onChange={ ( e ) => setEditedSummary( e.target.value ) }
                                    rows={ 12 }
                                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none"
                                />
                                <div className="flex items-center justify-end gap-3">
                                    <button onClick={ handleCancelEdit } className="btn btn-secondary">
                                        Cancel
                                    </button>
                                    <button onClick={ handleSaveEdit } className="btn btn-primary">
                                        Save Changes
                                    </button>
                                </div>
                            </div>
                        ) : (
                            <div className="prose prose-sm max-w-none">
                                { ( report.ai_summary?.executive_summary || report.ai_summary || '' ).split( '\n' ).map(
                                    ( paragraph, idx ) =>
                                        paragraph.trim() && (
                                            <p key={ idx } className="text-gray-700 mb-4 last:mb-0">
                                                { paragraph }
                                            </p>
                                        )
                                ) }
                            </div>
                        ) }
                    </div>
                </div>
            ) }

            { /* Plan of Improvement Section */ }
            { hasSummary &&
                report.ai_summary?.plan_of_improvement &&
                report.ai_summary.plan_of_improvement.length > 0 && (
                    <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        { /* POI Header */ }
                        <div className="px-6 py-4 bg-gradient-to-r from-amber-50 to-orange-50 border-b border-gray-200 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <svg
                                    className="w-5 h-5 text-amber-600"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={ 2 }
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                                    />
                                </svg>
                                <span className="font-semibold text-gray-900">Plan of Improvement</span>
                                <span className="ml-2 px-2 py-0.5 bg-amber-100 text-amber-700 text-xs font-medium rounded-full">
                                    { report.ai_summary.plan_of_improvement.length } items
                                </span>
                            </div>
                        </div>

                        { /* POI Content */ }
                        <div className="divide-y divide-gray-100">
                            { report.ai_summary.plan_of_improvement.map( ( poi, index ) => (
                                <div key={ index } className="p-6">
                                    { /* Priority Badge & Area */ }
                                    <div className="flex items-start justify-between mb-3">
                                        <div className="flex items-center gap-3">
                                            <span
                                                className={ cn(
                                                    'px-2.5 py-1 text-xs font-bold rounded-full',
                                                    poi.priority === 1 ||
                                                        String( poi.priority ).toLowerCase() === 'immediate'
                                                        ? 'bg-red-100 text-red-700'
                                                        : poi.priority === 2 ||
                                                          String( poi.priority ).toLowerCase() === 'high'
                                                        ? 'bg-orange-100 text-orange-700'
                                                        : poi.priority === 3 ||
                                                          String( poi.priority ).toLowerCase() === 'medium'
                                                        ? 'bg-amber-100 text-amber-700'
                                                        : 'bg-green-100 text-green-700'
                                                ) }
                                            >
                                                Priority { poi.priority || index + 1 }
                                            </span>
                                            <h4 className="font-semibold text-gray-900">{ poi.area }</h4>
                                        </div>
                                        { poi.timeline && (
                                            <span className="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                                { poi.timeline }
                                            </span>
                                        ) }
                                    </div>

                                    { /* Current Status */ }
                                    { poi.current_status && (
                                        <div className="mb-3 bg-gray-50 p-3 rounded-lg border border-gray-100">
                                            <p className="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">
                                                Current Status
                                            </p>
                                            <p className="text-sm text-gray-700 italic">{ poi.current_status }</p>
                                        </div>
                                    ) }

                                    { /* Action Steps */ }
                                    { poi.action_steps && poi.action_steps.length > 0 && (
                                        <div className="mb-3">
                                            <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">
                                                Required Action Steps
                                            </p>
                                            <ul className="space-y-1.5">
                                                { poi.action_steps.map( ( step, stepIdx ) => (
                                                    <li
                                                        key={ stepIdx }
                                                        className="flex items-start gap-2 text-sm text-gray-700"
                                                    >
                                                        <span className="w-5 h-5 flex-shrink-0 flex items-center justify-center bg-primary-100 text-primary-700 rounded-full text-xs font-medium">
                                                            { stepIdx + 1 }
                                                        </span>
                                                        <span>{ step }</span>
                                                    </li>
                                                ) ) }
                                            </ul>
                                        </div>
                                    ) }

                                    { /* Success Criteria & Support */ }
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 pt-3 border-t border-gray-100">
                                        { poi.success_criteria && (
                                            <div>
                                                <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">
                                                    Success Criteria
                                                </p>
                                                <p className="text-sm text-gray-700">{ poi.success_criteria }</p>
                                            </div>
                                        ) }
                                        { poi.support_offered && (
                                            <div>
                                                <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">
                                                    Support Available
                                                </p>
                                                <p className="text-sm text-gray-700">{ poi.support_offered }</p>
                                            </div>
                                        ) }
                                    </div>
                                </div>
                            ) ) }
                        </div>
                    </div>
                ) }

            { /* Recommendations Section */ }
            { hasSummary && report.ai_summary?.recommendations && report.ai_summary.recommendations.length > 0 && (
                <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div className="px-6 py-4 bg-gradient-to-r from-emerald-50 to-teal-50 border-b border-gray-200">
                        <div className="flex items-center gap-2">
                            <svg
                                className="w-5 h-5 text-emerald-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={ 2 }
                                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                                />
                            </svg>
                            <span className="font-semibold text-gray-900">Recommendations</span>
                        </div>
                    </div>
                    <div className="p-6">
                        <ul className="space-y-3">
                            { report.ai_summary.recommendations.map( ( rec, idx ) => (
                                <li key={ idx } className="flex items-start gap-3">
                                    <span className="w-6 h-6 flex-shrink-0 flex items-center justify-center bg-emerald-100 text-emerald-700 rounded-full text-sm font-medium">
                                        { idx + 1 }
                                    </span>
                                    <span className="text-gray-700">{ rec }</span>
                                </li>
                            ) ) }
                        </ul>
                    </div>
                </div>
            ) }

            { /* Error State */ }
            { generateSummary.isError && (
                <div className="bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
                    <AlertCircle className="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
                    <div>
                        <p className="font-medium text-red-800">Generation Failed</p>
                        <p className="text-sm text-red-700 mt-1">
                            { generateSummary.error?.message || 'An error occurred. Please try again.' }
                        </p>
                        <button
                            onClick={ handleGenerate }
                            className="mt-3 text-sm text-red-700 font-medium hover:text-red-800"
                        >
                            Try Again →
                        </button>
                    </div>
                </div>
            ) }
        </div>
    );
}

export default StepAISummary;
