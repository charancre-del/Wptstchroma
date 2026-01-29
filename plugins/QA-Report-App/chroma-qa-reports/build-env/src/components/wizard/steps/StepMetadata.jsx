import React from 'react';
import { Calendar, FileType, FileText, AlertTriangle } from 'lucide-react';

const StepMetadata = ({ draft, updateDraft }) => {

    const handleChange = (field, value) => {
        updateDraft({ [field]: value });
    };

    return (
        <div className="space-y-8 animate-fade-in">
            {/* Closing Notes (Prominent) */}
            <div className="space-y-2 bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                <label className="block text-sm font-bold text-gray-800 flex items-center gap-2">
                    <FileText size={16} className="text-cqa-primary" /> Closing Remarks
                </label>
                <textarea
                    className="w-full p-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-cqa-primary focus:border-cqa-primary outline-none h-32 resize-none"
                    placeholder="General observations or executive summary for this visit..."
                    value={draft.closing_notes || ''}
                    onChange={(e) => handleChange('closing_notes', e.target.value)}
                />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">

                {/* Inspection Date */}
                <div className="space-y-2">
                    <label className="block text-sm font-medium text-gray-700 flex items-center gap-2">
                        <Calendar size={16} /> Inspection Date
                    </label>
                    <input
                        type="date"
                        className="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-cqa-primary focus:border-cqa-primary outline-none"
                        value={draft.inspection_date || ''}
                        max={new Date().toISOString().split('T')[0]}
                        onChange={(e) => handleChange('inspection_date', e.target.value)}
                        required
                    />
                </div>

                {/* Report Type */}
                <div className="space-y-2">
                    <label className="block text-sm font-medium text-gray-700 flex items-center gap-2">
                        <FileType size={16} /> Report Type
                    </label>
                    <select
                        className="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-cqa-primary focus:border-cqa-primary outline-none bg-white"
                        value={draft.report_type}
                        onChange={(e) => handleChange('report_type', e.target.value)}
                    >
                        <option value="tier1">Tier 1 (Standard)</option>
                        <option value="tier1_tier2">Tier 1 + Tier 2 (Comprehensive)</option>
                        <option value="new_acquisition">New Acquisition</option>
                    </select>
                </div>
            </div>

            {/* Linking Confirmation (Audit Guardrail) */}
            <div className="bg-gray-50 p-4 rounded-md border border-gray-200">
                <h4 className="text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide text-xs">Configuration Summary</h4>
                <div className="flex flex-col gap-2 text-sm text-gray-600">
                    <div className="flex justify-between">
                        <span>School:</span>
                        <span className="font-medium text-gray-900">{draft.school_name}</span>
                    </div>
                    <div className="flex justify-between">
                        <span>Comparison Link:</span>
                        {draft.previous_report_id ? (
                            <span className="font-medium text-green-600 flex items-center gap-1">
                                <FileText size={14} /> Linked to Report #{draft.previous_report_id} ({draft.previous_report_date})
                            </span>
                        ) : (
                            <span className="font-medium text-orange-600 flex items-center gap-1">
                                <AlertTriangle size={14} /> No Comparison (Fresh Start)
                            </span>
                        )}
                    </div>
                </div>
            </div>

        </div>
    );
};

export default StepMetadata;
