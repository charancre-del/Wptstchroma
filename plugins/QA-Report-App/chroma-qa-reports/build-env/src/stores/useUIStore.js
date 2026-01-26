import { create } from 'zustand';

const useUIStore = create((set) => ({
    isSidebarOpen: true,
    toggleSidebar: () => set((state) => ({ isSidebarOpen: !state.isSidebarOpen })),

    // Session Expiry Modal
    isSessionExpired: false,
    setSessionExpired: (value) => set({ isSessionExpired: value }),

    // Conflict Modal (409)
    conflict: null, // { updatedBy, updatedAt, onOverwrite, onReload }
    showConflictModal: (conflictData) => set({ conflict: conflictData }),
    clearConflictModal: () => set({ conflict: null }),

    // Toast Notifications
    toasts: [],
    addToast: (toast) => set((state) => ({
        toasts: [...state.toasts, { id: Date.now(), ...toast }]
    })),
    removeToast: (id) => set((state) => ({
        toasts: state.toasts.filter((t) => t.id !== id)
    })),
}));

export default useUIStore;
