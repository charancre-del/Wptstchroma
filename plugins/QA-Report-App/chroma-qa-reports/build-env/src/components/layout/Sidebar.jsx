import React from 'react';
import { NavLink } from 'react-router-dom';
import { LayoutDashboard, School, FileText, PlusCircle, Settings, Menu, ChevronLeft, LogOut } from 'lucide-react';
import useUIStore from '@stores/useUIStore';
import useAuthStore from '@stores/useAuthStore';

const Sidebar = ({ isOpen }) => {
    const { toggleSidebar } = useUIStore();
    const { can } = useAuthStore();

    const navItems = [
        { path: '/', label: 'Overview', icon: LayoutDashboard },
        { path: '/schools', label: 'Schools', icon: School, requiredCap: 'cqa_manage_schools' },
        { path: '/reports', label: 'Reports', icon: FileText },
        { path: '/create', label: 'New Inspection', icon: PlusCircle, requiredCap: 'cqa_create_reports' },
    ];

    // Filter items based on capabilities
    const filteredItems = navItems.filter(item => !item.requiredCap || can(item.requiredCap));

    return (
        <aside className={`flex flex-col h-full bg-white border-r border-brand-ink/5 shadow-sm transition-all duration-300 ${isOpen ? 'w-64' : 'w-20'}`}>
            {/* Header */}
            <div className="h-20 flex items-center justify-between px-6 border-b border-brand-ink/5">
                {isOpen ? (
                    <div className="flex flex-col">
                        <span className="font-serif font-bold text-xl text-brand-ink">Chroma<span className="text-chroma-blue">QA</span></span>
                        <span className="text-[10px] text-brand-ink/40 uppercase font-bold tracking-widest">Reports</span>
                    </div>
                ) : (
                    <div className="w-8 h-8 rounded-full bg-chroma-blue flex items-center justify-center text-white font-serif font-bold">C</div>
                )}

                <button
                    onClick={toggleSidebar}
                    className="p-1.5 rounded-full hover:bg-brand-cream text-brand-ink/40 hover:text-brand-ink transition-colors"
                >
                    {isOpen ? <ChevronLeft size={18} /> : <Menu size={18} />}
                </button>
            </div>

            {/* Navigation */}
            <nav className="flex-1 py-6 px-3 space-y-2 overflow-y-auto">
                {filteredItems.map((item) => (
                    <NavLink
                        key={item.path}
                        to={item.path}
                        className={({ isActive }) => `
                            flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-200 group
                            ${isActive
                                ? 'bg-chroma-blue/10 text-chroma-blue font-bold shadow-sm'
                                : 'text-brand-ink/60 hover:bg-brand-cream hover:text-brand-ink font-medium'}
                            ${!isOpen && 'justify-center px-2'}
                        `}
                        title={!isOpen ? item.label : undefined}
                    >
                        <item.icon size={22} strokeWidth={2} className={`transition-transform duration-200 ${!isOpen ? 'group-hover:scale-110' : ''}`} />
                        <span className={`whitespace-nowrap overflow-hidden transition-all duration-200 ${isOpen ? 'opacity-100 w-auto' : 'opacity-0 w-0'}`}>
                            {item.label}
                        </span>
                    </NavLink>
                ))}
            </nav>

            {/* Footer */}
            <div className="p-4 border-t border-brand-ink/5 bg-brand-cream/30">
                <button className={`w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-chroma-red/80 hover:bg-chroma-red/10 hover:text-chroma-red transition-all ${!isOpen && 'justify-center px-2'}`}>
                    <LogOut size={20} />
                    <span className={`whitespace-nowrap overflow-hidden transition-all duration-200 ${isOpen ? 'opacity-100 w-auto' : 'opacity-0 w-0'} font-bold`}>
                        Sign Out
                    </span>
                </button>
            </div>
        </aside>
    );
};

export default Sidebar;
