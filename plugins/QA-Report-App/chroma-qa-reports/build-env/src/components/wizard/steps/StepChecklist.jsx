import React, { useState, useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import apiFetch from '../../../api/client';
import ChecklistSection from '@components/wizard/checklist/ChecklistSection';
import { ListChecks, AlertTriangle } from 'lucide-react';

const StepChecklist = ({ draft, updateDraft, nextStep }) => {
    // Determine checklist type from draft or default to 'tier1'
    const checklistType = draft.report_type || 'tier1';

    // Fetch Checklist Definition
    const { data: checklistDef, isLoading, error } = useQuery({
        queryKey: ['checklist', checklistType],
        queryFn: () => apiFetch(`checklists/${checklistType}`),
        staleTime: Infinity, // Definitions rarely change
    });

    // Local state to manage responses before pushing to draft
    // Initialize from draft.responses if exists
    // Structure: { itemId: { rating: 'meets', notes: '...' } }
    const [responses, setResponses] = useState(draft.responses || {});

    // Sync responses back to draft parent whenever they change
    // Debounced sync could be optimizing, but for now we rely on the parents updates
    useEffect(() => {
        updateDraft({ responses });
    }, [responses]);

    const handleResponseChange = (itemId, newResponse) => {
        setResponses(prev => ({
            ...prev,
            [itemId]: newResponse
        }));
    };

    // Calculate Completion
    const calculateCompletion = () => {
        if (!checklistDef?.sections) return { total: 0, answered: 0 };

        let total = 0;
        let answered = 0;

        checklistDef.sections.forEach(section => {
            total += section.items.length;
            section.items.forEach(item => {
                if (responses[item.id]?.rating) answered++;
            });
        });

        return { total, answered, percent: total > 0 ? Math.round((answered / total) * 100) : 0 };
    };

    const completion = calculateCompletion();

    if (isLoading) {
        return (
            <div className="flex flex-col items-center justify-center py-20 text-gray-500">
                <span className="spinner h-8 w-8 mb-4 border-4 border-cqa-primary border-t-transparent rounded-full animate-spin"></span>
                <p>Loading checklist definition...</p>
            </div>
        );
    }

    if (error) {
        return (
            <div className="bg-red-50 p-6 rounded-lg border border-red-200 text-center">
                <AlertTriangle className="mx-auto text-red-500 mb-2" size={32} />
                <h3 className="text-lg font-bold text-red-800">Failed to load checklist</h3>
                <p className="text-red-600 mb-4">Could not retrieve the checklist definition for {checklistType}.</p>
                <button
                    onClick={() => window.location.reload()}
                    className="px-4 py-2 bg-white border border-red-300 text-red-700 rounded hover:bg-red-50"
                >
                    Retry
                </button>
            </div>
        );
    }

    return (
        <div className="space-y-6">

            {/* Header / Stats */}
            <div className="bg-white p-4 rounded-lg border border-gray-200 shadow-sm flex items-center justify-between sticky top-0 z-20">
                <div>
                    <h2 className="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <ListChecks className="text-cqa-primary" />
                        {checklistDef.title || 'Inspection Checklist'}
                    </h2>
                    <p className="text-sm text-gray-500">
                        {completion.answered} of {completion.total} items rated
                    </p>
                </div>
                <div className="text-right">
                    <div className="text-2xl font-bold text-cqa-primary">{completion.percent}%</div>
                    <div className="text-xs text-gray-400 uppercase tracking-wide">Complete</div>
                </div>
            </div>

            {/* Sections */}
            <div>
                {checklistDef.sections?.map((section) => (
                    <ChecklistSection
                        key={section.id}
                        section={section}
                        responses={responses}
                        onResponseChange={handleResponseChange}
                    />
                ))}
            </div>
        </div>
    );
};

export default StepChecklist;
