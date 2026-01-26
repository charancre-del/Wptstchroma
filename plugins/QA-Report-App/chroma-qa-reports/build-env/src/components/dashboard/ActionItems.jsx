import React from 'react';
import { Link } from 'react-router-dom';
import { AlertCircle, ArrowRight, Clock } from 'lucide-react';

const ActionItems = ({ stats, isLoading }) => {
    const items = stats?.action_items || [];

    if (isLoading) return <div className="animate-pulse h-64 bg-gray-100 rounded-3xl" />;

    return (
        <div className="bg-white rounded-3xl p-8 border border-brand-ink/5 shadow-sm h-full flex flex-col">
            <h3 className="text-2xl font-serif font-bold text-brand-ink mb-6 flex items-center gap-2">
                <div className="w-8 h-8 rounded-lg bg-chroma-red/10 flex items-center justify-center text-chroma-red">
                    <AlertCircle size={18} />
                </div>
                Action Required
            </h3>

            <div className="space-y-3 flex-1 overflow-y-auto pr-2 custom-scrollbar">
                {items.length === 0 ? (
                    <div className="text-center py-8 text-brand-ink/40 text-sm font-bold">
                        No pending actions.
                    </div>
                ) : (
                    items.map((item) => (
                        <Link to={item.link} key={item.id} className="block">
                            <div className="p-4 rounded-2xl bg-brand-cream border border-brand-ink/5 flex items-center justify-between group hover:shadow-md transition-all cursor-pointer">
                                <div className="flex items-center gap-3">
                                    <div className={`w-2 h-2 rounded-full ${item.type === 'overdue' ? 'bg-chroma-red' :
                                        item.type === 'critical' ? 'bg-chroma-yellow' : 'bg-chroma-blue'
                                        }`} />
                                    <div>
                                        <h4 className="font-bold text-brand-ink text-sm">{item.title}</h4>
                                        <p className="text-xs text-brand-ink/40 font-bold flex items-center gap-1 mt-0.5">
                                            <Clock size={10} /> {item.date}
                                        </p>
                                    </div>
                                </div>
                                <div className="w-8 h-8 rounded-full bg-white flex items-center justify-center text-brand-ink/20 group-hover:text-chroma-blue group-hover:scale-110 transition-all">
                                    <ArrowRight size={16} />
                                </div>
                            </div>
                        </Link>
                    ))
                )}
            </div>

            <Link to="/reports?status=pending" className="block text-center w-full mt-6 py-3 rounded-xl border border-brand-ink/10 text-brand-ink/60 font-bold text-xs uppercase tracking-wider hover:bg-brand-ink hover:text-white transition-colors">
                View All Actions
            </Link>
        </div>
    );
};

export default ActionItems;
