import React, { useEffect, useMemo, useState } from 'react';
import {
    AlertTriangle,
    Clock3,
    Eye,
    GitCompare,
    History,
    Loader2,
    RotateCcw,
    ShieldAlert,
} from 'lucide-react';
import useUIStore from '@stores/useUIStore';
import {
    useCompareReportVersions,
    useReportVersion,
    useReportVersions,
    useRestoreReportVersion,
} from '@hooks/useQueries';

const IMPORTANT_REPORT_FIELDS = [
    [ 'school_id', 'School' ],
    [ 'report_type', 'Report Type' ],
    [ 'inspection_date', 'Inspection Date' ],
    [ 'previous_report_id', 'Compare With' ],
    [ 'overall_rating', 'Overall Rating' ],
    [ 'status', 'Status' ],
    [ 'closing_notes', 'Closing Notes' ],
];

const formatDateTime = ( value ) => {
    if ( ! value ) {
        return 'Unknown';
    }

    const parsed = new Date( value );
    if ( Number.isNaN( parsed.getTime() ) ) {
        return value;
    }

    return parsed.toLocaleString();
};

const timeAgo = ( value ) => {
    if ( ! value ) {
        return 'Unknown';
    }

    const parsed = new Date( value );
    if ( Number.isNaN( parsed.getTime() ) ) {
        return value;
    }

    const seconds = Math.floor( ( Date.now() - parsed.getTime() ) / 1000 );
    const intervals = [
        [ 'year', 31536000 ],
        [ 'month', 2592000 ],
        [ 'day', 86400 ],
        [ 'hour', 3600 ],
        [ 'minute', 60 ],
    ];

    for ( const [ label, interval ] of intervals ) {
        const count = Math.floor( seconds / interval );
        if ( count >= 1 ) {
            return `${ count } ${ label }${ count === 1 ? '' : 's' } ago`;
        }
    }

    return 'Just now';
};

const getSnapshotPayload = ( snapshot ) => snapshot?.snapshot_data || {};

const countResponses = ( responses ) =>
    Object.values( responses || {} ).reduce( ( total, section ) => {
        if ( ! section || typeof section !== 'object' ) {
            return total;
        }

        return total + Object.keys( section ).length;
    }, 0 );

const getSnapshotWarnings = ( snapshot ) => {
    const payload = getSnapshotPayload( snapshot );
    const warnings = [];

    if ( ! payload.snapshot_meta?.plugin_version ) {
        warnings.push( 'Legacy snapshot: this version may not include every field captured by newer restores.' );
    }

    if ( ! payload.report || typeof payload.report !== 'object' || Array.isArray( payload.report ) ) {
        warnings.push( 'Report metadata may be incomplete for this version.' );
    }

    if ( ! payload.responses || typeof payload.responses !== 'object' || Array.isArray( payload.responses ) ) {
        warnings.push( 'Checklist response coverage may be incomplete for this version.' );
    }

    if ( ! Array.isArray( payload.photos ) ) {
        warnings.push( 'Photo references may be incomplete for this version.' );
    }

    return warnings;
};

const summarizeSnapshot = ( snapshot ) => {
    const payload = getSnapshotPayload( snapshot );
    return {
        reportType: payload.report?.report_type || 'Unknown',
        inspectionDate: payload.report?.inspection_date || 'Unknown',
        responseCount: countResponses( payload.responses ),
        photoCount: Array.isArray( payload.photos ) ? payload.photos.length : 0,
        hasAiSummary: !! payload.ai_summary,
    };
};

const compareReportFields = ( previousSnapshot, currentSnapshot ) => {
    const previousReport = getSnapshotPayload( previousSnapshot ).report || {};
    const currentReport = getSnapshotPayload( currentSnapshot ).report || {};

    return IMPORTANT_REPORT_FIELDS.reduce( ( changes, [ field, label ] ) => {
        const previousValue = previousReport[ field ] ?? '';
        const currentValue = currentReport[ field ] ?? '';

        if ( previousValue !== currentValue ) {
            changes.push( {
                field,
                label,
                previousValue: previousValue || '(empty)',
                currentValue: currentValue || '(empty)',
            } );
        }

        return changes;
    }, [] );
};

