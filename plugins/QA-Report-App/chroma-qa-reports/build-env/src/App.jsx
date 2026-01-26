import React, { useEffect } from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import useAuthStore from './stores/useAuthStore';
import useUIStore from './stores/useUIStore';

// Layout
import Shell from './components/layout/Shell';

// Wizard
import ReportWizard from './components/wizard/ReportWizard';

// Pages
import Dashboard from './pages/Dashboard';
import SchoolsPage from './pages/Schools';
import ReportsPage from './pages/Reports';
import Settings from './pages/Settings';

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: 1,
            refetchOnWindowFocus: false,
        },
    },
});

const App = () => {
    const { fetchUser, isLoading: isAuthLoading } = useAuthStore();
    const { setSessionExpired } = useUIStore();

    useEffect(() => {
        fetchUser();
        const handleSessionExpiry = () => setSessionExpired(true);
        window.addEventListener('cqa:session-expired', handleSessionExpiry);
        return () => window.removeEventListener('cqa:session-expired', handleSessionExpiry);
    }, [fetchUser, setSessionExpired]);

    if (isAuthLoading) {
        return <div className="flex items-center justify-center h-screen bg-[#fdfbf7]">Loading QA Reports...</div>;
    }

    return (
        <QueryClientProvider client={queryClient}>
            <BrowserRouter basename="/qa-reports">
                <Routes>
                    <Route path="/" element={<Shell />}>
                        <Route index element={<Dashboard />} />
                        <Route path="schools" element={<SchoolsPage />} />
                        <Route path="reports" element={<ReportsPage />} />
                        <Route path="reports/:id" element={<ReportWizard />} /> {/* View/Edit logic inside ReportWizard or separate? */}
                        <Route path="create" element={<ReportWizard />} />
                        <Route path="settings" element={<Settings />} />
                        <Route path="*" element={<Navigate to="/" replace />} />
                    </Route>
                </Routes>
            </BrowserRouter>
        </QueryClientProvider>
    );
};

export default App;
