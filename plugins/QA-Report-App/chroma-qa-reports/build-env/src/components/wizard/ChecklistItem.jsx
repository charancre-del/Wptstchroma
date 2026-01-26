import React from 'react';
import { Star, MessageSquare, Camera } from 'lucide-react';

const ChecklistItem = ({ item, response, onChange }) => {
    // Rating Options
    const ratings = [
        { value: 'exceeds', label: 'Exceeds', color: 'bg-emerald-100 text-emerald-800 border-emerald-200' },
        { value: 'meets', label: 'Meets', color: 'bg-amber-100 text-amber-800 border-amber-200' },
        { value: 'needs_improvement', label: 'Needs Improvement', color: 'bg-red-100 text-red-800 border-red-200' },
        { value: 'not_applicable', label: 'N/A', color: 'bg-gray-100 text-gray-800 border-gray-200' },
    ];

    const currentRating = response?.rating || '';
    const currentNotes = response?.notes || '';

    return (
        <div className="bg-white border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-all duration-200">
            <div className="flex justify-between items-start mb-3">
                <div className="flex-1">
                    <h4 className="text-md font-medium text-gray-900">{item.label}</h4>
                    {item.description && (
                        <p className="text-sm text-gray-500 mt-1">{item.description}</p>
                    )}
                </div>
            </div>

            {/* Rating Controls */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-2 mb-4">
                {ratings.map((rate) => (
                    <button
                        key={rate.value}
                        onClick={() => onChange(item.key || item.id, { ...response, rating: rate.value })}
                        className={`
                            px-3 py-2 rounded-md text-sm font-medium border transition-colors flex items-center justify-center gap-2
                            ${currentRating === rate.value
                                ? `${rate.color} ring-2 ring-offset-1 ring-cqa-primary`
                                : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'
                            }
                        `}
                    >
                        {rate.label}
                    </button>
                ))}
            </div>

            {/* Additional Inputs (Notes / Photos) */}
            <div className="flex flex-col gap-3">
                {/* Notes Toggle / Input */}
                <div className="relative">
                    <MessageSquare size={16} className="absolute top-3 left-3 text-gray-400" />
                    <textarea
                        className="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-cqa-primary focus:border-cqa-primary outline-none transition-shadow min-h-[60px]"
                        placeholder="Add notes..."
                        value={currentNotes}
                        onChange={(e) => onChange(item.id, { ...response, notes: e.target.value })}
                    />
                </div>

                {/* Photo Placeholder (Coming in Step 4, but item-level photos go here) */}
                <div className="flex justify-end">
                    <button className="text-xs text-gray-500 hover:text-cqa-primary flex items-center gap-1 transition-colors">
                        <Camera size={14} /> Add Info
                    </button>
                </div>
            </div>
        </div>
    );
};

export default ChecklistItem;