const compareResponses = ( previousSnapshot, currentSnapshot ) => {
    const previousResponses = getSnapshotPayload( previousSnapshot ).responses || {};
    const currentResponses = getSnapshotPayload( currentSnapshot ).responses || {};
    const sectionKeys = new Set( [ ...Object.keys( previousResponses ), ...Object.keys( currentResponses ) ] );
    const changes = [];

    sectionKeys.forEach( ( sectionKey ) => {
        const previousItems = previousResponses[ sectionKey ] || {};
        const currentItems = currentResponses[ sectionKey ] || {};
        const itemKeys = new Set( [ ...Object.keys( previousItems ), ...Object.keys( currentItems ) ] );

        itemKeys.forEach( ( itemKey ) => {
            const previousRating = previousItems[ itemKey ]?.rating || '';
            const currentRating = currentItems[ itemKey ]?.rating || '';

            if ( previousRating !== currentRating ) {
                changes.push( {
                    key: `${ sectionKey }/${ itemKey }`,
                    sectionKey,
                    itemKey,
                    previousRating: previousRating || 'N/A',
                    currentRating: currentRating || 'N/A',
                } );
            }
        } );
    } );

    return changes;
};

const comparePhotos = ( previousSnapshot, currentSnapshot ) => {
    const previousPhotos = Array.isArray( getSnapshotPayload( previousSnapshot ).photos )
        ? getSnapshotPayload( previousSnapshot ).photos
        : [];
    const currentPhotos = Array.isArray( getSnapshotPayload( currentSnapshot ).photos )
        ? getSnapshotPayload( currentSnapshot ).photos
        : [];

    const toKey = ( photo ) =>
        photo?.id || `${ photo?.section_key || 'general' }/${ photo?.filename || photo?.caption || 'unknown' }`;

    const previousKeys = new Set( previousPhotos.map( toKey ) );
    const currentKeys = new Set( currentPhotos.map( toKey ) );

    return {
        added: [ ...currentKeys ].filter( ( key ) => ! previousKeys.has( key ) ),
        removed: [ ...previousKeys ].filter( ( key ) => ! currentKeys.has( key ) ),
    };
};

const DetailModal = ( { title, open, onClose, children } ) => {
    if ( ! open ) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-[100000] bg-black/50 p-4 backdrop-blur-sm flex items-center justify-center">
            <div className="w-full max-w-4xl max-h-[90vh] overflow-hidden rounded-3xl bg-white shadow-2xl border border-brand-ink/10">
                <div className="flex items-center justify-between px-6 py-4 border-b border-brand-ink/10">
                    <h3 className="text-xl font-serif font-bold text-brand-ink">{ title }</h3>
                    <button
                        type="button"
                        onClick={ onClose }
                        className="rounded-2xl border border-brand-ink/10 px-4 py-2 text-sm font-bold text-brand-ink hover:bg-brand-ink/5 transition-all"
                    >
                        Close
                    </button>
                </div>
                <div className="max-h-[calc(90vh-72px)] overflow-y-auto p-6 bg-brand-cream/20">{ children }</div>
            </div>
        </div>
    );
};

