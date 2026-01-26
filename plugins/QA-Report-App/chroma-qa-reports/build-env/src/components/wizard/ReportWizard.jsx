import React, { useState, useEffect } from 'react';
import { useNavigate, useParams, useSearchParams, useLocation } from 'react-router-dom';
import { useReportWizardStore } from '@stores/index';
import useAuthStore from '@stores/useAuthStore';
import useUIStore from '@stores/useUIStore';
import useAutoSave from '@hooks/useAutoSave';
import {
    useReport,
    useCreateReport,
    useUpdateReport,
    useSubmitReport
} from '@hooks/useQueries';
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
        report: draft,
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

    // React Query Mutations
    const createMutation = useCreateReport();
    const updateMutation = useUpdateReport();
    const submitMutation = useSubmitReport();

    const isSavingManual = createMutation.isPending || updateMutation.isPending || submitMutation.isPending;

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

        if (currentStep < steps.length) {
            setCurrentStep(prev => prev + 1);
        }
    };

    const prevStep = () => {
        if (currentStep > 1) {
            setCurrentStep(prev => prev - 1);
        } else {
            if (confirm('Exit wizard? Unsaved changes will be lost (v1 drafts coming soon).')) {
                navigate('/');
            }
        }
    };

    const updateDraft = (updates) => {
        setDraft(updates);
        setIsDirty(true);
    };

    const reportState = draft;

    const handleSave = async () => {
        try {
            if (reportState.id) {
                // Update
                await updateMutation.mutateAsync({
                    ...reportState,
                    responses,
                    photos,
                    updatedAt: reportState.updated_at // Pass timestamp for optimistic lock
                });
            } else {
                // Create
                const newReport = await createMutation.mutateAsync({
                    ...reportState,
                    responses,
                    photos
                });
                updateDraft({ id: newReport.id });
            }

            addToast({ type: 'success', message: 'Draft saved successfully.' });
        } catch (error) {
            console.error('Save failed', error);
            addToast({ type: 'error', message: 'Failed to save draft.' });
        }
    };

    const handleSubmit = async () => {
        // Ensure saved first if new
        let currentId = reportState.id;

        if (!currentId) {
            try {
                const newReport = await createMutation.mutateAsync({ ...reportState, responses, photos });
                currentId = newReport.id;
                updateDraft({ id: currentId });
            } catch (err) {
                addToast({ type: 'error', message: 'Failed to create report before submission.' });
                return;
            }
        } else {
            try {
                // Update latest state before submitting
                await updateMutation.mutateAsync({ ...reportState, responses, photos, updatedAt: reportState.updated_at });
            } catch (err) {
                console.warn('Pre-submit save had issues', err);
            }
        }

        try {
            await submitMutation.mutateAsync(currentId);

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
                        disabled={isSaving || isSavingManual}
                    >
                        {isSaving || isSavingManual ? 'Saving...' : 'Save Draft'}
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
