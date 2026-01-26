import React from 'react';
import { NavLink } from 'react-router-dom';
import { LayoutDashboard, School, FileText, PlusCircle, Settings, Menu, ChevronLeft } from 'lucide-react';
import useUIStore from '../../stores/useUIStore';
import useAuthStore from '../../stores/useAuthStore';

const Sidebar = ({ isOpen }) => {
    const { toggleSidebar } = useUIStore();
    const { can } = useAuthStore();

    const navItems = [
        { path: '/', label: 'Dashboard', icon: LayoutDashboard },
        { path: '/schools', label: 'Schools', icon: School, requiredCap: 'cqa_manage_schools' },
        { path: '/reports', label: 'Reports', icon: FileText },
        { path: '/create', label: 'Create Report', icon: PlusCircle, requiredCap: 'cqa_create_reports' },
    ];

    // Filter items based on capabilities
    const filteredItems = navItems.filter(item => !item.requiredCap || can(item.requiredCap));

    return (
        <>
            {/* Header / Toggle */}
            <div className="h-16 flex items-center justify-between px-4 border-b border-gray-100">
                {isOpen && <span className="font-bold text-lg text-cqa-primary">Chroma QA</span>}
                <button
                    onClick={toggleSidebar}
                    className="p-1 rounded hover:bg-gray-100 text-gray-500 transition-colors"
                >
                    {isOpen ? <ChevronLeft size={20} /> : <Menu size={20} />}
                </button>
            </div>

            {/* Navigation */}
            <nav className="flex-1 py-4 overflow-y-auto">
                <ul className="space-y-1 px-2">
                    {filteredItems.map((item) => (
                        <li key={item.path}>
                            <NavLink
                                to={item.path}
                                className={({ isActive }) => `
                                    flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors
                                    ${isActive
                                        ? 'bg-cqa-primary-light/10 text-cqa-primary font-medium'
                                        : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'}
                                    ${!isOpen && 'justify-center'}
                                `}
                                title={!isOpen ? item.label : undefined}
                            >
                                <item.icon size={20} />
                                <span className={`whitespace-nowrap transition-opacity duration-200 ${isOpen ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'}`}>
                                    {item.label}
                                </span>
                            </NavLink>
                        </li>
                    ))}
                </ul>
            </nav>

            {/* Documentation / Help Link could go here */}
            <div className="p-4 border-t border-gray-100">
                {/* User Info or other footer items */}
            </div>
        </>
    );
};

export default Sidebar;
