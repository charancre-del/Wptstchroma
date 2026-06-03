/**
 * React Query hooks for data fetching
 */
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@api/client';

// Query key factories
export const queryKeys = {
    schools: {
        all: [ 'schools' ],
        list: ( filters ) => [ 'schools', 'list', filters ],
        detail: ( id ) => [ 'schools', 'detail', id ],
    },
    reports: {
        all: [ 'reports' ],
        list: ( filters ) => [ 'reports', 'list', filters ],
        detail: ( id ) => [ 'reports', 'detail', id ],
        checklist: ( id ) => [ 'reports', id, 'checklist' ],
        versions: ( id ) => [ 'reports', id, 'versions' ],
        version: ( id, version ) => [ 'reports', id, 'versions', version ],
        compare: ( id, currentVersion, compareVersion ) => [
            'reports',
            id,
            'versions',
            'compare',
            currentVersion,
            compareVersion,
        ],
    },
    user: {
        me: [ 'user', 'me' ],
    },
};

// ============ School Queries ============

/**
 * Fetch all schools
 * @param filters
 */
export function useSchools( filters = {} ) {
    return useQuery( {
        queryKey: queryKeys.schools.list( filters ),
        queryFn: () => apiClient.get( '/schools', { params: filters } ),
        staleTime: 5 * 60 * 1000, // 5 minutes
    } );
}

/**
 * Fetch a single school by ID
 * @param id
 */
export function useSchool( id ) {
    return useQuery( {
        queryKey: queryKeys.schools.detail( id ),
        queryFn: () => apiClient.get( `/schools/${ id }` ),
        enabled: !! id,
    } );
}

/**
 * Create a new school
 */
export function useCreateSchool() {
    const queryClient = useQueryClient();

    return useMutation( {
        mutationFn: ( data ) => apiClient.post( '/schools', data ),
        onSuccess: () => {
            queryClient.invalidateQueries( { queryKey: queryKeys.schools.all } );
        },
    } );
}

/**
 * Update a school
 */
export function useUpdateSchool() {
    const queryClient = useQueryClient();

    return useMutation( {
        mutationFn: ( { id, ...data } ) => apiClient.put( `/schools/${ id }`, data ),
        onSuccess: ( _, variables ) => {
            queryClient.invalidateQueries( { queryKey: queryKeys.schools.detail( variables.id ) } );
            queryClient.invalidateQueries( { queryKey: queryKeys.schools.all } );
        },
    } );
}

// ============ Report Queries ============

/**
 * Fetch all reports
 * @param filters
 */
export function useReports( filters = {} ) {
    return useQuery( {
        queryKey: queryKeys.reports.list( filters ),
        queryFn: () => apiClient.get( '/reports', { params: filters } ),
        staleTime: 2 * 60 * 1000, // 2 minutes
    } );
}

/**
 * Fetch a single report by ID
 * @param id
 */
export function useReport( id ) {
    return useQuery( {
        queryKey: queryKeys.reports.detail( id ),
        queryFn: () => apiClient.get( `/reports/${ id }` ),
        enabled: !! id,
        staleTime: 60 * 1000, // 1 minute stale time to prevent unnecessary re-fetches during wizard steps
    } );
}

/**
 * Fetch report version list
 * @param id
 */
export function useReportVersions( id ) {
    return useQuery( {
        queryKey: queryKeys.reports.versions( id ),
        queryFn: () => apiClient.get( `/reports/${ id }/versions` ),
        enabled: !! id,
        staleTime: 30 * 1000,
    } );
}

/**
 * Fetch a single report version snapshot
 * @param id
 * @param version
 * @param enabled
 */
export function useReportVersion( id, version, enabled = true ) {
    return useQuery( {
        queryKey: queryKeys.reports.version( id, version ),
        queryFn: () => apiClient.get( `/reports/${ id }/versions/${ version }` ),
        enabled: !! id && !! version && enabled,
        staleTime: 30 * 1000,
    } );
}

