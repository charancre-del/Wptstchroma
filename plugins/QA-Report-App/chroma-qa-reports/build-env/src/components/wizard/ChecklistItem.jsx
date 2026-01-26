import React, { useState } from 'react';
import { cn } from '../../utils/helpers';
import {
    Check,
    X,
    Star,
    MessageSquare,
    ChevronDown,
    ChevronUp,
    Camera,
} from 'lucide-react';

/**
 * ChecklistItem - Renders different types of checklist items
 * Types: boolean, rating_notes, enrollment, text
 */
export function ChecklistItem({ item, response, onChange }) {
    const [showNotes, setShowNotes] = useState(!!response?.notes);

    const handleValueChange = (value) => {
        onChange(value, response?.notes || '');
    };

    const handleNotesChange = (notes) => {
        onChange(response?.value, notes);
    };

    const renderControl = () => {
        switch (item.type) {
            case 'boolean':
                return (
                    <BooleanControl
                        value={response?.value}
                        onChange={handleValueChange}
                        labels={item.labels}
                    />
                );

            case 'rating_notes':
            case 'rating':
                return (
                    <RatingControl
                        value={response?.value}
                        onChange={handleValueChange}
                        max={item.max || 4}
                        labels={item.labels}
                    />
                );

            case 'enrollment':
                return (
                    <EnrollmentControl
                        value={response?.value}
                        onChange={handleValueChange}
                        fields={item.fields}
                    />
                );

            case 'text':
                return (
                    <TextControl
                        value={response?.value || ''}
                        onChange={handleValueChange}
                        placeholder={item.placeholder}
                    />
                );

            default:
                return (
                    <BooleanControl
                        value={response?.value}
                        onChange={handleValueChange}
                    />
                );
        }
    };

    const hasResponse = response?.value !== undefined && response?.value !== '';

    return (
        <div className="py-4">
            <div className="flex items-start gap-4">
                {/* Item Info */}
                <div className="flex-1 min-w-0">
                    <div className="flex items-start gap-2">
                        <span className={cn(
                            'w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium flex-shrink-0 mt-0.5',
                            hasResponse
                                ? 'bg-green-100 text-green-700'
                                : 'bg-gray-100 text-gray-500'
                        )}>
                            {hasResponse ? <Check className="w-3 h-3" /> : item.number || '•'}
                        </span>
                        <div>
                            <p className="text-gray-900 font-medium">{item.text || item.label}</p>
                            {item.description && (
                                <p className="text-sm text-gray-500 mt-1">{item.description}</p>
                            )}
                        </div>
                    </div>
                </div>

                {/* Control */}
                <div className="flex-shrink-0">
                    {renderControl()}
                </div>
            </div>

            {/* Notes Section */}
            {(item.type === 'rating_notes' || item.allow_notes !== false) && (
                <div className="mt-3 ml-8">
                    <button
                        onClick={() => setShowNotes(!showNotes)}
                        className="text-sm text-gray-500 flex items-center gap-1 hover:text-gray-700"
                    >
                        <MessageSquare className="w-4 h-4" />
                        {showNotes ? 'Hide notes' : 'Add notes'}
                        {showNotes ? <ChevronUp className="w-3 h-3" /> : <ChevronDown className="w-3 h-3" />}
                    </button>

                    {showNotes && (
                        <textarea
                            value={response?.notes || ''}
                            onChange={(e) => handleNotesChange(e.target.value)}
                            placeholder="Add observations, findings, or recommendations..."
                            rows={2}
                            className="mt-2 w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none"
                        />
                    )}
                </div>
            )}
        </div>
    );
}

/**
 * Boolean Control - Yes/No toggle
 */
function BooleanControl({ value, onChange, labels = { yes: 'Yes', no: 'No' } }) {
    return (
        <div className="flex items-center gap-2">
            <button
                onClick={() => onChange(true)}
                className={cn(
                    'px-4 py-2 rounded-lg flex items-center gap-2 transition-all',
                    value === true
                        ? 'bg-green-100 text-green-700 border-2 border-green-500'
                        : 'bg-gray-50 text-gray-600 border border-gray-200 hover:border-green-300'
                )}
            >
                <Check className="w-4 h-4" />
                {labels.yes || 'Yes'}
            </button>
            <button
                onClick={() => onChange(false)}
                className={cn(
                    'px-4 py-2 rounded-lg flex items-center gap-2 transition-all',
                    value === false
                        ? 'bg-red-100 text-red-700 border-2 border-red-500'
                        : 'bg-gray-50 text-gray-600 border border-gray-200 hover:border-red-300'
                )}
            >
                <X className="w-4 h-4" />
                {labels.no || 'No'}
            </button>
        </div>
    );
}

/**
 * Rating Control - 1-4 star rating
 */
function RatingControl({ value, onChange, max = 4, labels }) {
    const ratingLabels = labels || {
        1: 'Poor',
        2: 'Fair',
        3: 'Good',
        4: 'Excellent',
    };

    return (
        <div className="flex items-center gap-1">
            {Array.from({ length: max }, (_, i) => i + 1).map((rating) => (
                <button
                    key={rating}
                    onClick={() => onChange(rating)}
                    title={ratingLabels[rating]}
                    className={cn(
                        'w-10 h-10 rounded-lg flex items-center justify-center transition-all',
                        value >= rating
                            ? 'bg-amber-100 text-amber-600'
                            : 'bg-gray-50 text-gray-300 hover:bg-amber-50 hover:text-amber-400'
                    )}
                >
                    <Star className={cn('w-5 h-5', value >= rating && 'fill-current')} />
                </button>
            ))}
            {value && (
                <span className="ml-2 text-sm text-gray-500">
                    {ratingLabels[value]}
                </span>
            )}
        </div>
    );
}

/**
 * Enrollment Control - Number inputs for enrollment data
 */
function EnrollmentControl({ value = {}, onChange, fields = ['licensed', 'enrolled', 'present'] }) {
    const handleFieldChange = (field, fieldValue) => {
        onChange({
            ...value,
            [field]: parseInt(fieldValue) || 0,
        });
    };

    const fieldLabels = {
        licensed: 'Licensed',
        enrolled: 'Enrolled',
        present: 'Present',
    };

    return (
        <div className="flex items-center gap-3">
            {fields.map((field) => (
                <div key={field} className="flex flex-col items-center">
                    <input
                        type="number"
                        min="0"
                        value={value[field] || ''}
                        onChange={(e) => handleFieldChange(field, e.target.value)}
                        className="w-16 px-2 py-1.5 text-center border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    />
                    <span className="text-xs text-gray-500 mt-1">{fieldLabels[field] || field}</span>
                </div>
            ))}
        </div>
    );
}

/**
 * Text Control - Free text input
 */
function TextControl({ value, onChange, placeholder }) {
    return (
        <input
            type="text"
            value={value}
            onChange={(e) => onChange(e.target.value)}
            placeholder={placeholder || 'Enter value...'}
            className="w-48 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
        />
    );
}

export default ChecklistItem;
