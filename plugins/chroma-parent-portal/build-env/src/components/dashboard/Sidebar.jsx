import React from 'react';
import { motion } from 'framer-motion';

const Sidebar = ({ activeTab, setActiveTab, isAdmin }) => {
    const menuItems = [
        { id: 'overview', label: 'Dashboard', icon: '🏠' },
        { id: 'lessons', label: 'Lesson Plans', icon: '📚' },
        { id: 'meals', label: 'Meal Plans', icon: '🍱' },
        { id: 'events', label: 'School Events', icon: '🗓️' },
        { id: 'downloads', label: 'Download Center', icon: '📥' }
    ];

    return (
        <div className="portal-sidebar">
            <div className="sidebar-logo">
                <div className="logo-icon">C</div>
                <span>Portal</span>
            </div>

            <nav className="sidebar-nav">
                {menuItems.map(item => (
                    <motion.button
                        key={item.id}
                        whileHover={{ x: 5 }}
                        whileTap={{ scale: 0.95 }}
                        className={`nav-item ${activeTab === item.id ? 'active' : ''}`}
                        onClick={() => setActiveTab(item.id)}
                    >
                        <span className="icon">{item.icon}</span>
                        <span className="label">{item.label}</span>
                        {activeTab === item.id && (
                            <motion.div
                                layoutId="active-pill"
                                className="active-indicator"
                            />
                        )}
                    </motion.button>
                ))}
            </nav>

            <div className="sidebar-footer">
                {isAdmin && <div className="admin-badge">Admin Mode</div>}
            </div>
        </div>
    );
};

export default Sidebar;
