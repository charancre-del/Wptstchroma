import React from 'react';
import { Star, MessageSquare, Camera } from 'lucide-react';

const ChecklistItem = ({ item, response, onChange, readOnly = false }) => {
    // Rating Options - Values MUST match backend Checklist_Response constants
    const ratings = [
        { value: 'yes', label: 'Exceeds/Meets', color: 'bg-emerald-100 text-emerald-800 border-emerald-200' },
        { value: 'sometimes', label: 'Needs Improvement', color: 'bg-amber-100 text-amber-800 border-amber-200' },
        { value: 'no', label: 'Critical/No', color: 'bg-red-100 text-red-800 border-red-200' },
        { value: 'na', label: 'N/A', color: 'bg-gray-100 text-gray-800 border-gray-200' },
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
            <div
                className={`grid grid-cols-2 md:grid-cols-4 gap-2 mb-4 ${readOnly ? 'opacity-90' : ''}`}
                role="radiogroup"
                aria-label={`Rating for ${item.label}`}
            >
                {ratings.map((rate) => {
                    const isSelected = currentRating === rate.value;
                    return (
                        <button
                            key={rate.value}
                            type="button"
                            role="radio"
                            aria-checked={isSelected}
                            tabIndex={isSelected ? 0 : -1} // Roving tabindex could be implemented, but simple toggle here
                            onClick={() => !readOnly && onChange(item.id, { ...response, rating: rate.value })}
                            disabled={readOnly}
                            className={`
                                px-3 py-2 rounded-md text-sm font-medium border transition-colors flex items-center justify-center gap-2
                                ${isSelected
                                    ? `${rate.color} ring-2 ring-offset-1 ring-cqa-primary`
                                    : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'
                                }
                                ${readOnly ? 'cursor-default' : ''}
                            `}
                        >
                            {rate.label}
                        </button>
                    );
                })}
            </div>

            {/* Additional Inputs (Notes / Photos) */}
            <div className="flex flex-col gap-3">
                {/* Notes Toggle / Input */}
                <div className="relative">
                    <MessageSquare size={16} className="absolute top-3 left-3 text-gray-400" />
                    <textarea
                        className={`w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm outline-none transition-shadow min-h-[60px] ${readOnly ? 'bg-gray-50 text-gray-600 cursor-default' : 'focus:ring-1 focus:ring-cqa-primary focus:border-cqa-primary'}`}
                        placeholder={readOnly ? "" : "Add notes..."}
                        value={currentNotes}
                        readOnly={readOnly}
                        onChange={(e) => !readOnly && onChange(item.id, { ...response, notes: e.target.value })}
                    />
                </div>

                {!readOnly && (
                    <div className="flex justify-end">
                        <button className="text-xs text-gray-500 hover:text-cqa-primary flex items-center gap-1 transition-colors">
                            <Camera size={14} /> Add Info
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
};

export default ChecklistItem;
