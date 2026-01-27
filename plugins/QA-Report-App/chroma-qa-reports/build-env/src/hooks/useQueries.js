/**
 * React Query hooks for data fetching
 */
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@api/client';

// Query key factories
export const queryKeys = {
    schools: {
        all: ['schools'],
        list: (filters) => ['schools', 'list', filters],
        detail: (id) => ['schools', 'detail', id],
    },
    reports: {
        all: ['reports'],
        list: (filters) => ['reports', 'list', filters],
        detail: (id) => ['reports', 'detail', id],
        checklist: (id) => ['reports', id, 'checklist'],
    },
    user: {
        me: ['user', 'me'],
    },
};

// ============ School Queries ============

/**
 * Fetch all schools
 */
export function useSchools(filters = {}) {
    return useQuery({
        queryKey: queryKeys.schools.list(filters),
        queryFn: () => apiClient.get('/schools', { params: filters }),
        staleTime: 5 * 60 * 1000, // 5 minutes
    });
}

/**
 * Fetch a single school by ID
 */
export function useSchool(id) {
    return useQuery({
        queryKey: queryKeys.schools.detail(id),
        queryFn: () => apiClient.get(`/schools/${id}`),
        enabled: !!id,
    });
}

/**
 * Create a new school
 */
export function useCreateSchool() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data) => apiClient.post('/schools', data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: queryKeys.schools.all });
        },
    });
}

/**
 * Update a school
 */
export function useUpdateSchool() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({ id, ...data }) => apiClient.put(`/schools/${id}`, data),
        onSuccess: (_, variables) => {
            queryClient.invalidateQueries({ queryKey: queryKeys.schools.detail(variables.id) });
            queryClient.invalidateQueries({ queryKey: queryKeys.schools.all });
        },
    });
}

// ============ Report Queries ============

/**
 * Fetch all reports
 */
export function useReports(filters = {}) {
    return useQuery({
        queryKey: queryKeys.reports.list(filters),
        queryFn: () => apiClient.get('/reports', { params: filters }),
        staleTime: 2 * 60 * 1000, // 2 minutes
    });
}

/**
 * Fetch a single report by ID
 */
export function useReport(id) {
    return useQuery({
        queryKey: queryKeys.reports.detail(id),
        queryFn: () => apiClient.get(`/reports/${id}`),
        enabled: !!id,
    });
}

/**
 * Fetch report checklist template
 */
export function useReportChecklist(reportType) {
    return useQuery({
        queryKey: ['checklist', reportType],
        queryFn: () => apiClient.get(`/checklists/${reportType}`),
        enabled: !!reportType,
        staleTime: 30 * 60 * 1000, // 30 minutes - checklists don't change often
    });
}

/**
 * Create a new report
 */
export function useCreateReport() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data) => apiClient.post('/reports', data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: queryKeys.reports.all });
        },
    });
}

/**
 * Update a report
 */
export function useUpdateReport() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({ id, version_id, ...data }) =>
            apiClient.put(`/reports/${id}`, data, {
                headers: version_id ? { 'X-CQA-Version': version_id } : {},
            }),
        onSuccess: (_, variables) => {
            queryClient.invalidateQueries({ queryKey: queryKeys.reports.detail(variables.id) });
            queryClient.invalidateQueries({ queryKey: queryKeys.reports.all });
        },
    });
}

/**
 * Submit a report for review
 */
export function useSubmitReport() {
    const queryClient = useQueryClient();

    return useMutation({
        // Use PUT with status='submitted' instead of non-existent RPC endpoint
        mutationFn: (id) => apiClient.put(`/reports/${id}`, { status: 'submitted' }),
        onSuccess: (_, id) => {
            queryClient.invalidateQueries({ queryKey: queryKeys.reports.detail(id) });
            queryClient.invalidateQueries({ queryKey: queryKeys.reports.all });
        },
    });
}

/**
 * Upload photos to a report
 */
export function useUploadPhotos() {
    return useMutation({
        mutationFn: ({ reportId, photos }) => {
            const formData = new FormData();
            photos.forEach((photo, index) => {
                formData.append(`photos[${index}]`, photo.file);
                if (photo.caption) formData.append(`captions[${index}]`, photo.caption);
                if (photo.category) formData.append(`categories[${index}]`, photo.category);
            });
            return apiClient.post(`/reports/${reportId}/photos`, formData);
        },
    });
}

/**
 * Generate AI summary for a report
 */
export function useGenerateAISummary() {
    return useMutation({
        mutationFn: ({ reportId, checklistData }) =>
            apiClient.post(`/reports/${reportId}/generate-summary`, { checklist: checklistData }),
    });
}

// ============ User Queries ============

/**
 * Fetch current user info
 */
export function useCurrentUser() {
    return useQuery({
        queryKey: queryKeys.user.me,
        queryFn: () => apiClient.get('/me'),
        staleTime: 10 * 60 * 1000, // 10 minutes
    });
}

/**
 * Fetch Dashboard Stats
 */
export function useStats() {
    return useQuery({
        queryKey: ['stats'],
        queryFn: () => apiClient.get('/stats'),
        staleTime: 1 * 60 * 1000, // 1 minute
        retry: 1
    });
}

/**
 * Fetch Settings
 */
export function useSettings() {
    return useQuery({
        queryKey: ['settings'],
        queryFn: () => apiClient.get('/settings'),
        staleTime: 5 * 60 * 1000,
    });
}

/**
 * Update Settings
 */
export function useUpdateSettings() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (data) => apiClient.post('/settings', data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['settings'] });
        },
    });
}
