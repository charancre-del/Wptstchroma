import { create } from 'zustand';
import apiFetch from '@api/client';

const useAuthStore = create((set, get) => ({
    user: null,
    isAuthenticated: false,
    isLoading: true,
    error: null,
    capabilities: {},
    flags: {},

    // Fetch user info from /me
    fetchUser: async () => {
        set({ isLoading: true });
        try {
            const response = await apiFetch('me');
            if (response.success) {
                set({
                    user: response.data,
                    isAuthenticated: true,
                    capabilities: response.data.capabilities || {},
                    flags: response.data.flags || {},
                    isLoading: false,
                    error: null,
                });
            } else {
                set({ error: 'Failed to load user', isAuthenticated: false, isLoading: false });
            }
        } catch (error) {
            set({ error: error.message, isAuthenticated: false, isLoading: false });
        }
    },

    // Check specific capability
    can: (capability) => {
        const { capabilities } = get();
        return capabilities[capability] === true;
    },

    // Check specific feature flag
    isFeatureEnabled: (feature) => {
        const { flags } = get();
        return flags[`cqa_flag_${feature}`] === true;
    }
}));

export default useAuthStore;
