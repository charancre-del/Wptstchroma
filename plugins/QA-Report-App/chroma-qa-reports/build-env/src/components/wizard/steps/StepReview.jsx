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

const OVERALL_RATING_OPTIONS = [
    {
        value: 'exceeds',
        label: 'Exceeds',
        selectedClass: 'border-green-400 bg-green-50 text-green-800',
    },
    {
        value: 'meets',
        label: 'Meets',
        selectedClass: 'border-blue-400 bg-blue-50 text-blue-800',
    },
    {
        value: 'needs_improvement',
        label: 'Needs Improvement',
        selectedClass: 'border-red-400 bg-red-50 text-red-800',
    },
];

export function StepReview( { isViewMode = false, readOnly = false } ) {
    const report = useReportWizardStore( ( s ) => s.report );
    const responses = useReportWizardStore( ( s ) => s.responses );
    const photos = useReportWizardStore( ( s ) => s.photos );
    const [ showFullDetails, setShowFullDetails ] = useState( isViewMode );
    const { data: school } = useSchool( report?.school_id || 0 );

    // Validation checks
    const validation = useMemo( () => {
        const issues = [];
        const warnings = [];

        // Required: school selected
        if ( ! report?.school_id ) {
            issues.push( { field: 'school', message: 'No school selected' } );
        }

        // Required: report type
        if ( ! report?.report_type ) {
            issues.push( { field: 'type', message: 'Report type not selected' } );
        }

        // Required: visit date
        if ( ! report?.visit_date ) {
            issues.push( { field: 'date', message: 'Visit date not set' } );
        }

        // Count nested checklist items, not just top-level sections.
        const responseCount = Object.values( responses || {} ).reduce( ( total, section ) => {
            if ( typeof section !== 'object' || section === null ) {
                return total;
            }

            return total + Object.keys( section ).length;
        }, 0 );

        // Required: at least some checklist responses
        if ( responseCount === 0 ) {
            issues.push( { field: 'checklist', message: 'No checklist items completed' } );
        } else if ( responseCount < 10 ) {
            warnings.push( { field: 'checklist', message: `Only ${ responseCount } items completed` } );
        }

        // Optional but recommended: photos
        if ( photos.length === 0 ) {
            warnings.push( { field: 'photos', message: 'No photos attached' } );
        }

        // Optional: AI summary
        if ( ! report?.ai_summary ) {
            warnings.push( { field: 'summary', message: 'AI summary not generated' } );
        }

        return {
            issues,
            warnings,
            isValid: issues.length === 0,
            isComplete: issues.length === 0 && warnings.length === 0,
        };
    }, [ report, responses, photos ] );

    const checklistStats = useMemo( () => {
        let total = 0;
        let withNotes = 0;

        // responses is { section_key: { item_key: { rating, notes, ... } } }
        Object.values( responses || {} ).forEach( ( section ) => {
            if ( typeof section === 'object' && section !== null ) {
                const items = Object.values( section );
                total += items.length;
                withNotes += items.filter( ( item ) => item?.notes?.trim() ).length;
            }
        } );

        return { total, withNotes };
    }, [ responses ] );

    const currentOverallRating = report?.overall_rating || 'pending';
    const reportStatus = report?.status || 'draft';
    const reviewTitle = isViewMode ? 'Review Report' : 'Review & Submit';
    const reviewIntro = isViewMode
        ? reportStatus === 'submitted'
            ? 'This report has been submitted and is awaiting approval.'
            : reportStatus === 'approved'
                ? 'This report has been approved and is read-only unless reverted by an approver.'
                : 'Review the current report details.'
        : 'Review your report before submitting. You can go back to make changes.';
    const completeMessage = isViewMode
        ? reportStatus === 'submitted'
            ? 'Report is submitted and ready for approval'
            : reportStatus === 'approved'
                ? 'Report is approved'
                : 'Report is complete'
        : 'Report is complete and ready to submit';

    return (
        <div className="space-y-6 w-full">
            <div>
                <h2 className="text-2xl font-bold text-gray-900 mb-2">{ reviewTitle }</h2>
                <p className="text-gray-600">{ reviewIntro }</p>
            </div>

            { /* Validation Status */ }
            { ! validation.isValid && (
                <div className="bg-red-50 border border-red-200 rounded-xl p-4">
                    <div className="flex items-start gap-3">
                        <AlertCircle className="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
                        <div>
                            <p className="font-medium text-red-800">Cannot submit yet</p>
                            <ul className="mt-2 text-sm text-red-700 space-y-1">
                                { validation.issues.map( ( issue, i ) => (
                                    <li key={ i }>• { issue.message }</li>
                                ) ) }
                            </ul>
                        </div>
                    </div>
                </div>
            ) }

            { validation.isValid && validation.warnings.length > 0 && (
                <div className="bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <div className="flex items-start gap-3">
                        <AlertTriangle className="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" />
                        <div>
                            <p className="font-medium text-amber-800">Recommendations</p>
                            <ul className="mt-2 text-sm text-amber-700 space-y-1">
                                { validation.warnings.map( ( warning, i ) => (
                                    <li key={ i }>• { warning.message }</li>
                                ) ) }
                            </ul>
                        </div>
                    </div>
                </div>
            ) }

            { validation.isComplete && (
                <div className="bg-green-50 border border-green-200 rounded-xl p-4">
                    <div className="flex items-center gap-3">
                        <CheckCircle className="w-5 h-5 text-green-500" />
                        <p className="font-medium text-green-800">{ completeMessage }</p>
                    </div>
                </div>
            ) }

            { /* Summary Cards */ }
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                { /* School */ }
                <SummaryCard
                    icon={ School }
                    label="School"
                    value={ school?.name || report?.school_name || 'Not selected' }
                    status={ report?.school_id ? 'complete' : 'error' }
                />

                { /* Report Type */ }
                <SummaryCard
                    icon={ FileText }
                    label="Report Type"
                    value={ formatReportType( report?.report_type ) }
                    status={ report?.report_type ? 'complete' : 'error' }
                />

                { /* Visit Date */ }
                <SummaryCard
                    icon={ Calendar }
                    label="Visit Date"
                    value={ report?.visit_date ? formatDate( report.visit_date ) : 'Not set' }
                    status={ report?.visit_date ? 'complete' : 'error' }
                />

                { /* Checklist */ }
                <SummaryCard
                    icon={ ClipboardList }
                    label="Checklist Items"
                    value={ `${ checklistStats.total } completed (${ checklistStats.withNotes } with notes)` }
                    status={ checklistStats.total > 0 ? 'complete' : 'error' }
                />

                { /* Photos */ }
                <SummaryCard
                    icon={ Camera }
                    label="Photos"
                    value={ `${ photos.length } attached` }
                    status={ photos.length > 0 ? 'complete' : 'warning' }
                />

                { /* AI Summary */ }
                <SummaryCard
                    icon={ Sparkles }
                    label="AI Summary"
                    value={ report?.ai_summary ? 'Generated' : 'Not generated' }
                    status={ report?.ai_summary ? 'complete' : 'warning' }
                />
            </div>

            { /* Overall Rating Selection */ }
            <div className="bg-white rounded-xl border border-gray-200 p-4">
                <h3 className="font-medium text-gray-700 mb-2">Overall Rating</h3>
                { ! readOnly ? (
                    <>
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            { OVERALL_RATING_OPTIONS.map( ( option ) => {
                                const isSelected = currentOverallRating === option.value;
                                return (
                                    <button
                                        key={ option.value }
                                        type="button"
                                        onClick={ () =>
                                            useReportWizardStore
                                                .getState()
                                                .updateReportData( { overall_rating: option.value } )
                                        }
                                        className={ cn(
                                            'rounded-lg border px-3 py-2 text-sm font-semibold transition-colors',
                                            isSelected
                                                ? option.selectedClass
                                                : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                                        ) }
                                        aria-pressed={ isSelected }
                                    >
                                        { option.label }
                                    </button>
                                );
                            } ) }
                        </div>
                        { currentOverallRating === 'pending' && (
                            <p className="mt-2 text-xs text-amber-700">
                                Select a rating before submitting so the report does not remain pending.
                            </p>
                        ) }
                    </>
                ) : (
                    <p className="text-gray-600 capitalize">{ String( currentOverallRating ).replace( '_', ' ' ) }</p>
                ) }
            </div>

            { /* AI Summary Preview */ }
            { report?.ai_summary && (
                <div className="space-y-4">
                    <div className="bg-white rounded-xl border border-gray-200 p-6">
                        <h3 className="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                            <Sparkles className="w-5 h-5 text-primary-500" />
                            AI Summary Preview
                        </h3>
                        <div className="prose prose-sm max-w-none text-gray-700">
                            { report.ai_summary?.executive_summary ? (
                                <>
                                    { report.ai_summary.executive_summary
                                        .split( '\n' )
                                        .slice( 0, 3 )
                                        .map( ( p, i ) => (
                                            <p key={ i } className="mb-2">
                                                { p }
                                            </p>
                                        ) ) }
                                    { report.ai_summary.executive_summary.split( '\n' ).length > 3 && (
                                        <p className="text-gray-500 italic">... and more</p>
                                    ) }
                                </>
                            ) : (
                                <p className="text-gray-500 italic">No summary available.</p>
                            ) }
                        </div>
                    </div>

                    { /* Plan of Improvement Preview */ }
                    { report.ai_summary?.plan_of_improvement && report.ai_summary.plan_of_improvement.length > 0 && (
                        <div className="bg-white rounded-xl border border-gray-200 p-6">
                            <h3 className="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                                <ClipboardList className="w-5 h-5 text-amber-500" />
                                Plan of Improvement Preview
                            </h3>
                            <div className="space-y-3">
                                { report.ai_summary.plan_of_improvement.map( ( poi, idx ) => (
                                    <div
                                        key={ idx }
                                        className="bg-white rounded-lg p-4 border border-gray-100 shadow-sm"
                                    >
                                        <div className="flex items-start justify-between mb-2">
                                            <div className="flex items-center gap-2">
                                                <span
                                                    className={ cn(
                                                        'text-[10px] font-bold px-2 py-0.5 rounded-full uppercase',
                                                        poi.priority === 1 ||
                                                            String( poi.priority ).toLowerCase() === 'immediate'
                                                            ? 'bg-red-100 text-red-700'
                                                            : poi.priority === 2 ||
                                                              String( poi.priority ).toLowerCase() === 'high'
                                                            ? 'bg-orange-100 text-orange-700'
                                                            : 'bg-amber-100 text-amber-700'
                                                    ) }
                                                >
                                                    Priority { poi.priority || idx + 1 }
                                                </span>
                                                <span className="font-semibold text-sm text-gray-900">
                                                    { poi.area }
                                                </span>
                                            </div>
                                            { poi.timeline && (
                                                <span className="text-[10px] text-gray-500 bg-gray-50 px-1.5 py-0.5 rounded">
                                                    { poi.timeline }
                                                </span>
                                            ) }
                                        </div>

                                        { poi.current_status && (
                                            <p className="text-xs text-gray-500 italic mb-2 border-l-2 border-gray-100 pl-2">
                                                &quot;{ poi.current_status }&quot;
                                            </p>
                                        ) }

                                        { poi.action_steps && poi.action_steps.length > 0 && (
                                            <ul className="space-y-1">
                                                { poi.action_steps.map( ( step, sIdx ) => (
                                                    <li key={ sIdx } className="flex gap-2 text-[11px] text-gray-700">
                                                        <span className="text-primary-500 font-bold">•</span>
                                                        <span>{ step }</span>
                                                    </li>
                                                ) ) }
                                            </ul>
                                        ) }
                                    </div>
                                ) ) }
                            </div>
                        </div>
                    ) }
                </div>
            ) }

            { /* Closing Notes */ }
            <div className="bg-gray-50 rounded-xl border border-gray-200 p-4">
                <h3 className="font-medium text-gray-700 mb-2">Closing Notes</h3>
                { ! readOnly ? (
                    <textarea
                        className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-brand-primary outline-none min-h-[120px] resize-y bg-white text-gray-800"
                        placeholder="General observations, overall summary, or final remarks for this visit..."
                        value={ report.closing_notes || '' }
                        onChange={ ( e ) =>
                            useReportWizardStore.getState().updateReportData( { closing_notes: e.target.value } )
                        }
                    />
                ) : (
                    <p className="text-gray-600 whitespace-pre-wrap">
                        { report.closing_notes || 'No closing notes provided.' }
                    </p>
                ) }
            </div>

            { /* Full Details Toggle */ }
            <div className="pt-4 border-t border-brand-ink/5">
                <button
                    onClick={ () => setShowFullDetails( ! showFullDetails ) }
                    className="flex items-center gap-2 text-primary-600 font-bold hover:text-primary-700 transition-colors"
                >
                    <ClipboardList className="w-5 h-5" />
                    { showFullDetails ? 'Hide Detailed Results' : 'Show Detailed Results' }
                </button>
            </div>

            { showFullDetails && (
                <div className="mt-6 p-6 bg-white rounded-3xl border border-brand-ink/10 shadow-sm animate-fade-in">
                    <h3 className="text-xl font-serif font-bold text-brand-ink mb-6">Detailed Inspection Results</h3>
                    <StepChecklist draft={ report } readOnly={ true } />
                </div>
            ) }
        </div>
    );
}

function SummaryCard( { icon: Icon, label, value, status } ) {
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
        <div
            className={ cn(
                'rounded-xl border p-4 flex items-start gap-3',
                statusStyles[ status ] || statusStyles.default
            ) }
        >
            <Icon className={ cn( 'w-5 h-5 mt-0.5', iconStyles[ status ] || iconStyles.default ) } />
            <div>
                <p className="text-sm text-gray-500">{ label }</p>
                <p className="font-medium text-gray-900">{ value }</p>
            </div>
        </div>
    );
}

function formatReportType( type ) {
    const labels = {
        'tier-1': 'Tier 1 Assessment',
        'tier-2': 'Tier 2 CQI',
        'follow-up': 'Follow-up Visit',
    };
    return labels[ type ] || type || 'Not selected';
}

export default StepReview;
