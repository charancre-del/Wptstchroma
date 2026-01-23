import { motion } from 'framer-motion';
import logo from '../../assets/images/chroma_hex_logo.png';

const Sidebar = ({ activeTab, setActiveTab, isAdmin }) => {
    // Use localized logo URL if available, fallback to internal import
    const logoUrl = window.chromaPortalSettings?.logoUrl || logo;

    const menuItems = [
        { id: 'overview', label: 'Dashboard', icon: '🏠' },
        { id: 'lessons', label: 'Lesson Plans', icon: '📚' },
        { id: 'meals', label: 'Meal Plans', icon: '🍱' },
        { id: 'events', label: 'School Events', icon: '🗓️' },
        { id: 'news', label: 'News & Updates', icon: '📣' },
        { id: 'resources', label: 'Resources & Handbooks', icon: '📖' },
        { id: 'policies', label: 'Policies & Procedures', icon: '📋' },
        { id: 'downloads', label: 'Download Center', icon: '📥' }
    ];

    return (
        <div className="portal-sidebar">
            <div className="sidebar-logo">
                <img src={logoUrl} alt="Chroma Logo" className="logo-img" />
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
