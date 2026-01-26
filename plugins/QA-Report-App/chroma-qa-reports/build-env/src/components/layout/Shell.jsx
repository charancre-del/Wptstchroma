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
            <div className="flex h-screen items-center justify-center bg-gray-50">
                <div className="text-center p-8 bg-white shadow-lg rounded-lg">
                    <h2 className="text-xl font-bold text-red-600 mb-2">Access Denied</h2>
                    <p className="text-gray-600 mb-4">You must be logged in to access this application.</p>
                    <a href="/wp-login.php" className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">Go to Login</a>
                </div>
            </div>
        );
    }

    return (
        <div className="flex h-screen bg-[#fdfbf7] overflow-hidden font-sans text-gray-900">
            <ConflictModal />
            <SessionExpiredModal />

            {/* Sidebar */}
            <div className={`transition-all duration-300 ${isSidebarOpen ? 'w-64' : 'w-16'} bg-white border-r border-gray-200 shadow-sm flex flex-col z-20`}>
                <Sidebar isOpen={isSidebarOpen} />
            </div>

            {/* Main Content */}
            <main className="flex-1 overflow-auto relative flex flex-col">
                <OfflineBanner />
                <div className="max-w-7xl mx-auto p-6 w-full">
                    <Outlet />
                </div>
            </main>
        </div>
    );
};

export default Shell;
