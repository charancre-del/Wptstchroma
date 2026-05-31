import React, { useState } from 'react';
import { motion } from 'framer-motion';
import { useAuth } from '../../context/AuthContext';
import Announcements from './Announcements';
import LessonPlanSection from './LessonPlanSection';
import MealPlansSection from './MealPlansSection';
import PDFCard from '../common/PDFCard';
import UploadModal from '../common/UploadModal';

const DashboardGrid = ({ data, refreshData, onDocumentClick, onTabChange }) => {
    const { user } = useAuth();
    const [showUpload, setShowUpload] = useState(false);
    const [uploadType, setUploadType] = useState('cp_lesson_plan');

    const containerVariants = {
        hidden: { opacity: 0 },
        visible: {
            opacity: 1,
            transition: { staggerChildren: 0.1 }
        }
    };

    const itemVariants = {
        hidden: { y: 20, opacity: 0 },
        visible: { y: 0, opacity: 1 }
    };

    if (!data) return null;

    const handleUploadClick = (type) => {
        setUploadType(type);
        setShowUpload(true);
    };

    const handleView = (item) => {
        if (onDocumentClick) onDocumentClick(item);
    };

    return (
        <motion.div
            className="dashboard-grid"
            variants={containerVariants}
            initial="hidden"
            animate="visible"
        >
            {/* Announcements Ticker */}
            <motion.div variants={itemVariants}>
                <Announcements items={data.announcements} isAdmin={data.is_admin} onAdd={() => handleUploadClick('cp_announcement')} onDelete={refreshData} />
            </motion.div>

            {/* Main Sections */}
            <div className="grid-container" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(450px, 1fr))', gap: '30px' }}>

                <motion.div variants={itemVariants} className="glass-card section-card">
                    <div className="section-header" style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '15px' }}>
                        <h3 onClick={() => onTabChange('lessons')} style={{ cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '8px' }}>
                            Lesson Plans <span style={{ fontSize: '0.8rem', opacity: 0.5 }}>→</span>
                        </h3>
                        {data.is_admin && <button onClick={() => handleUploadClick('cp_lesson_plan')} className="add-btn">+ Add New</button>}
                    </div>
                    <LessonPlanSection items={data.lesson_plans} type="lesson" onView={handleView} onDelete={refreshData} />
                </motion.div>

                <motion.div variants={itemVariants} className="glass-card section-card">
                    <div className="section-header" style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '15px' }}>
                        <h3 onClick={() => onTabChange('home_activities')} style={{ cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '8px' }}>
                            Home Activities <span style={{ fontSize: '0.8rem', opacity: 0.5 }}>→</span>
                        </h3>
                        {data.is_admin && <button onClick={() => handleUploadClick('cp_home_activity')} className="add-btn">+ Add New</button>}
                    </div>
                    <LessonPlanSection items={data.home_activities} type="activity" onView={handleView} onDelete={refreshData} />
                </motion.div>

                <motion.div variants={itemVariants} className="glass-card section-card">
                    <div className="section-header" style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '15px' }}>
                        <h3 onClick={() => onTabChange('meals')} style={{ cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '8px' }}>
                            Meal Plans <span style={{ fontSize: '0.8rem', opacity: 0.5 }}>→</span>
                        </h3>
                        {data.is_admin && <button onClick={() => handleUploadClick('cp_meal_plan')} className="add-btn">+ Add New</button>}
                    </div>
                    <MealPlansSection items={data.meal_plans} onView={handleView} onDelete={refreshData} />
                </motion.div>



                <motion.div variants={itemVariants} className="glass-card section-card">
                    <div className="section-header" style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '15px' }}>
                        <h3 onClick={() => onTabChange('resources')} style={{ cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '8px' }}>
                            Resources <span style={{ fontSize: '0.8rem', opacity: 0.5 }}>→</span>
                        </h3>
                        {data.is_admin && <button onClick={() => handleUploadClick('cp_resource')} className="add-btn">+ Add</button>}
                    </div>
                    <LessonPlanSection items={data.resources} type="resource" onView={handleView} onDelete={refreshData} />
                </motion.div>

                <motion.div variants={itemVariants} className="glass-card section-card">
                    <div className="section-header" style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '15px' }}>
                        <h3 onClick={() => onTabChange('policies')} style={{ cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '8px' }}>
                            Policies & Procedures <span style={{ fontSize: '0.8rem', opacity: 0.5 }}>→</span>
                        </h3>
                        {data.is_admin && <button onClick={() => handleUploadClick('cp_form')} className="add-btn">+ Add</button>}
                    </div>
                    <LessonPlanSection items={data.forms} type="policy" onView={handleView} onDelete={refreshData} />
                </motion.div>

                <motion.div variants={itemVariants} className="glass-card section-card">
                    <div className="section-header" style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '15px' }}>
                        <h3 onClick={() => onTabChange('events')} style={{ cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '8px' }}>
                            School Events <span style={{ fontSize: '0.8rem', opacity: 0.5 }}>→</span>
                        </h3>
                        {data.is_admin && <button onClick={() => handleUploadClick('cp_event')} className="add-btn">+ Add Event</button>}
                    </div>
                    <div className="event-grid" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(280px, 1fr))', gap: '15px' }}>
                        {data.events.slice(0, 3).map(event => (
                            <PDFCard key={event.id} item={event} onClick={handleView} onDelete={refreshData} />
                        ))}
                        {data.events.length === 0 && <p style={{ fontStyle: 'italic', color: '#999' }}>No upcoming events.</p>}
                    </div>
                </motion.div>

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
        </motion.div>
    );
};

export default DashboardGrid;