/**
 * Fetch two versions for comparison in one stable query
 * @param id
 * @param currentVersion
 * @param compareVersion
 * @param enabled
 */
export function useCompareReportVersions( id, currentVersion, compareVersion, enabled = true ) {
    return useQuery( {
        queryKey: queryKeys.reports.compare( id, currentVersion, compareVersion ),
        queryFn: async () => {
            const [ current, compare ] = await Promise.all( [
                apiClient.get( `/reports/${ id }/versions/${ currentVersion }` ),
                apiClient.get( `/reports/${ id }/versions/${ compareVersion }` ),
            ] );

            return { current, compare };
        },
        enabled: !! id && !! currentVersion && !! compareVersion && enabled,
        staleTime: 30 * 1000,
    } );
}

/**
 * Fetch report checklist template
 * @param reportType
 */
export function useReportChecklist( reportType ) {
    return useQuery( {
        queryKey: [ 'checklist', reportType ],
        queryFn: () => apiClient.get( `/checklists/${ reportType }` ),
        enabled: !! reportType,
        staleTime: 30 * 60 * 1000, // 30 minutes - checklists don't change often
    } );
}

/**
 * Create a new report
 */
export function useCreateReport() {
    const queryClient = useQueryClient();

    return useMutation( {
        mutationFn: ( data ) => apiClient.post( '/reports', data ),
        onSuccess: ( response ) => {
            queryClient.invalidateQueries( { queryKey: queryKeys.reports.all } );
            if ( response?.id ) {
                queryClient.invalidateQueries( { queryKey: queryKeys.reports.detail( response.id ) } );
                queryClient.invalidateQueries( { queryKey: queryKeys.reports.versions( response.id ) } );
            }
        },
    } );
}

/**
 * Update a report
 */
export function useUpdateReport() {
    const queryClient = useQueryClient();

    return useMutation( {
        mutationFn: ( { id, version_id, updated_at, updatedAt, ...data } ) =>
            apiClient.put( `/reports/${ id }`, data, {
                headers: version_id ? { 'X-CQA-Version': version_id } : {},
                ifUnmodifiedSince: updated_at || updatedAt || undefined,
            } ),
        onSuccess: ( _, variables ) => {
            queryClient.invalidateQueries( { queryKey: queryKeys.reports.detail( variables.id ) } );
            queryClient.invalidateQueries( { queryKey: queryKeys.reports.versions( variables.id ) } );
            queryClient.invalidateQueries( { queryKey: queryKeys.reports.all } );
        },
    } );
}

/**
 * Restore a report to a previous version
 */
export function useRestoreReportVersion() {
    const queryClient = useQueryClient();

    return useMutation( {
        mutationFn: ( { id, version, currentVersion, updatedAt } ) =>
            apiClient.post(
                `/reports/${ id }/restore/${ version }`,
                {},
                {
                    headers: currentVersion ? { 'X-CQA-Version': currentVersion } : {},
                    ifUnmodifiedSince: updatedAt || undefined,
                }
            ),
        onSuccess: ( _, variables ) => {
            queryClient.invalidateQueries( { queryKey: queryKeys.reports.detail( variables.id ) } );
            queryClient.invalidateQueries( { queryKey: queryKeys.reports.versions( variables.id ) } );
            queryClient.invalidateQueries( { queryKey: queryKeys.reports.all } );
        },
    } );
}

/**
 * Restore a single report field or checklist item from a previous version
 */
export function useRestoreReportVersionSelection() {
    const queryClient = useQueryClient();

    return useMutation( {
        mutationFn: ( { id, version, currentVersion, updatedAt, selection } ) =>
            apiClient.post(
                `/reports/${ id }/versions/${ version }/restore-selection`,
                {
                    ...selection,
                    version_id: currentVersion || undefined,
                },
                {
                    headers: currentVersion ? { 'X-CQA-Version': currentVersion } : {},
                    ifUnmodifiedSince: updatedAt || undefined,
                }
            ),
        onSuccess: ( _, variables ) => {
            queryClient.invalidateQueries( { queryKey: queryKeys.reports.detail( variables.id ) } );
            queryClient.invalidateQueries( { queryKey: queryKeys.reports.versions( variables.id ) } );
            queryClient.invalidateQueries( { queryKey: queryKeys.reports.all } );
        },
    } );
}

