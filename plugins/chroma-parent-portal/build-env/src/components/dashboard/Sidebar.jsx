import { motion } from 'framer-motion';
import logo from '../../assets/images/chroma_hex_logo.png';

const Sidebar = ({ activeTab, setActiveTab, isAdmin, isCollapsed, onToggle }) => {
    const logoUrl = window.chromaPortalSettings?.logoUrl || logo;

    const menuItems = [
        { id: 'overview', label: 'Dashboard', icon: '🏠' },
        { id: 'lessons', label: 'Lesson Plans', icon: '📚' },
        { id: 'meals', label: 'Meal Plans', icon: '🍱' },
        { id: 'events', label: 'School Events', icon: '🗓️' },
        { id: 'news', label: 'News & Updates', icon: '📣' },
        { id: 'resources', label: 'Resources', icon: '📖' },
        { id: 'policies', label: 'Policies & Procedures', icon: '📋' }
    ];

    return (
        <div
            className={`portal-sidebar ${isCollapsed ? 'collapsed' : ''}`}
            role="navigation"
            aria-label="Main navigation"
        >
            <button
                className="sidebar-toggle"
                onClick={onToggle}
                aria-label={isCollapsed ? 'Expand sidebar' : 'Collapse sidebar'}
                aria-expanded={!isCollapsed}
            >
                <motion.span animate={{ rotate: isCollapsed ? 180 : 0 }}>
                    ◀
                </motion.span>
            </button>

            <div className="sidebar-logo">
                <img src={logoUrl} alt="Chroma Logo" className="logo-img" />
                {!isCollapsed && <span>Portal</span>}
            </div>

            <nav className="sidebar-nav">
                {menuItems.map(item => (
                    <motion.button
                        key={item.id}
                        whileHover={{ x: isCollapsed ? 0 : 5 }}
                        whileTap={{ scale: 0.95 }}
                        className={`nav-item ${activeTab === item.id ? 'active' : ''}`}
                        onClick={() => setActiveTab(item.id)}
                        title={isCollapsed ? item.label : ''}
                    >
                        <span className="icon">{item.icon}</span>
                        {!isCollapsed && <span className="label">{item.label}</span>}
                        {activeTab === item.id && !isCollapsed && (
                            <motion.div
                                layoutId="active-pill"
                                className="active-indicator"
                            />
                        )}
                    </motion.button>
                ))}
            </nav>

            <div className="sidebar-footer">
                {isAdmin && <div className="admin-badge">{isCollapsed ? 'A' : 'Admin Mode'}</div>}
            </div>
        </div>
    );
};

export default Sidebar;
