import React, { Suspense, lazy, useEffect } from 'react';
import { HashRouter, Routes, Route, Navigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import useAuthStore from '@stores/useAuthStore';
import useUIStore from '@stores/useUIStore';

// Layout
import Shell from './components/layout/Shell';
import ErrorBoundary from './components/common/ErrorBoundary';

const ReportWizard = lazy(() => import(/* webpackChunkName: "cqa-report-wizard" */ './components/wizard/ReportWizard'));
const Dashboard = lazy(() => import(/* webpackChunkName: "cqa-dashboard-page" */ './pages/Dashboard'));
const SchoolsPage = lazy(() => import(/* webpackChunkName: "cqa-schools-page" */ './pages/Schools'));
const ReportsPage = lazy(() => import(/* webpackChunkName: "cqa-reports-page" */ './pages/Reports'));
const PrintReport = lazy(() => import(/* webpackChunkName: "cqa-print-page" */ './pages/PrintReport'));
const Settings = lazy(() => import(/* webpackChunkName: "cqa-settings-page" */ './pages/Settings'));

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: 1,
            refetchOnWindowFocus: false,
        },
    },
});

const RouteLoader = () => (
    <div className="flex items-center justify-center min-h-[45vh] text-brand-ink/70">
        Loading...
    </div>
);

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
        <ErrorBoundary>
            <QueryClientProvider client={queryClient}>
                <HashRouter>
                    <Suspense fallback={<RouteLoader />}>
                        <Routes>
                            <Route path="print/:id" element={<PrintReport />} />
                            <Route path="/" element={<Shell />}>
                                <Route index element={<Dashboard />} />
                                <Route path="schools" element={<SchoolsPage />} />
                                <Route path="reports" element={<ReportsPage />} />
                                <Route path="reports/:id" element={<ReportWizard />} />
                                <Route path="edit/:id" element={<ReportWizard />} />
                                <Route path="create" element={<ReportWizard />} />
                                <Route path="settings" element={<Settings />} />
                                <Route path="*" element={<Navigate to="/" replace />} />
                            </Route>
                        </Routes>
                    </Suspense>
                </HashRouter>
            </QueryClientProvider>
        </ErrorBoundary>
    );
};

export default App;
