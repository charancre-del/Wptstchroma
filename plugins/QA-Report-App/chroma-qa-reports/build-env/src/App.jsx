import React, { useEffect } from 'react';
import { HashRouter, Routes, Route, Navigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import useAuthStore from './stores/useAuthStore';
import useUIStore from './stores/useUIStore';

// Layout
import Shell from './components/layout/Shell';

// Wizard
import ReportWizard from './components/wizard/ReportWizard';

// List Views
import SchoolsList from './components/schools/SchoolsList';
import ReportsList from './components/reports/ReportsList';

// Pages (Placeholders)
const Dashboard = () => <div className="p-6"><h1>Dashboard</h1></div>;
// SchoolsList and ReportsList are now real components
// const CreateReport = () => <div className="p-6"><h1>Create Report</h1></div>;

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: 1, // Minimize retries for quicker failure response
            refetchOnWindowFocus: false, // Prevent overwhelming server
        },
    },
});

const App = () => {
    const { fetchUser, isLoading: isAuthLoading } = useAuthStore();
    const { setSessionExpired } = useUIStore();

    useEffect(() => {
        // Initial user fetch
        fetchUser();

        // Listen for 401 events from api/client.js
        const handleSessionExpiry = () => {
            setSessionExpired(true);
        };

        window.addEventListener('cqa:session-expired', handleSessionExpiry);
        return () => window.removeEventListener('cqa:session-expired', handleSessionExpiry);
    }, [fetchUser, setSessionExpired]);

    if (isAuthLoading) {
        return <div className="flex items-center justify-center h-screen">Loading QA Reports...</div>;
    }

    return (
        <QueryClientProvider client={queryClient}>
            <HashRouter>
                <Routes>
                    <Route path="/" element={<Shell />}>
                        <Route index element={<Dashboard />} />
                        <Route path="schools" element={<SchoolsList />} />
                        <Route path="reports" element={<ReportsList />} />
                        <Route path="create" element={<ReportWizard />} />
                        <Route path="*" element={<Navigate to="/" replace />} />
                    </Route>
                </Routes>
            </HashRouter>
        </QueryClientProvider>
    );
};

export default App;
