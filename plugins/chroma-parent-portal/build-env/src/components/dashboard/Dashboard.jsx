import React, { useState, useEffect, Suspense, lazy } from 'react';
import { useAuth } from '../../context/AuthContext';
import Header from './Header';
import { AnimatePresence, motion } from 'framer-motion';

const debugLog = (...args) => {
    if (window.chromaPortalSettings?.debug && window.console && typeof window.console.debug === 'function') {
        window.console.debug(...args);
    }
};

// Lazy Load Heavy Components
const Sidebar = lazy(() => import('./Sidebar'));
const DashboardGrid = lazy(() => import('./DashboardGrid'));
const LessonPlanSection = lazy(() => import('./LessonPlanSection'));
const MealPlansSection = lazy(() => import('./MealPlansSection'));
const OrganizationGroup = lazy(() => import('../common/OrganizationGroup'));
const PDFViewerModal = lazy(() => import('../common/PDFViewerModal'));
const FeedbackSection = lazy(() => import('./FeedbackSection'));

const Dashboard = () => {
    const { user } = useAuth();
    const [year, setYear] = useState(new Date().getFullYear().toString());
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [activeTab, setActiveTab] = useState('overview');
    const [availableYears, setAvailableYears] = useState([]);
    const [selectedFile, setSelectedFile] = useState(null);
    const [isSidebarCollapsed, setIsSidebarCollapsed] = useState(() => {
        // Initialize from localStorage for persistence
        if (typeof window !== 'undefined') {
            const stored = localStorage.getItem('chroma_portal_sidebar_collapsed');
            return stored === 'true';
        }
        return false;
    });

    // Persist sidebar state to localStorage
    useEffect(() => {
        localStorage.setItem('chroma_portal_sidebar_collapsed', String(isSidebarCollapsed));
    }, [isSidebarCollapsed]);

    const fetchData = async () => {
        setLoading(true);
        const settings = window.chromaPortalSettings;
        try {
            debugLog('[Dashboard] Fetching portal dashboard data.');
            const res = await fetch(`${settings.root}chroma-portal/v1/content/dashboard?year=${year}`, {
                headers: {
                    'X-Portal-Token': user.token,
                    'X-WP-Nonce': settings.nonce
                }
            });

            debugLog('[Dashboard] Response status:', res.status);

            // If 403, token is invalid - force re-login
            if (res.status === 403) {
                console.error('[Dashboard] 403 Forbidden - Token invalid or expired');
                localStorage.removeItem('chroma_portal_token');
                localStorage.removeItem('chroma_portal_family');
                window.location.reload();
                return;
            }

            if (!res.ok) {
                throw new Error(`API returned ${res.status}`);
            }

            const json = await res.json();

            // Ensure all arrays exist to prevent .map() errors
            const safeData = {
                is_admin: json.is_admin || false,
                announcements: json.announcements || [],
                lesson_plans: json.lesson_plans || [],
                home_activities: json.home_activities || [],
                meal_plans: json.meal_plans || [],
                resources: json.resources || [],
                forms: json.forms || [],
                events: json.events || []
            };

            debugLog('[Dashboard] Data loaded successfully:', Object.keys(safeData).map(k => `${k}:${safeData[k]?.length || safeData[k]}`).join(', '));
            setData(safeData);
        } catch (e) {
            console.error("[Dashboard] Failed to fetch dashboard:", e);
            // Set empty data structure to prevent .map() errors
            setData({
                is_admin: false,
                announcements: [],
                lesson_plans: [],
                home_activities: [],
                meal_plans: [],
                resources: [],
                forms: [],
                events: []
            });
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, [year, user.token]);

    useEffect(() => {
        // Fetch available years from WordPress taxonomy
        const fetchYears = async () => {
            const settings = window.chromaPortalSettings;
            try {
                const res = await fetch(`${settings.root}chroma-portal/v1/years`, {
                    headers: {
                        'X-Portal-Token': user.token,
                        'X-WP-Nonce': settings.nonce
                    }
                });

                if (res.ok) {
                    const years = await res.json();
                    debugLog('[Dashboard] Available years from WP:', years);
                    setAvailableYears(years);

                    // Set default year to the first available year if current year yields nothing
                    if (years.length > 0) {
                        const currentCalYear = new Date().getFullYear().toString();
                        // If current year is NOT in the list, or we haven't set a year yet, use the first one
                        if (!years.find(y => y.value === year) || year === currentCalYear) {
                            setYear(years[0].value);
                        }
                    }
                }
            } catch (e) {
                console.error('[Dashboard] Failed to fetch years:', e);
            }
        };

        fetchYears();
    }, [user.token]);

    if (!data && !loading) return (
        <div style={{ padding: 50, textAlign: 'center' }}>
            <h1>Dashboard Error</h1>
            <p>Could not load portal data. Please try logging in again.</p>
            <button
                onClick={() => {
                    localStorage.removeItem('chroma_portal_token');
                    localStorage.removeItem('chroma_portal_family');
                    window.location.reload();
                }}
                style={{
                    marginTop: 20,
                    padding: '10px 20px',
                    background: '#263238',
                    color: 'white',
                    border: 'none',
                    borderRadius: 8,
                    cursor: 'pointer'
                }}
            >
                Return to Login
            </button>
        </div>
    );

    const handleView = (file) => {
        debugLog('[Dashboard] Opening PDF:', file.title);
        setSelectedFile(file);
    };

    const renderView = () => {
        if (loading) return <div style={{ textAlign: 'center', padding: '100px' }}>Loading Portal Data...</div>;

        return (
            <Suspense fallback={
                <div className="flex h-64 w-full items-center justify-center text-chroma-blue">
                    <i className="fa-solid fa-circle-notch fa-spin text-3xl"></i>
                </div>
            }>
                {renderContent()}
            </Suspense>
        );
    };

    const renderContent = () => {
        switch (activeTab) {
            case 'lessons':
                return (
                    <div className="view-container">
                        <div className="section-header" style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '20px' }}>
                            <h2>Lesson Plans Library</h2>
                        </div>
                        <LessonPlanSection
                            items={data.lesson_plans}
                            type="lesson"
                            onView={handleView}
                            onDelete={fetchData}
                            showClassroomFilter={true}
                        />
                    </div>
                );
            case 'home_activities':
                return (
                    <div className="view-container">
                        <div className="section-header" style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '20px' }}>
                            <h2>Home Activities</h2>
                        </div>
                        <LessonPlanSection
                            items={data.home_activities}
                            type="activity"
                            onView={handleView}
                            onDelete={fetchData}
                            showClassroomFilter={true}
                        />
                    </div>
                );
            case 'meals':
                return (
                    <div className="view-container">
                        <div className="section-header" style={{ marginBottom: '20px' }}>
                            <h2>Nutritional Meal Plans</h2>
                        </div>
                        <MealPlansSection items={data.meal_plans} onView={handleView} onDelete={fetchData} />
                    </div>
                );
            case 'events':
                return (
                    <div className="view-container">
                        <div className="section-header" style={{ marginBottom: '20px' }}>
                            <h2>Upcoming School Events</h2>
                        </div>
                        <OrganizationGroup
                            items={data.events}
                            onView={handleView}
                            onDelete={fetchData}
                            emptyMessage="No upcoming events scheduled."
                        />
                    </div>
                );
            case 'news':
                return (
                    <div className="view-container">
                        <div className="section-header" style={{ marginBottom: '20px' }}>
                            <h2>School News & Announcements</h2>
                        </div>
                        <OrganizationGroup
                            items={data.announcements}
                            onView={handleView}
                            onDelete={fetchData}
                            emptyMessage="No recent news available."
                        />
                    </div>
                );
            case 'resources':
                return (
                    <div className="view-container">
                        <div className="section-header" style={{ marginBottom: '20px' }}>
                            <h2>Resources</h2>
                        </div>
                        <LessonPlanSection
                            items={data.resources}
                            type="resource"
                            onView={handleView}
                            onDelete={fetchData}
                        />
                    </div>
                );
            case 'policies':
                return (
                    <div className="view-container">
                        <div className="section-header" style={{ marginBottom: '20px' }}>
                            <h2>Policies & Procedures</h2>
                        </div>
                        <LessonPlanSection
                            items={data.forms}
                            type="policy"
                            onView={handleView}
                            onDelete={fetchData}
                        />
                    </div>
                );


            case 'feedback':
                return <FeedbackSection />;

            default:
                return <DashboardGrid data={data} refreshData={fetchData} onDocumentClick={handleView} onTabChange={setActiveTab} />;
        }
    };

    return (
        <div className={`glass-app-shell ${isSidebarCollapsed ? 'sidebar-collapsed' : ''}`}>
            <Suspense fallback={null}>
                <Sidebar
                    activeTab={activeTab}
                    setActiveTab={setActiveTab}
                    isAdmin={data?.is_admin}
                    isCollapsed={isSidebarCollapsed}
                    onToggle={() => setIsSidebarCollapsed(!isSidebarCollapsed)}
                />
            </Suspense>

            <main className="portal-main">
                <div className="main-viewport">
                    <Header user={user} year={year} setYear={setYear} availableYears={availableYears} />

                    <AnimatePresence mode="wait">
                        <motion.div
                            key={activeTab}
                            initial={{ opacity: 0, x: 20 }}
                            animate={{ opacity: 1, x: 0 }}
                            exit={{ opacity: 0, x: -20 }}
                            transition={{ duration: 0.3 }}
                        >
                            {renderView()}
                        </motion.div>
                    </AnimatePresence>
                </div>
            </main>

            <AnimatePresence>
                {selectedFile && (
                    <Suspense fallback={null}>
                        <PDFViewerModal
                            file={selectedFile}
                            onClose={() => setSelectedFile(null)}
                        />
                    </Suspense>
                )}
            </AnimatePresence>
        </div>
    );
};

export default Dashboard;