function normalizeReportMutationInput( report ) {
    return typeof report === 'object' ? report : { id: report };
}

function getReportId( report ) {
    return typeof report === 'object' ? report.id : report;
}

function normalizeReportMutationResponse( response ) {
    if ( ! response || typeof response !== 'object' ) {
        return null;
    }

    if ( response.data && typeof response.data === 'object' && ! Array.isArray( response.data ) ) {
        return response.data;
    }

    return response;
}

function patchReportInCache( cachedValue, reportId, patch ) {
    if ( ! cachedValue || ! reportId || ! patch ) {
        return cachedValue;
    }

    const shouldPatch = ( item ) => String( item?.id ) === String( reportId );
    const mergeReport = ( item ) => ( shouldPatch( item ) ? { ...item, ...patch } : item );

    if ( Array.isArray( cachedValue ) ) {
        return cachedValue.map( mergeReport );
    }

    if ( Array.isArray( cachedValue.data ) ) {
        return {
            ...cachedValue,
            data: cachedValue.data.map( mergeReport ),
        };
    }

    if ( shouldPatch( cachedValue ) ) {
        return {
            ...cachedValue,
            ...patch,
        };
    }

    return cachedValue;
}

function reportMatchesFilters( report, filters = {} ) {
    if ( ! report || ! filters || typeof filters !== 'object' ) {
        return true;
    }

    if ( filters.status && report.status !== filters.status ) {
        return false;
    }

    const typeFilter = filters.type || filters.report_type;
    if ( typeFilter && report.report_type !== typeFilter ) {
        return false;
    }

    if ( filters.school_id && String( report.school_id ) !== String( filters.school_id ) ) {
        return false;
    }

    if ( filters.author === 'me' && report.is_mine !== true ) {
        return false;
    }

    return true;
}

function patchReportListInCache( cachedValue, reportId, patch, filters = {} ) {
    if ( ! cachedValue || ! reportId || ! patch ) {
        return cachedValue;
    }

    const patchItem = ( item ) => {
        if ( String( item?.id ) !== String( reportId ) ) {
            return item;
        }

        return { ...item, ...patch };
    };

    const patchList = ( list ) => {
        const patched = list.map( patchItem );
        return patched.filter(
            ( item ) => String( item?.id ) !== String( reportId ) || reportMatchesFilters( item, filters )
        );
    };

    if ( Array.isArray( cachedValue ) ) {
        return patchList( cachedValue );
    }

    if ( Array.isArray( cachedValue.data ) ) {
        const nextData = patchList( cachedValue.data );
        const removedCount = cachedValue.data.length - nextData.length;

        return {
            ...cachedValue,
            data: nextData,
            total:
                typeof cachedValue.total === 'number'
                    ? Math.max( 0, cachedValue.total - removedCount )
                    : cachedValue.total,
        };
    }

    return cachedValue;
}

function syncReportStatusCaches( queryClient, report, updatedReport, fallbackStatus ) {
    const reportId = getReportId( report );
    const originalReport = normalizeReportMutationInput( report );
    const normalizedUpdatedReport = normalizeReportMutationResponse( updatedReport );
    const patch =
        normalizedUpdatedReport && typeof normalizedUpdatedReport === 'object'
            ? {
                  ...originalReport,
                  ...normalizedUpdatedReport,
                  status: normalizedUpdatedReport.status || fallbackStatus,
              }
            : { ...originalReport, id: reportId, status: fallbackStatus };

    queryClient.getQueriesData( { queryKey: [ 'reports', 'list' ] } ).forEach( ( [ queryKey ] ) => {
        const filters = queryKey?.[ 2 ] && typeof queryKey[ 2 ] === 'object' ? queryKey[ 2 ] : {};

        queryClient.setQueryData( queryKey, ( cachedValue ) =>
            patchReportListInCache( cachedValue, reportId, patch, filters )
        );
    } );

    queryClient.setQueryData( queryKeys.reports.detail( reportId ), ( cachedValue ) =>
        patchReportInCache( cachedValue, reportId, patch )
    );

    return patch;
}

