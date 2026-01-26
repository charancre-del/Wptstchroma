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
    const location = useLocation();
    const [searchParams] = useSearchParams();

    // Detect View Mode
    const isViewMode = location.pathname.includes('/reports/') && !location.pathname.includes('/edit');

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
    const { data: existingReport, isLoading: reportLoading, isError } = useReport(id);

    // React Query Mutations
    const createMutation = useCreateReport();
    const updateMutation = useUpdateReport();
    const submitMutation = useSubmitReport();

    const isSavingManual = createMutation.isPending || updateMutation.isPending || submitMutation.isPending;

    // Auto-Save Hook (Handles DB sync & Conflict Modal)
    // CRITICAL FIX: Must be called before early returns to satisfy Rules of Hooks (Error #310)
    const { lastSaved, isSaving, saveError } = useAutoSave(draft, isDirty);

    // Initialize from Params or Existing Report
    useEffect(() => {
        if (existingReport && id) {
            setDraft({
                ...existingReport,
                school_id: parseInt(existingReport.school_id),
            });

            // If viewing a completed report or explicitly in view mode, jump to Review/Summary
            if (isViewMode || ['submitted', 'approved'].includes(existingReport.status)) {
                setCurrentStep(6);
            }
        } else {
            const schoolId = searchParams.get('school');
            if (schoolId) {
                setDraft({ school_id: parseInt(schoolId) });
            }
        }
    }, [existingReport, id, isViewMode]);

    // LOADING STATE: Prevent showing "Create New Report" while fetching "Edit" data
    if (id && reportLoading) {
        return (
            <div className="flex flex-col items-center justify-center h-96">
                <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-brand-ink"></div>
                <span className="mt-4 text-brand-ink/60 font-medium font-outfit">Loading Report Details...</span>
            </div>
        );
    }

    if (id && isError) {
        return (
            <div className="flex items-center justify-center h-96">
                <div className="text-center p-8 bg-chroma-red/5 rounded-3xl border border-chroma-red/20 max-w-lg">
                    <h3 className="text-chroma-red font-bold text-xl mb-2 font-serif">Error Loading Report</h3>
                    <p className="text-brand-ink/70 mb-6 font-outfit">The requested report could not be found or you do not have permission to view it.</p>
                    <button
                        onClick={() => navigate('/reports')}
                        className="px-6 py-2.5 bg-white border border-chroma-red/30 text-chroma-red rounded-2xl hover:bg-chroma-red/10 font-bold transition-all shadow-sm"
                    >
                        Return to Reports
                    </button>
                </div>
            </div>
        );
    }


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

    return (
        <div className="max-w-4xl mx-auto bg-brand-cream/30 backdrop-blur-sm rounded-3xl shadow-sm border border-brand-ink/10 overflow-hidden min-h-[600px] flex flex-col font-outfit">
            {/* Wizard Header */}
            <div className="bg-brand-cream/50 px-8 py-6 border-b border-brand-ink/5 flex justify-between items-center">
                <h2 className="text-2xl font-serif font-bold text-brand-ink">
                    {isViewMode ? 'View Report' : (id ? 'Edit Report' : 'Create New Report')}
                </h2>
                <div className="text-sm text-brand-ink/60 font-medium">
                    Step {currentStep} of {steps.length}: <span className="text-brand-ink ml-1 font-bold">{steps[currentStep - 1].title}</span>
                </div>
            </div>

            {/* Progress Bar */}
            <div className="w-full bg-brand-ink/5 h-1.5">
                <div
                    className="bg-brand-secondary h-1.5 transition-all duration-300 ease-in-out"
                    style={{ width: `${(currentStep / steps.length) * 100}%` }}
                ></div>
            </div>

            {/* Step Content */}
            <div className="flex-1 p-8 overflow-y-auto custom-scrollbar">
                <CurrentComponent
                    draft={draft}
                    updateDraft={updateDraft}
                    nextStep={nextStep}
                />
            </div>

            {/* Wizard Footer */}
            <div className="px-8 py-6 border-t border-brand-ink/5 bg-brand-cream/50 flex justify-between items-center">
                <button
                    onClick={prevStep}
                    className="px-6 py-2.5 border border-brand-ink/10 rounded-2xl text-brand-ink hover:bg-brand-ink/5 font-bold text-sm transition-all"
                >
                    {currentStep === 1 ? 'Cancel' : 'Back'}
                </button>

                <div className="flex gap-3">
                    <button
                        onClick={handleSave}
                        className="px-6 py-2.5 border border-chroma-blue/30 text-chroma-blue rounded-2xl font-bold text-sm hover:bg-chroma-blue/10 transition-all flex items-center gap-2"
                        disabled={isSaving || isSavingManual}
                    >
                        {isSaving || isSavingManual ? (
                            <>Saving...</>
                        ) : (
                            <>Save Draft</>
                        )}
                    </button>

                    {currentStep < steps.length ? (
                        <button
                            onClick={nextStep}
                            className="px-6 py-2.5 bg-brand-ink hover:bg-brand-ink/90 text-brand-cream rounded-2xl font-bold text-sm transition-all shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled={currentStep === 1 && !draft.school_id}
                        >
                            Next Step
                        </button>
                    ) : (
                        <button
                            onClick={handleSubmit}
                            className="px-6 py-2.5 bg-chroma-green hover:bg-chroma-green/90 text-white rounded-2xl font-bold text-sm transition-all shadow-md hover:shadow-lg"
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
