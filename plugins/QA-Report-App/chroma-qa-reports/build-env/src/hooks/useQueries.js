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
            apiClient.post( `/reports/${ id }/restore/${ version }`, {}, {
                headers: currentVersion ? { 'X-CQA-Version': currentVersion } : {},
                ifUnmodifiedSince: updatedAt || undefined,
            } ),
        onSuccess: ( _, variables ) => {
            queryClient.invalidateQueries( { queryKey: queryKeys.reports.detail( variables.id ) } );
            queryClient.invalidateQueries( { queryKey: queryKeys.reports.versions( variables.id ) } );
            queryClient.invalidateQueries( { queryKey: queryKeys.reports.all } );
        },
    } );
}

/**
 * Submit a report for review
 */
export function useSubmitReport() {
    const queryClient = useQueryClient();

    return useMutation( {
        // Use PUT with status='submitted' instead of non-existent RPC endpoint
        mutationFn: ( report ) =>
            apiClient.put(
                `/reports/${ typeof report === 'object' ? report.id : report }`,
                { status: 'submitted', save_mode: 'status_change' },
                {
                    headers:
                        typeof report === 'object' && report.version_id
                            ? { 'X-CQA-Version': report.version_id }
                            : {},
                    ifUnmodifiedSince:
                        typeof report === 'object' ? report.updated_at || undefined : undefined,
                }
            ),
        onSuccess: ( _, report ) => {
            const id = typeof report === 'object' ? report.id : report;
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
        mutationFn: ( report ) =>
            apiClient.put(
                `/reports/${ typeof report === 'object' ? report.id : report }`,
                { status: 'approved', save_mode: 'status_change' },
                {
                    headers:
                        typeof report === 'object' && report.version_id
                            ? { 'X-CQA-Version': report.version_id }
                            : {},
                    ifUnmodifiedSince:
                        typeof report === 'object' ? report.updated_at || undefined : undefined,
                }
            ),
        onSuccess: ( _, report ) => {
            const id = typeof report === 'object' ? report.id : report;
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
        mutationFn: ( report ) =>
            apiClient.put(
                `/reports/${ typeof report === 'object' ? report.id : report }`,
                { status: 'draft', save_mode: 'status_change' },
                {
                    headers:
                        typeof report === 'object' && report.version_id
                            ? { 'X-CQA-Version': report.version_id }
                            : {},
                    ifUnmodifiedSince:
                        typeof report === 'object' ? report.updated_at || undefined : undefined,
                }
            ),
        onSuccess: ( _, report ) => {
            const id = typeof report === 'object' ? report.id : report;
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
            photos.forEach( ( photo, index ) => {
                formData.append( `photos[${ index }]`, photo.file );
                if ( photo.caption ) {
                    formData.append( `captions[${ index }]`, photo.caption );
                }
                if ( photo.category ) {
                    formData.append( `categories[${ index }]`, photo.category );
                }
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
        mutationFn: ( { boardId, ...data } ) => apiClient.post( `/monday/boards/${ boardId }/setup`, data ),
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
