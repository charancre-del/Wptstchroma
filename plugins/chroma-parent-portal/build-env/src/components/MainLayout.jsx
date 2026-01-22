import React, { useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import LoginScreen from './auth/LoginScreen';
import Dashboard from './dashboard/Dashboard';

const MainLayout = () => {
    const { user, loading, setAdmin } = useAuth();

    useEffect(() => {
        // Initial Fetch to check if we are secretly an Admin (Standard WP Login)
        // AND to wake up the dashboard
        const checkAdmin = async () => {
            const settings = window.chromaPortalSettings;
            // Only check if we have a token OR if we suspect WP Admin session
            // For now, suppress the noise to avoid confusing the user with 403s
            if (!settings || !localStorage.getItem('chroma_portal_token')) return;

            try {
                const res = await fetch(`${settings.root}chroma-portal/v1/content/dashboard?year=${new Date().getFullYear()}`, {
                    headers: { 'X-WP-Nonce': settings.nonce }
                });
                const data = await res.json();

                if (data.is_admin) {
                    setAdmin();
                }
            } catch (e) { }
        };
        checkAdmin();
    }, []);

    if (loading) return <div>Loading...</div>;

    if (!user) {
        return <LoginScreen />;
    }

    return (
        <div className="portal-app-wrapper">
            <Dashboard />
        </div>
    );
};

export default MainLayout;
