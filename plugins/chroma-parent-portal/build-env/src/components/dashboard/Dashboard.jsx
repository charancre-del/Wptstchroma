import React, { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import Sidebar from './Sidebar';
import Header from './Header';
import DashboardGrid from './DashboardGrid';
import LessonPlanSection from './LessonPlanSection';
import MealPlansSection from './MealPlansSection';
import PDFCard from '../common/PDFCard';
import OrganizationGroup from '../common/OrganizationGroup';
import PDFViewerModal from '../common/PDFViewerModal';
import { AnimatePresence, motion } from 'framer-motion';

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
            console.log('[Dashboard] Fetching with token:', user.token?.substring(0, 16) + '...');
            const res = await fetch(`${settings.root}chroma-portal/v1/content/dashboard?year=${year}`, {
                headers: {
                    'X-Portal-Token': user.token,
                    'X-WP-Nonce': settings.nonce
                }
            });

            console.log('[Dashboard] Response status:', res.status);

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
                meal_plans: json.meal_plans || [],
                resources: json.resources || [],
                forms: json.forms || [],
                events: json.events || []
            };

            console.log('[Dashboard] Data loaded successfully:', Object.keys(safeData).map(k => `${k}:${safeData[k]?.length || safeData[k]}`).join(', '));
            setData(safeData);
        } catch (e) {
            console.error("[Dashboard] Failed to fetch dashboard:", e);
            // Set empty data structure to prevent .map() errors
            setData({
                is_admin: false,
                announcements: [],
                lesson_plans: [],
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
                    console.log('[Dashboard] Available years from WP:', years);
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
        console.log('[Dashboard] Opening PDF:', file.title);
        setSelectedFile(file);
    };

    const renderView = () => {
        if (loading) return <div style={{ textAlign: 'center', padding: '100px' }}>Loading Portal Data...</div>;

        switch (activeTab) {
            case 'lessons':
                return (
                    <div className="view-container">
                        <div className="section-header" style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '20px' }}>
                            <h2>Lesson Plans Library</h2>
                        </div>
                        <LessonPlanSection items={data.lesson_plans} type="lesson" onView={handleView} onDelete={fetchData} />
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
                        <div className="downloads-grid" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))', gap: '15px' }}>
                            {data.resources.map(item => (
                                <PDFCard key={item.id} item={item} showThumb={false} onClick={handleView} onDelete={fetchData} />
                            ))}
                            {data.resources.length === 0 && <p>No resources found.</p>}
                        </div>
                    </div>
                );
            case 'policies':
                return (
                    <div className="view-container">
                        <div className="section-header" style={{ marginBottom: '20px' }}>
                            <h2>Policies & Procedures</h2>
                        </div>
                        <div className="downloads-grid" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))', gap: '15px' }}>
                            {data.forms.map(item => (
                                <PDFCard key={item.id} item={item} showThumb={false} onClick={handleView} onDelete={fetchData} />
                            ))}
                            {data.forms.length === 0 && <p>No policy documents available.</p>}
                        </div>
                    </div>
                );

            default:
                return <DashboardGrid data={data} refreshData={fetchData} onDocumentClick={handleView} />;
        }
    };

    return (
        <div className={`glass-app-shell ${isSidebarCollapsed ? 'sidebar-collapsed' : ''}`}>
            <Sidebar
                activeTab={activeTab}
                setActiveTab={setActiveTab}
                isAdmin={data?.is_admin}
                isCollapsed={isSidebarCollapsed}
                onToggle={() => setIsSidebarCollapsed(!isSidebarCollapsed)}
            />

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
                    <PDFViewerModal
                        file={selectedFile}
                        onClose={() => setSelectedFile(null)}
                    />
                )}
            </AnimatePresence>
        </div>
    );
};

export default Dashboard;
