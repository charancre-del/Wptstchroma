import React from 'react';
import { Outlet } from 'react-router-dom';
import Sidebar from './Sidebar';
import useUIStore from '../../stores/useUIStore';
import ConflictModal from '../common/ConflictModal';
import OfflineBanner from '../common/OfflineBanner';

const Shell = () => {
    const { isSidebarOpen } = useUIStore();

    return (
        <div className="flex h-screen bg-[#fdfbf7] overflow-hidden font-sans text-gray-900">
            <ConflictModal />

            {/* Sidebar */}
            <div className={`transition-all duration-300 ${isSidebarOpen ? 'w-64' : 'w-16'} bg-white border-r border-gray-200 shadow-sm flex flex-col z-20`}>
                <Sidebar isOpen={isSidebarOpen} />
            </div>

            {/* Main Content */}
            <main className="flex-1 overflow-auto relative flex flex-col">
                <OfflineBanner />
                <div className="max-w-7xl mx-auto p-6 w-full">
                    <Outlet />
                </div>
            </main>
        </div>
    );
};

export default Shell;
