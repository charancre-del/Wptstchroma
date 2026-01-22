import React, { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import Sidebar from './Sidebar';
import Header from './Header';
import DashboardGrid from './DashboardGrid';
import LessonPlanSection from './LessonPlanSection';
import MealPlansSection from './MealPlansSection';
import DownloadCenter from './DownloadCenter';
import PDFCard from '../common/PDFCard';
import { AnimatePresence, motion } from 'framer-motion';

const Dashboard = () => {
    const { user } = useAuth();
    const [year, setYear] = useState(new Date().getFullYear().toString());
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [activeTab, setActiveTab] = useState('overview');

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

    const renderView = () => {
        if (loading) return <div style={{ textAlign: 'center', padding: '100px' }}>Loading Portal Data...</div>;

        switch (activeTab) {
            case 'lessons':
                return (
                    <div className="view-container">
                        <div className="section-header" style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '20px' }}>
                            <h2>Lesson Plans Library</h2>
                            {/* Add logic if needed */}
                        </div>
                        <LessonPlanSection items={data.lesson_plans} type="lesson" onView={(f) => { }} onDelete={fetchData} />
                    </div>
                );
            case 'meals':
                return (
                    <div className="view-container">
                        <div className="section-header" style={{ marginBottom: '20px' }}>
                            <h2>Nutritional Meal Plans</h2>
                        </div>
                        <MealPlansSection items={data.meal_plans} onView={() => { }} onDelete={fetchData} />
                    </div>
                );
            case 'events':
                return (
                    <div className="view-container">
                        <div className="section-header" style={{ marginBottom: '20px' }}>
                            <h2>Upcoming School Events</h2>
                        </div>
                        <div className="event-list" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))', gap: '20px' }}>
                            {data.events.map(event => (
                                <PDFCard key={event.id} item={event} onClick={() => { }} onDelete={fetchData} />
                            ))}
                        </div>
                    </div>
                );
            case 'downloads':
                return (
                    <div className="view-container">
                        <div className="section-header" style={{ marginBottom: '20px' }}>
                            <h2>Policy & Resource Center</h2>
                        </div>
                        <DownloadCenter resources={data.resources} forms={data.forms} onView={() => { }} onDelete={fetchData} />
                    </div>
                );
            default:
                return <DashboardGrid data={data} refreshData={fetchData} />;
        }
    };

    return (
        <div className="glass-app-shell">
            <Sidebar activeTab={activeTab} setActiveTab={setActiveTab} isAdmin={data?.is_admin} />

            <main className="portal-main">
                <div className="main-viewport">
                    <Header user={user} year={year} setYear={setYear} />

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
        </div>
    );
};

export default Dashboard;
