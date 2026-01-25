import React, { useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import LoginScreen from './auth/LoginScreen';
import Dashboard from './dashboard/Dashboard';

const MainLayout = () => {
    const { user, loading, setAdmin } = useAuth();

    useEffect(() => {
        // Initial Fetch to check if we are secretly an Admin (Standard WP Login)
        const checkAdmin = async () => {
            const settings = window.chromaPortalSettings;
            const token = localStorage.getItem('chroma_portal_token');
            if (!settings || !token || user?.isAdmin) return;

            try {
                const res = await fetch(`${settings.root}chroma-portal/v1/content/dashboard?year=${new Date().getFullYear()}`, {
                    headers: {
                        'X-Portal-Token': token,
                        'X-WP-Nonce': settings.nonce
                    }
                });

                if (res.ok) {
                    const data = await res.json();
                    if (data.is_admin) {
                        setAdmin();
                    }
                }
            } catch (e) { }
        };
        checkAdmin();
    }, [user]);

    if (loading) return (
        <div style={{
            height: '100vh',
            width: '100vw',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            background: '#ffffff',
            color: '#263238',
            fontSize: '1.2rem',
            zIndex: 999999,
            position: 'fixed',
            flexDirection: 'column',
            gap: '20px'
        }}>
            <div className="spinner" style={{
                width: '40px',
                height: '40px',
                border: '4px solid #f3f3f3',
                borderTop: '4px solid #263238',
                borderRadius: '50%',
                animation: 'spin 1s linear infinite'
            }}></div>
            <style>{`
                @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            `}</style>
            <span>Initializing Portal...</span>
        </div>
    );

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
