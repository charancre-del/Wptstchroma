import React from 'react';
import ChecklistItem from '../ChecklistItem';

const ChecklistSection = ({ section, responses, onResponseChange, readOnly = false }) => {

    // Calculate progress for this section
    const totalItems = section.items.length;
    const answeredItems = section.items.filter(item => responses[item.id]?.rating).length;
    const progress = Math.round((answeredItems / totalItems) * 100);

    return (
        <div className="mb-8 animate-fade-in-up">
            {/* Section Header */}
            <div className="flex items-center justify-between mb-4 sticky top-0 bg-gray-50/95 backdrop-blur z-10 p-2 rounded-t-lg border-b border-gray-200">
                <h3 className="text-lg font-bold text-gray-800 uppercase tracking-wide">
                    {section.title}
                </h3>
                <div className="flex items-center gap-2 text-sm text-gray-600">
                    <div className="w-24 bg-gray-200 h-2 rounded-full overflow-hidden">
                        <div
                            className="bg-cqa-success h-full transition-all duration-500"
                            style={{ width: `${progress}%` }}
                        ></div>
                    </div>
                    <span>{answeredItems}/{totalItems}</span>
                </div>
            </div>

            {/* Items Grid */}
            <div className="grid grid-cols-1 gap-6">
                {section.items.map((item) => {
                    const itemKey = item.key || item.id;
                    return (
                        <ChecklistItem
                            key={itemKey}
                            item={item}
                            response={responses[itemKey] || {}}
                            readOnly={readOnly}
                            onChange={(itemId, response) => onResponseChange(itemId, response, section.key)}
                        />
                    );
                })}
            </div>
        </div>
    );
};

export default ChecklistSection;