function buildStatusChangeRequestConfig( report ) {
    const normalizedReport = normalizeReportMutationInput( report );

    return {
        headers: normalizedReport.version_id ? { 'X-CQA-Version': normalizedReport.version_id } : {},
        ifUnmodifiedSince: normalizedReport.updated_at || normalizedReport.updatedAt || undefined,
    };
}

async function performStatusChange( report, status ) {
    const normalizedReport = normalizeReportMutationInput( report );
    const endpoint = `/reports/${ normalizedReport.id }`;
    const body = { status, save_mode: 'status_change' };

    try {
        return await apiClient.put( endpoint, body, buildStatusChangeRequestConfig( normalizedReport ) );
    } catch ( error ) {
        if ( error?.status !== 409 || ! normalizedReport.id ) {
            throw error;
        }

        let latestReport;

        try {
            latestReport = await apiClient.get( `/reports/${ normalizedReport.id }` );
        } catch {
            throw error;
        }

        if ( ! latestReport || typeof latestReport !== 'object' ) {
            throw error;
        }

        if ( latestReport.status === status ) {
            return latestReport;
        }

        const canRetry =
            ( status === 'submitted' && latestReport.status === 'draft' ) ||
            ( status === 'approved' && latestReport.status === 'submitted' ) ||
            ( status === 'draft' && latestReport.status === 'approved' );

        if ( ! canRetry ) {
            throw error;
        }

        return apiClient.put( endpoint, body, buildStatusChangeRequestConfig( latestReport ) );
    }
}

/**
 * Submit a report for review
 */
export function useSubmitReport() {
    const queryClient = useQueryClient();

    return useMutation( {
        mutationFn: ( report ) => performStatusChange( report, 'submitted' ),
        onSuccess: ( updatedReport, report ) => {
            const id = getReportId( report );
            syncReportStatusCaches( queryClient, report, updatedReport, 'submitted' );
            queryClient.invalidateQueries( { queryKey: queryKeys.reports.detail( id ) } );
            queryClient.invalidateQueries( { queryKey: queryKeys.reports.versions( id ) } );
            queryClient.invalidateQueries( { queryKey: queryKeys.reports.all } );
        },
    } );
}

/**
 * Approve a report
 */
export function useApproveReport() {
    const queryClient = useQueryClient();

    return useMutation( {
        mutationFn: ( report ) => performStatusChange( report, 'approved' ),
        onSuccess: ( updatedReport, report ) => {
            const id = getReportId( report );
            syncReportStatusCaches( queryClient, report, updatedReport, 'approved' );
            queryClient.invalidateQueries( { queryKey: queryKeys.reports.detail( id ) } );
            queryClient.invalidateQueries( { queryKey: queryKeys.reports.versions( id ) } );
            queryClient.invalidateQueries( { queryKey: queryKeys.reports.all } );
        },
    } );
}

/**
 * Revert a report to draft status (Unapprove)
 */
export function useRevertToDraft() {
    const queryClient = useQueryClient();

    return useMutation( {
        mutationFn: ( report ) => performStatusChange( report, 'draft' ),
        onSuccess: ( updatedReport, report ) => {
            const id = getReportId( report );
            syncReportStatusCaches( queryClient, report, updatedReport, 'draft' );
            queryClient.invalidateQueries( { queryKey: queryKeys.reports.detail( id ) } );
            queryClient.invalidateQueries( { queryKey: queryKeys.reports.versions( id ) } );
            queryClient.invalidateQueries( { queryKey: queryKeys.reports.all } );
        },
    } );
}

