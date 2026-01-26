import React, { useState, useEffect } from 'react';
import { useNavigate, useParams, useSearchParams } from 'react-router-dom';
import { useReportWizardStore } from '../../stores/index';
import useAuthStore from '../../stores/useAuthStore';
import useUIStore from '../../stores/useUIStore';
import useAutoSave from '../../hooks/useAutoSave';
import { useReport } from '../../hooks/useQueries';
import apiFetch from '../../api/client';

// Steps
import StepSchool from './steps/StepSchool';
import StepMetadata from './steps/StepMetadata';
import StepChecklist from './steps/StepChecklist';
import StepPhotos from './steps/StepPhotos';
import StepAISummary from './steps/StepAISummary';
import StepReview from './steps/StepReview';

const ReportWizard = () => {
    const navigate = useNavigate();
    const { id } = useParams();
    const [searchParams] = useSearchParams();
    const { addToast } = useUIStore();
    const {
        report,
        responses,
        photos,
        updateReportData: setDraft,
        currentStep,
        setStep: setCurrentStep,
        reset: resetWizard
    } = useReportWizardStore();

    const [isDirty, setIsDirty] = useState(false);

    // Fetch report if ID is present (Edit Mode)
    const { data: existingReport } = useReport(id);

    // Initialize from Params or Existing Report
    useEffect(() => {
        if (existingReport && id) {
            setDraft({
                ...existingReport,
                school_id: parseInt(existingReport.school_id),
            });
        } else {
            const schoolId = searchParams.get('school');
            if (schoolId) {
                setDraft({ school_id: parseInt(schoolId) });
            }
        }
    }, [existingReport, id]);


    // Step Definitions
    const steps = [
        { id: 1, title: 'Select School', component: StepSchool },
        { id: 2, title: 'Report Details', component: StepMetadata },
        { id: 3, title: 'Checklist', component: StepChecklist },
        { id: 4, title: 'Photos', component: StepPhotos },
        { id: 5, title: 'AI Summary', component: StepAISummary },
        { id: 6, title: 'Review & Submit', component: StepReview },
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
        setDraft(updates);
        setIsDirty(true);
    };

    const reportState = report;

    const handleSave = async () => {
        try {
            const method = reportState.id ? 'PUT' : 'POST';
            const endpoint = reportState.id ? `reports/${reportState.id}` : 'reports';

            const response = await apiFetch(endpoint, {
                method,
                body: { ...reportState, responses, photos }
            });

            if (response.id) {
                updateDraft({ id: response.id });
                addToast({ type: 'success', message: 'Draft saved successfully.' });
            }
        } catch (error) {
            addToast({ type: 'error', message: 'Failed to save draft.' });
        }
    };

    const handleSubmit = async () => {
        if (!reportState.id) {
            await handleSave(); // Must have ID to submit correctly
        }

        try {
            await apiFetch(`reports/${reportState.id}/submit`, {
                method: 'POST',
                body: { ...reportState, responses, photos, status: 'submitted' }
            });

            addToast({ type: 'success', message: 'Report submitted successfully!' });
            resetWizard();
            navigate('/reports');
        } catch (error) {
            addToast({ type: 'error', message: 'Failed to submit report. Please try again.' });
        }
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
                    <button
                        onClick={handleSave}
                        className="px-4 py-2 border border-blue-300 text-blue-600 rounded-md font-medium text-sm hover:bg-blue-50 transition-colors"
                        disabled={isSaving}
                    >
                        {isSaving ? 'Saving...' : 'Save Draft'}
                    </button>

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
                            onClick={handleSubmit}
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
