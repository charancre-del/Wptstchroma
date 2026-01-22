import React, { useState } from 'react';
import Announcements from './Announcements';
import LessonPlanSection from './LessonPlanSection';
import DownloadCenter from './DownloadCenter';
import MealPlansSection from './MealPlansSection';
import UploadModal from '../common/UploadModal';
import PDFViewerModal from '../common/PDFViewerModal';
import { useAuth } from '../../context/AuthContext';

const DashboardGrid = ({ data, refreshData }) => {
    const { user } = useAuth();
    const [showUpload, setShowUpload] = useState(false);
    const [uploadType, setUploadType] = useState('cp_lesson_plan');
    const [viewFile, setViewFile] = useState(null);

    if (!data) return null;

    const handleUploadClick = (type) => {
        setUploadType(type);
        setShowUpload(true);
    };

    const handleView = (item) => {
        if (item.pdf_url) setViewFile(item);
    };

    return (
        <div className="dashboard-grid">
            {/* Announcements Ticker */}
            <Announcements items={data.announcements} isAdmin={data.is_admin} onAdd={() => handleUploadClick('cp_announcement')} onDelete={refreshData} />

            {/* Main Sections */}
            <div className="grid-container" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(350px, 1fr))', gap: '30px' }}>

                {/* Lesson Plans */}
                <div className="glass-card section-card" style={{ background: 'rgba(255,255,255,0.4)', borderRadius: '15px', padding: '20px' }}>
                    <div className="section-header" style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '15px' }}>
                        <h3>Lesson Plans</h3>
                        {data.is_admin && <button onClick={() => handleUploadClick('cp_lesson_plan')} className="add-btn">+ Add New</button>}
                    </div>
                    <LessonPlanSection items={data.lesson_plans} type="lesson" onView={handleView} onDelete={refreshData} />
                </div>

                {/* Meal Plans */}
                <div className="glass-card section-card" style={{ background: 'rgba(255,255,255,0.4)', borderRadius: '15px', padding: '20px' }}>
                    <div className="section-header" style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '15px' }}>
                        <h3>Meal Plans</h3>
                        {data.is_admin && <button onClick={() => handleUploadClick('cp_meal_plan')} className="add-btn">+ Add New</button>}
                    </div>
                    <MealPlansSection items={data.meal_plans} onView={handleView} onDelete={refreshData} />
                </div>

                {/* Download Center (Forms & Policies) */}
                <div className="glass-card section-card" style={{ background: 'rgba(255,255,255,0.4)', borderRadius: '15px', padding: '20px', gridColumn: '1 / -1' }}>
                    <div className="section-header" style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '15px' }}>
                        <h3>Download Center</h3>
                        {data.is_admin && <button onClick={() => handleUploadClick('cp_resource')} className="add-btn">+ Add Resource</button>}
                    </div>
                    <DownloadCenter resources={data.resources} forms={data.forms} onView={handleView} onDelete={refreshData} />
                </div>

                {/* Events Section */}
                <div className="glass-card section-card" style={{ background: 'rgba(255,255,255,0.4)', borderRadius: '15px', padding: '20px' }}>
                    <div className="section-header" style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '15px' }}>
                        <h3>School Events</h3>
                        {data.is_admin && <button onClick={() => handleUploadClick('cp_event')} className="add-btn">+ Add Event</button>}
                    </div>
                    <div className="event-list">
                        {data.events.map(event => (
                            <PDFCard key={event.id} item={event} onClick={() => { }} onDelete={refreshData} />
                        ))}
                        {data.events.length === 0 && <p style={{ fontStyle: 'italic', color: '#999' }}>No upcoming events.</p>}
                    </div>
                </div>

            </div>

            {/* Admin Upload Modal */}
            {data.is_admin && showUpload && (
                <UploadModal
                    isOpen={showUpload}
                    onClose={() => setShowUpload(false)}
                    type={uploadType}
                    onSuccess={refreshData}
                />
            )}

            {viewFile && <PDFViewerModal file={viewFile} onClose={() => setViewFile(null)} />}
        </div>
    );
};

export default DashboardGrid;