const VersionHistoryPanel = ( {
    reportId,
    currentVersion: currentVersionProp,
    currentUpdatedAt = null,
    canRestore = true,
    hasUnsavedChanges = false,
    isBusy = false,
    onRestoreSuccess,
} ) => {
    const { addToast } = useUIStore();
    const [ compareVersion, setCompareVersion ] = useState( null );
    const [ restoreVersion, setRestoreVersion ] = useState( null );
    const [ acknowledgeRestore, setAcknowledgeRestore ] = useState( false );

    const versionsQuery = useReportVersions( reportId );
    const restoreMutation = useRestoreReportVersion();

    const currentVersion = Math.max(
        Number( versionsQuery.data?.current_version || 0 ),
        Number( currentVersionProp || 0 )
    ) || null;
    const versions = versionsQuery.data?.versions || [];

    const compareQuery = useCompareReportVersions(
        reportId,
        currentVersion,
        compareVersion,
        Boolean( reportId && currentVersion && compareVersion )
    );

    const restoreSnapshotQuery = useReportVersion( reportId, restoreVersion, Boolean( reportId && restoreVersion ) );

    useEffect( () => {
        if ( ! restoreVersion ) {
            setAcknowledgeRestore( false );
        }
    }, [ restoreVersion ] );

    const compareSummary = useMemo( () => {
        if ( ! compareQuery.data?.current || ! compareQuery.data?.compare ) {
            return null;
        }

        return {
            reportChanges: compareReportFields( compareQuery.data.compare, compareQuery.data.current ),
            responseChanges: compareResponses( compareQuery.data.compare, compareQuery.data.current ),
            photoChanges: comparePhotos( compareQuery.data.compare, compareQuery.data.current ),
            currentWarnings: getSnapshotWarnings( compareQuery.data.current ),
            compareWarnings: getSnapshotWarnings( compareQuery.data.compare ),
        };
    }, [ compareQuery.data ] );

    const restoreWarnings = useMemo(
        () => getSnapshotWarnings( restoreSnapshotQuery.data ),
        [ restoreSnapshotQuery.data ]
    );

    const restoreSummary = useMemo(
        () => summarizeSnapshot( restoreSnapshotQuery.data ),
        [ restoreSnapshotQuery.data ]
    );

    const getRestoreErrorMessage = ( error ) => {
        if ( error?.status === 409 ) {
            return 'This report changed on the server before the restore completed. Refresh the report and try again.';
        }

        if ( error?.status === 403 ) {
            return 'You do not have permission to restore this version.';
        }

        if ( error?.status === 404 ) {
            return 'This version is no longer available to restore.';
        }

        return error?.message || 'Failed to restore the selected version.';
    };

    const handleRestoreConfirm = async () => {
        if ( ! reportId || ! restoreVersion ) {
            return;
        }

        try {
            await restoreMutation.mutateAsync( {
                id: reportId,
                version: restoreVersion,
                currentVersion,
                updatedAt: currentUpdatedAt,
            } );

            if ( onRestoreSuccess ) {
                await onRestoreSuccess( restoreVersion );
            }

            setRestoreVersion( null );
            addToast( {
                type: 'success',
                message: `Restored report to version ${ restoreVersion }.`,
            } );
        } catch ( error ) {
            addToast( {
                type: 'error',
                message: getRestoreErrorMessage( error ),
            } );
        }
    };

    const renderWarningBox = ( warning ) => (
        <div
            key={ warning }
            className="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 flex items-start gap-2"
        >
            <ShieldAlert className="h-4 w-4 mt-0.5 flex-shrink-0" />
            <span>{ warning }</span>
        </div>
    );

    return (
        <>
            <section className="mt-8 rounded-3xl border border-brand-ink/10 bg-white/90 shadow-sm overflow-hidden">
                <div className="px-6 py-5 border-b border-brand-ink/10 bg-brand-cream/40 flex items-center justify-between gap-4">
                    <div className="min-w-0">
                        <h3 className="text-xl font-serif font-bold text-brand-ink flex items-center gap-2">
                            <History className="h-5 w-5 text-cqa-brand-secondary" />
                            Version History
                        </h3>
                        <p className="mt-1 text-sm text-brand-ink/60">
                            Compare and restore saved report snapshots without leaving the editor.
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={ () => versionsQuery.refetch() }
                        disabled={ versionsQuery.isFetching }
                        className="rounded-2xl border border-brand-ink/10 px-4 py-2 text-sm font-bold text-brand-ink hover:bg-brand-ink/5 transition-all disabled:opacity-50"
                    >
                        { versionsQuery.isFetching ? 'Refreshing...' : 'Refresh' }
                    </button>
                </div>

                <div className="p-6 space-y-4">
                    { versionsQuery.isLoading ? (
                        <div className="rounded-2xl border border-brand-ink/10 bg-brand-cream/30 px-4 py-8 text-sm text-brand-ink/60 flex items-center justify-center gap-2">
                            <Loader2 className="h-4 w-4 animate-spin" />
                            Loading version history...
                        </div>
                    ) : null }

                    { versionsQuery.isError ? (
                        <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-800 flex items-start gap-2">
                            <AlertTriangle className="h-4 w-4 mt-0.5 flex-shrink-0" />
                            <span>Failed to load version history for this report.</span>
                        </div>
                    ) : null }

                    { ! versionsQuery.isLoading && ! versions.length ? (
                        <div className="rounded-2xl border border-brand-ink/10 bg-brand-cream/20 px-4 py-6 text-sm text-brand-ink/60">
                            No version history is available for this report yet.
                        </div>
                    ) : null }

                    { versions.map( ( version ) => {
                        const versionNumber = Number( version.version_number );
                        const isCurrent = currentVersion === versionNumber;

                        return (
                            <article
                                key={ version.id || version.version_number }
                                className="rounded-2xl border border-brand-ink/10 bg-white px-4 py-4 shadow-sm"
                            >
                                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                    <div className="min-w-0">
                                        <div className="flex items-center gap-2 flex-wrap">
                                            <span className="inline-flex items-center rounded-full bg-cqa-brand-secondary/10 px-3 py-1 text-xs font-bold uppercase tracking-wide text-cqa-brand-secondary">
                                                v{ versionNumber }
                                            </span>
                                            { isCurrent ? (
                                                <span className="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-green-800">
                                                    Current
                                                </span>
                                            ) : null }
                                            <span className="inline-flex items-center gap-1 text-xs text-brand-ink/50">
                                                <Clock3 className="h-3 w-3" />
                                                { timeAgo( version.created_at ) }
                                            </span>
                                        </div>
                                        <p className="mt-2 text-sm font-medium text-brand-ink">
                                            { version.change_summary || 'Report updated' }
                                        </p>
                                        <p className="mt-1 text-xs text-brand-ink/55">
                                            { formatDateTime( version.created_at ) }
                                            { version.user_name ? ` by ${ version.user_name }` : '' }
                                        </p>
                                    </div>

                                    <div className="flex flex-wrap gap-2">
                                        { ! isCurrent ? (
                                            <button
                                                type="button"
                                                onClick={ () => setCompareVersion( versionNumber ) }
                                                className="rounded-2xl border border-brand-ink/10 px-4 py-2 text-sm font-bold text-brand-ink hover:bg-brand-ink/5 transition-all flex items-center gap-2"
                                            >
                                                <GitCompare className="h-4 w-4" />
                                                Compare
                                            </button>
                                        ) : null }
                                        { canRestore && ! isCurrent ? (
                                            <button
                                                type="button"
                                                onClick={ () => setRestoreVersion( versionNumber ) }
                                                disabled={ isBusy || restoreMutation.isPending }
                                                className="rounded-2xl border border-amber-300 px-4 py-2 text-sm font-bold text-amber-800 hover:bg-amber-50 transition-all flex items-center gap-2 disabled:opacity-50"
                                            >
                                                <RotateCcw className="h-4 w-4" />
                                                Restore
                                            </button>
                                        ) : null }
                                    </div>
                                </div>
                            </article>
                        );
                    } ) }
                </div>
            </section>

            <DetailModal
                title={ compareVersion ? `Compare Version ${ compareVersion } to Current` : 'Compare Versions' }
                open={ !! compareVersion }
                onClose={ () => setCompareVersion( null ) }
            >
                { compareQuery.isLoading ? (
                    <div className="flex items-center justify-center gap-2 py-10 text-brand-ink/60">
                        <Loader2 className="h-5 w-5 animate-spin" />
                        Loading comparison...
                    </div>
                ) : null }

                { compareQuery.isError ? (
                    <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-800">
                        Failed to load the selected versions for comparison.
                    </div>
                ) : null }

                { compareSummary ? (
                    <div className="space-y-6">
                        { compareSummary.compareWarnings.map( renderWarningBox ) }
                        { compareSummary.currentWarnings.map( renderWarningBox ) }

                        <div className="grid gap-4 md:grid-cols-3">
                            <div className="rounded-2xl border border-brand-ink/10 bg-white px-4 py-4">
                                <div className="text-xs font-bold uppercase tracking-wide text-brand-ink/50">
                                    Report Fields Changed
                                </div>
                                <div className="mt-2 text-2xl font-bold text-brand-ink">
                                    { compareSummary.reportChanges.length }
                                </div>
                            </div>
                            <div className="rounded-2xl border border-brand-ink/10 bg-white px-4 py-4">
                                <div className="text-xs font-bold uppercase tracking-wide text-brand-ink/50">
                                    Checklist Ratings Changed
                                </div>
                                <div className="mt-2 text-2xl font-bold text-brand-ink">
                                    { compareSummary.responseChanges.length }
                                </div>
                            </div>
                            <div className="rounded-2xl border border-brand-ink/10 bg-white px-4 py-4">
                                <div className="text-xs font-bold uppercase tracking-wide text-brand-ink/50">
                                    Photo Changes
                                </div>
                                <div className="mt-2 text-2xl font-bold text-brand-ink">
                                    { compareSummary.photoChanges.added.length + compareSummary.photoChanges.removed.length }
                                </div>
                            </div>
                        </div>

                        <div className="space-y-4">
                            <div className="rounded-2xl border border-brand-ink/10 bg-white px-4 py-4">
                                <h4 className="font-bold text-brand-ink">Report Details</h4>
                                { compareSummary.reportChanges.length ? (
                                    <div className="mt-3 space-y-3">
                                        { compareSummary.reportChanges.map( ( change ) => (
                                            <div key={ change.field } className="rounded-2xl bg-brand-cream/40 px-3 py-3">
                                                <div className="text-xs font-bold uppercase tracking-wide text-brand-ink/50">
                                                    { change.label }
                                                </div>
                                                <div className="mt-2 text-sm text-brand-ink/70">
                                                    <span className="font-medium text-brand-ink">{ change.previousValue }</span>
                                                    <span className="mx-2 text-brand-ink/40">{ '->' }</span>
                                                    <span className="font-medium text-brand-ink">{ change.currentValue }</span>
                                                </div>
                                            </div>
                                        ) ) }
                                    </div>
                                ) : (
                                    <p className="mt-3 text-sm text-brand-ink/60">No report field changes detected.</p>
                                ) }
                            </div>

                            <div className="rounded-2xl border border-brand-ink/10 bg-white px-4 py-4">
                                <h4 className="font-bold text-brand-ink">Checklist Changes</h4>
                                { compareSummary.responseChanges.length ? (
                                    <div className="mt-3 space-y-2">
                                        { compareSummary.responseChanges.slice( 0, 25 ).map( ( change ) => (
                                            <div
                                                key={ change.key }
                                                className="rounded-2xl bg-brand-cream/40 px-3 py-3 text-sm text-brand-ink/75"
                                            >
                                                <div className="font-semibold text-brand-ink">
                                                    { change.sectionKey } / { change.itemKey }
                                                </div>
                                                <div className="mt-1">
                                                    { change.previousRating }
                                                    <span className="mx-2 text-brand-ink/40">{ '->' }</span>
                                                    { change.currentRating }
                                                </div>
                                            </div>
                                        ) ) }
                                        { compareSummary.responseChanges.length > 25 ? (
                                            <p className="text-xs text-brand-ink/55">
                                                Showing the first 25 checklist changes out of { compareSummary.responseChanges.length }.
                                            </p>
                                        ) : null }
                                    </div>
                                ) : (
                                    <p className="mt-3 text-sm text-brand-ink/60">No checklist rating changes detected.</p>
                                ) }
                            </div>

                            <div className="rounded-2xl border border-brand-ink/10 bg-white px-4 py-4">
                                <h4 className="font-bold text-brand-ink">Photo Changes</h4>
                                <div className="mt-3 grid gap-3 md:grid-cols-2">
                                    <div className="rounded-2xl bg-green-50 px-3 py-3 text-sm text-green-900">
                                        <div className="font-semibold">Added</div>
                                        <div className="mt-1">{ compareSummary.photoChanges.added.length }</div>
                                    </div>
                                    <div className="rounded-2xl bg-red-50 px-3 py-3 text-sm text-red-900">
                                        <div className="font-semibold">Removed</div>
                                        <div className="mt-1">{ compareSummary.photoChanges.removed.length }</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                ) : null }
            </DetailModal>

            <DetailModal
                title={ restoreVersion ? `Restore Version ${ restoreVersion }` : 'Restore Version' }
                open={ !! restoreVersion }
                onClose={ () => {
                    if ( ! restoreMutation.isPending ) {
                        setRestoreVersion( null );
                    }
                } }
            >
                { restoreSnapshotQuery.isLoading ? (
                    <div className="flex items-center justify-center gap-2 py-10 text-brand-ink/60">
                        <Loader2 className="h-5 w-5 animate-spin" />
                        Loading version details...
                    </div>
                ) : null }

                { restoreSnapshotQuery.isError ? (
                    <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-800">
                        Failed to load the selected version for restore.
                    </div>
                ) : null }

                { restoreSnapshotQuery.data ? (
                    <div className="space-y-5">
                        <div className="rounded-2xl border border-brand-ink/10 bg-white px-4 py-4">
                            <div className="flex items-center gap-2 text-brand-ink">
                                <Eye className="h-4 w-4 text-cqa-brand-secondary" />
                                <span className="font-bold">Restore Preview</span>
                            </div>
                            <div className="mt-4 grid gap-4 md:grid-cols-2">
                                <div>
                                    <div className="text-xs font-bold uppercase tracking-wide text-brand-ink/50">
                                        Report Type
                                    </div>
                                    <div className="mt-1 text-sm text-brand-ink">{ restoreSummary.reportType }</div>
                                </div>
                                <div>
                                    <div className="text-xs font-bold uppercase tracking-wide text-brand-ink/50">
                                        Inspection Date
                                    </div>
                                    <div className="mt-1 text-sm text-brand-ink">{ restoreSummary.inspectionDate }</div>
                                </div>
                                <div>
                                    <div className="text-xs font-bold uppercase tracking-wide text-brand-ink/50">
                                        Checklist Responses
                                    </div>
                                    <div className="mt-1 text-sm text-brand-ink">{ restoreSummary.responseCount }</div>
                                </div>
                                <div>
                                    <div className="text-xs font-bold uppercase tracking-wide text-brand-ink/50">
                                        Photos
                                    </div>
                                    <div className="mt-1 text-sm text-brand-ink">{ restoreSummary.photoCount }</div>
                                </div>
                            </div>
                            <div className="mt-4 text-sm text-brand-ink/65">
                                Restoring creates a fresh new current version after the selected snapshot is applied.
                            </div>
                        </div>

                        { restoreWarnings.map( renderWarningBox ) }

                        { hasUnsavedChanges ? (
                            <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-900">
                                <div className="flex items-start gap-2">
                                    <AlertTriangle className="h-4 w-4 mt-0.5 flex-shrink-0" />
                                    <div>
                                        <p className="font-semibold">Unsaved local edits detected</p>
                                        <p className="mt-1">
                                            Restoring this version will replace the current draft shown in the editor.
                                        </p>
                                    </div>
                                </div>
                                <label className="mt-4 flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        checked={ acknowledgeRestore }
                                        onChange={ ( event ) => setAcknowledgeRestore( event.target.checked ) }
                                        className="mt-1 h-4 w-4 rounded border-brand-ink/20 text-chroma-red focus:ring-chroma-red"
                                    />
                                    <span>
                                        I understand this restore will replace my current unsaved draft with the selected version.
                                    </span>
                                </label>
                            </div>
                        ) : null }

                        <div className="flex justify-end gap-3">
                            <button
                                type="button"
                                onClick={ () => setRestoreVersion( null ) }
                                className="rounded-2xl border border-brand-ink/10 px-5 py-2.5 text-sm font-bold text-brand-ink hover:bg-brand-ink/5 transition-all"
                                disabled={ restoreMutation.isPending }
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                onClick={ handleRestoreConfirm }
                                disabled={
                                    restoreMutation.isPending ||
                                    isBusy ||
                                    ( hasUnsavedChanges && ! acknowledgeRestore )
                                }
                                className="rounded-2xl bg-amber-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-amber-700 transition-all disabled:opacity-50 flex items-center gap-2"
                            >
                                { restoreMutation.isPending ? (
                                    <>
                                        <Loader2 className="h-4 w-4 animate-spin" />
                                        Restoring...
                                    </>
                                ) : (
                                    <>
                                        <RotateCcw className="h-4 w-4" />
                                        Restore Version
                                    </>
                                ) }
                            </button>
                        </div>
                    </div>
                ) : null }
            </DetailModal>
        </>
    );
};

export default VersionHistoryPanel;
