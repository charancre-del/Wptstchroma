import React from 'react';
import { Link } from 'react-router-dom';

function StatCard({ icon: Icon, label, value, variant = 'blue', to }) {

    // Map variants to colors
    const colors = {
        blue: 'text-chroma-blue bg-chroma-blue/10',
        red: 'text-chroma-red bg-chroma-red/10',
        yellow: 'text-chroma-yellow bg-chroma-yellow/10',
        green: 'text-chroma-green bg-chroma-green/10',
        indigo: 'text-indigo-600 bg-indigo-50', // Fallback
        emerald: 'text-emerald-500 bg-emerald-50', // Fallback
        amber: 'text-amber-500 bg-amber-50', // Fallback
    };

    const iconColorClass = colors[variant] || colors.blue;
    const textColorClass = iconColorClass.split(' ')[0]; // Extract just the text color part

    const content = (
        <div className="bg-white rounded-3xl p-6 border border-brand-ink/5 shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden group h-full">
            {/* Background Icon */}
            <div className={`absolute -right-4 -top-4 opacity-5 group-hover:opacity-10 transition-opacity transform rotate-12`}>
                <Icon size={120} className={textColorClass} />
            </div>

            <div className="flex flex-col h-full relative z-10 justify-between">
                <div className={`w-14 h-14 rounded-2xl ${iconColorClass} flex items-center justify-center mb-4 transition-transform group-hover:scale-110 duration-300`}>
                    <Icon size={28} strokeWidth={2} />
                </div>
                <div>
                    <h3 className="text-4xl font-serif font-bold text-brand-ink mb-1">{value}</h3>
                    <p className="text-sm font-bold uppercase tracking-wider text-brand-ink/40">{label}</p>
                </div>
            </div>
        </div>
    );

    return to ? <Link to={to} className="block h-full">{content}</Link> : content;
}

export default StatCard;
