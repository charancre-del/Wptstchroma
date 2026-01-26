import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import useAuthStore from '../../stores/useAuthStore';
import useUIStore from '../../stores/useUIStore';
import useAutoSave from '../../hooks/useAutoSave';

// Steps
import StepSchool from './steps/StepSchool';
import StepMetadata from './steps/StepMetadata';
import StepChecklist from './steps/StepChecklist';
import StepPhotos from './steps/StepPhotos';
// import StepReview from './steps/StepReview'; // Coming soon

const ReportWizard = () => {
    const navigate = useNavigate();
    const { user } = useAuthStore();
    const { addToast } = useUIStore();
    const [isDirty, setIsDirty] = useState(false);

    // Wizard State
    const [currentStep, setCurrentStep] = useState(1);

    // Draft Data (In-Memory for now, IndexedDB later)
    const [draft, setDraft] = useState({
        school_id: 0,
        school_name: '', // For display
        previous_report_id: 0, // 0 = Explicit "No Link"
        previous_report_date: null, // For display
        report_type: 'tier1',
        inspection_date: new Date().toISOString().split('T')[0],
        // ... other fields
    });

    // Step Definitions
    const steps = [
        { id: 1, title: 'Select School', component: StepSchool },
        { id: 2, title: 'Report Details', component: StepMetadata },
        { id: 3, title: 'Checklist', component: StepChecklist },
        { id: 4, title: 'Photos', component: StepPhotos },
        { id: 5, title: 'Review', component: () => <div>Review Placeholder</div> },
    ];

    const CurrentComponent = steps[currentStep - 1].component;

    // Navigation Handlers
    const nextStep = () => {
        // AUDIT GUARDRAIL: Strict Payload Check
        if (currentStep === 1 && (!draft.school_id || draft.school_id === 0)) {
            addToast({ type: 'error', message: 'Please select a school to continue.' });
            return;
        }

        // Audit Guardrail: Metadata check? (Maybe report type required)

        if (currentStep < steps.length) {
            setCurrentStep(prev => prev + 1);
        }
    };

    const prevStep = () => {
        if (currentStep > 1) {
            setCurrentStep(prev => prev - 1);
        } else {
            // Cancel / Go Back to Dashboard?
            if (confirm('Exit wizard? Unsaved changes will be lost (v1 drafts coming soon).')) {
                navigate('/');
            }
        }
    };

    const updateDraft = (updates) => {
        setDraft(prev => ({ ...prev, ...updates }));
        setIsDirty(true);
    };

    // Auto-Save Hook (Handles DB sync & Conflict Modal)
    const { lastSaved, isSaving, saveError } = useAutoSave(draft, isDirty);

    return (
        <div className="max-w-4xl mx-auto bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden min-h-[600px] flex flex-col">
            {/* Wizard Header */}
            <div className="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 className="text-lg font-semibold text-gray-800">Create New Report</h2>
                <div className="text-sm text-gray-500">
                    Step {currentStep} of {steps.length}: <span className="font-medium text-gray-900">{steps[currentStep - 1].title}</span>
                </div>
            </div>

            {/* Progress Bar */}
            <div className="w-full bg-gray-200 h-1.5">
                <div
                    className="bg-cqa-primary h-1.5 transition-all duration-300 ease-in-out"
                    style={{ width: `${(currentStep / steps.length) * 100}%` }}
                ></div>
            </div>

            {/* Step Content */}
            <div className="flex-1 p-6 overflow-y-auto">
                <CurrentComponent
                    draft={draft}
                    updateDraft={updateDraft}
                    nextStep={nextStep}
                />
            </div>

            {/* Wizard Footer */}
            <div className="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-between items-center">
                <button
                    onClick={prevStep}
                    className="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-100 font-medium text-sm transition-colors"
                >
                    {currentStep === 1 ? 'Cancel' : 'Back'}
                </button>

                <div className="flex gap-2">
                    {/* Debug: <span className="text-xs text-mono text-gray-400 self-center">School: {draft.school_id} | Prev: {draft.previous_report_id}</span> */}

                    {currentStep < steps.length ? (
                        <button
                            onClick={nextStep}
                            className="px-4 py-2 bg-cqa-primary hover:bg-cqa-primary-dark text-white rounded-md font-medium text-sm transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled={currentStep === 1 && !draft.school_id}
                        >
                            Next Step
                        </button>
                    ) : (
                        <button
                            className="px-4 py-2 bg-cqa-success hover:bg-green-600 text-white rounded-md font-medium text-sm transition-colors shadow-sm"
                        >
                            Submit Report
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
};

export default ReportWizard;
