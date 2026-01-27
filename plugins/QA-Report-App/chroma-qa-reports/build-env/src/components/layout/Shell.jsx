import React, { useEffect } from 'react';
import { Outlet, useNavigate, useLocation } from 'react-router-dom';
import Sidebar from './Sidebar';
import useUIStore from '@stores/useUIStore';
import useAuthStore from '@stores/useAuthStore';
import ConflictModal from '../common/ConflictModal';
import SessionExpiredModal from '../common/SessionExpiredModal';
import OfflineBanner from '../common/OfflineBanner';

const Shell = () => {
    const { isSidebarOpen } = useUIStore();
    const { isAuthenticated, isLoading } = useAuthStore();
    const navigate = useNavigate();
    const location = useLocation();

    // AUDIT FIX: Security Guard
    useEffect(() => {
        if (!isLoading && !isAuthenticated) {
            // Redirect to WP login or show message? 
            // For an embedded WP app, usually we just show a "Please Log In" state or rely on WP auth cookies.
            // If the user isn't authenticated in the store, it means the API /me call failed or hasn't run.
            // But App.jsx handles the initial fetch.
            // here we just ensure we don't render content.
        }
    }, [isLoading, isAuthenticated]);

    if (!isAuthenticated && !isLoading) {
        return (
            <div className="flex h-screen items-center justify-center bg-brand-cream">
                <div className="text-center p-8 bg-white shadow-xl rounded-2xl border border-brand-ink/5">
                    <h2 className="text-xl font-serif font-bold text-chroma-red mb-2">Access Denied</h2>
                    <p className="text-brand-ink/60 mb-4">You must be logged in to access this application.</p>
                    <a href="/wp-login.php" className="px-6 py-2 bg-brand-ink text-white rounded-full hover:bg-brand-ink/80 transition-all font-bold text-sm">Go to Login</a>
                </div>
            </div>
        );
    }

    return (
        <div className="flex h-screen bg-brand-cream overflow-hidden font-sans text-brand-ink">
            <ConflictModal />
            <SessionExpiredModal />

            {/* Sidebar Container */}
            <div className="flex-shrink-0 z-20 h-full">
                <Sidebar isOpen={isSidebarOpen} />
            </div>

            {/* Main Content */}
            <main className="flex-1 overflow-auto relative flex flex-col scroll-smooth">
                <OfflineBanner />
                <div className="w-full max-w-7xl mx-auto p-4 md:p-8">
                    <Outlet />
                </div>
            </main>
        </div>
    );
};

export default Shell;