/**
 * Delete a report
 */
export function useDeleteReport() {
    const queryClient = useQueryClient();

    return useMutation( {
        mutationFn: ( id ) => apiClient.delete( `/reports/${ id }` ),
        onSuccess: ( _, id ) => {
            queryClient.removeQueries( { queryKey: queryKeys.reports.detail( id ) } );
            queryClient.invalidateQueries( { queryKey: queryKeys.reports.all } );
        },
    } );
}

/**
 * Upload photos to a report
 */
export function useUploadPhotos() {
    return useMutation( {
        mutationFn: ( { reportId, photos } ) => {
            const formData = new FormData();
            photos.forEach( ( photo ) => {
                formData.append( 'photos[]', photo.file );
            } );
            return apiClient.post( `/reports/${ reportId }/photos`, formData );
        },
    } );
}

/**
 * Generate AI summary for a report
 */
export function useGenerateAISummary() {
    return useMutation( {
        mutationFn: ( { reportId } ) => apiClient.post( `/reports/${ reportId }/generate-summary` ),
    } );
}

// ============ User Queries ============

/**
 * Fetch current user info
 */
export function useCurrentUser() {
    return useQuery( {
        queryKey: queryKeys.user.me,
        queryFn: () => apiClient.get( '/me' ),
        staleTime: 10 * 60 * 1000, // 10 minutes
    } );
}

/**
 * Fetch Dashboard Stats
 */
export function useStats() {
    return useQuery( {
        queryKey: [ 'stats' ],
        queryFn: () => apiClient.get( '/stats' ),
        staleTime: 1 * 60 * 1000, // 1 minute
        retry: 1,
    } );
}

/**
 * Fetch Settings
 */
export function useSettings() {
    return useQuery( {
        queryKey: [ 'settings' ],
        queryFn: () => apiClient.get( '/settings' ),
        staleTime: 5 * 60 * 1000,
    } );
}

/**
 * Update Settings
 */
export function useUpdateSettings() {
    const queryClient = useQueryClient();
    return useMutation( {
        mutationFn: ( data ) => apiClient.post( '/settings', data ),
        onSuccess: () => {
            queryClient.invalidateQueries( { queryKey: [ 'settings' ] } );
        },
    } );
}

/**
 * Test monday.com connection.
 */
export function useTestMondayConnection() {
    return useMutation( {
        mutationFn: () => apiClient.post( '/monday/test', {} ),
    } );
}

/**
 * Fetch monday.com workspaces.
 */
export function useMondayWorkspaces() {
    return useMutation( {
        mutationFn: () => apiClient.get( '/monday/workspaces' ),
    } );
}

/**
 * Fetch monday.com boards, optionally filtered by workspace.
 */
export function useMondayBoards() {
    return useMutation( {
        mutationFn: ( workspaceId ) =>
            apiClient.get( '/monday/boards', {
                params: workspaceId ? { workspace_id: workspaceId } : {},
            } ),
    } );
}

/**
 * Validate and create missing columns on a monday board.
 */
export function useSetupMondayBoard() {
    return useMutation( {
        mutationFn: ( { boardId, ...data } ) => apiClient.post( '/monday/boards/setup', { boardId, ...data } ),
    } );
}

/**
 * Sync a report's POI to monday.com.
 */
export function useSyncReportToMonday() {
    const queryClient = useQueryClient();

    return useMutation( {
        mutationFn: ( reportId ) => apiClient.post( `/reports/${ reportId }/sync-monday`, {} ),
        onSuccess: ( response, reportId ) => {
            queryClient.invalidateQueries( { queryKey: queryKeys.reports.detail( reportId ) } );
            queryClient.invalidateQueries( { queryKey: queryKeys.reports.all } );

            if ( response?.report?.school_id ) {
                queryClient.invalidateQueries( {
                    queryKey: queryKeys.schools.detail( response.report.school_id ),
                } );
            }
        },
    } );
}
