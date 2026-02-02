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
        <aside className={`flex flex-col h-full bg-white border-r border-brand-ink/5 shadow-sm transition-all duration-500 ease-in-out ${isOpen ? 'w-64' : 'w-16'}`}>
            {/* Header */}
            <div className={`h-20 flex items-center ${isOpen ? 'justify-between px-4' : 'justify-center'} border-b border-brand-ink/5 transition-all duration-300`}>
                {isOpen ? (
                    <div className="flex flex-col animate-in fade-in slide-in-from-left-2 duration-300">
                        <span className="font-serif font-bold text-xl text-brand-ink">Chroma<span className="text-chroma-blue">QA</span></span>
                        <span className="text-[10px] text-brand-ink/40 uppercase font-bold tracking-widest">Reports</span>
                    </div>
                ) : null}

                <button
                    onClick={toggleSidebar}
                    className={`p-2 rounded-xl hover:bg-brand-cream text-brand-ink/40 hover:text-brand-ink transition-all duration-300 ${!isOpen && 'mx-auto'}`}
                    title={isOpen ? 'Collapse Sidebar' : 'Expand Sidebar'}
                >
                    {isOpen ? <ChevronLeft size={18} /> : <div className="w-10 h-10 rounded-xl bg-chroma-blue shadow-lg shadow-chroma-blue/20 flex items-center justify-center text-white"><Menu size={20} /></div>}
                </button>
            </div>

            {/* Navigation */}
            <nav className="flex-1 py-6 px-3 space-y-2 overflow-y-auto">
                {filteredItems.map((item) => (
                    <NavLink
                        key={item.path}
                        to={item.path}
                        className={({ isActive }) => `
                            flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-300 group
                            ${isActive
                                ? 'bg-chroma-blue text-white font-bold shadow-lg shadow-chroma-blue/25 translate-x-1'
                                : 'text-brand-ink/60 hover:bg-brand-cream hover:text-brand-ink font-medium'}
                            ${!isOpen && 'justify-center px-1 mx-2'}
                        `}
                        title={!isOpen ? item.label : undefined}
                    >
                        {({ isActive }) => (
                            <>
                                <item.icon size={22} strokeWidth={2} className={`transition-all duration-300 ${!isOpen ? 'group-hover:scale-110' : ''} ${isActive && !isOpen ? 'scale-110' : ''}`} />
                                <span className={`whitespace-nowrap overflow-hidden transition-all duration-300 ${isOpen ? 'opacity-100 w-auto' : 'opacity-0 w-0 pointer-events-none'}`}>
                                    {item.label}
                                </span>
                            </>
                        )}
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
