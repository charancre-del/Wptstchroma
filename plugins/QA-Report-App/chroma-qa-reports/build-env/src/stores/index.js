import { create } from 'zustand';
import { persist } from 'zustand/middleware';

/**
 * Auth Store - manages authentication state
 */
export const useAuthStore = create(
    persist(
        (set, get) => ({
            // State
            user: null,
            isAuthenticated: false,
            isLoading: true,

            // Actions
            setUser: (user) => set({
                user,
                isAuthenticated: !!user,
                isLoading: false,
            }),

            logout: () => set({
                user: null,
                isAuthenticated: false,
                isLoading: false,
            }),

            setLoading: (isLoading) => set({ isLoading }),

            // Selectors
            hasCapability: (capability) => {
                const { user } = get();
                return user?.capabilities?.includes(capability) || false;
            },

            canCreateReports: () => get().hasCapability('cqa_create_reports'),
            canEditAllReports: () => get().hasCapability('cqa_edit_all_reports'),
            canManageSchools: () => get().hasCapability('cqa_manage_schools'),
            canUseAI: () => get().hasCapability('cqa_use_ai_features'),
            isAdmin: () => get().hasCapability('cqa_manage_settings'),
        }),
        {
            name: 'cqa-auth-storage',
            partialize: (state) => ({ user: state.user }),
        }
    )
);

/**
 * UI Store - manages global UI state
 */
export const useUIStore = create((set) => ({
    // Sidebar
    isSidebarCollapsed: false,
    toggleSidebar: () => set((state) => ({ isSidebarCollapsed: !state.isSidebarCollapsed })),

    // Notifications
    notifications: [],
    addNotification: (notification) => set((state) => ({
        notifications: [
            ...state.notifications,
            { id: Date.now(), ...notification },
        ],
    })),
    removeNotification: (id) => set((state) => ({
        notifications: state.notifications.filter((n) => n.id !== id),
    })),

    // Loading overlay
    isGlobalLoading: false,
    setGlobalLoading: (isGlobalLoading) => set({ isGlobalLoading }),

    // Modal
    activeModal: null,
    modalData: null,
    openModal: (modal, data = null) => set({ activeModal: modal, modalData: data }),
    closeModal: () => set({ activeModal: null, modalData: null }),
}));

/**
 * Report Wizard Store - manages report creation state
 */
export const useReportWizardStore = create(
    persist(
        (set, get) => ({
            // Wizard state
            currentStep: 1,
            isSubmitting: false,
            hasHydrated: false, // QAR-080: Hydration Sync

            // Report data
            report: {
                school_id: null,
                school_name: '',
                report_type: 'tier1',
                visit_date: new Date().toISOString().split('T')[0],
                inspection_date: new Date().toISOString().split('T')[0],
                previous_report_id: null,
                closing_notes: '',
                overall_rating: 'pending',
                status: 'draft',
            },
            responses: {},
            photos: [],

            // Actions
            setHasHydrated: (hasHydrated) => set({ hasHydrated }),
            setStep: (step) => set({ currentStep: step }),
            nextStep: () => set((state) => ({ currentStep: state.currentStep + 1 })),
            prevStep: () => set((state) => ({ currentStep: Math.max(1, state.currentStep - 1) })),

            setReport: (data) => set((state) => ({
                report: { ...state.report, ...data },
            })),

            // Legacy alias for setReport
            updateReportData: (data) => set((state) => ({
                report: { ...state.report, ...data },
            })),

            setResponses: (responses) => set({ responses }),

            setResponse: (sectionKey, itemKey, response) => set((state) => ({
                responses: {
                    ...state.responses,
                    [sectionKey]: {
                        ...state.responses[sectionKey],
                        [itemKey]: response,
                    },
                },
            })),

            setPhotos: (photos) => set({ photos }),

            addPhoto: (photo) => set((state) => ({
                photos: [...state.photos, photo],
            })),

            removePhoto: (photoId) => set((state) => ({
                photos: state.photos.filter((p) => p.id !== photoId),
            })),

            setSubmitting: (isSubmitting) => set({ isSubmitting }),

            reset: () => set({
                currentStep: 1,
                isSubmitting: false,
                report: {
                    school_id: null,
                    school_name: '',
                    report_type: 'tier1',
                    visit_date: new Date().toISOString().split('T')[0],
                    inspection_date: new Date().toISOString().split('T')[0],
                    previous_report_id: null,
                    closing_notes: '',
                    overall_rating: 'pending',
                    status: 'draft',
                },
                responses: {},
                photos: [],
            }),

            // Computed
            getTotalResponses: () => {
                const { responses } = get();
                return Object.values(responses).reduce(
                    (total, section) => {
                        if (typeof section === 'object' && section !== null) {
                            return total + Object.keys(section).length;
                        }
                        return total + 1; // Flat response
                    },
                    0
                );
            },
        }),
        {
            name: 'cqa-wizard-draft',
            partialize: (state) => ({
                currentStep: state.currentStep,
                report: state.report,
                responses: state.responses,
                photos: state.photos,
            }),
            onRehydrateStorage: () => (state) => {
                state.setHasHydrated(true);
            },
        }
    )
);
