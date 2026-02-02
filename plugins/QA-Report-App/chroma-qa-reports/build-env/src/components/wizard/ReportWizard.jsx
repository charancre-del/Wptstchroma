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
    useSubmitReport,
    useApproveReport,
    useRevertToDraft
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
    const editIdParam = searchParams.get('edit') || searchParams.get('id');
    const schoolParam = searchParams.get('school');
    const stateSchoolId = location.state?.school_id;

    // Detect View Mode
    const isViewMode = location.pathname.includes('/reports/') && !location.pathname.includes('/edit');

    // Selectors (Atomic to prevent unnecessary re-renders and hook fluctuations)
    const draft = useReportWizardStore(s => s.report);
    const responses = useReportWizardStore(s => s.responses);
    const photos = useReportWizardStore(s => s.photos);
    const setDraft = useReportWizardStore(s => s.updateReportData);
    const setResponses = useReportWizardStore(s => s.setResponses);
    const setPhotos = useReportWizardStore(s => s.setPhotos);
    const currentStep = useReportWizardStore(s => s.currentStep);
    const setCurrentStep = useReportWizardStore(s => s.setStep);
    const storeNextStep = useReportWizardStore(s => s.nextStep);
    const storePrevStep = useReportWizardStore(s => s.prevStep);
    const resetWizard = useReportWizardStore(s => s.reset);
    const hasHydrated = useReportWizardStore(s => s.hasHydrated);

    const { capabilities } = useAuthStore();
    const { addToast } = useUIStore();

    const [isDirty, setIsDirty] = useState(false);

    // Fetch report if ID is present (Edit Mode)
    const { data: existingReport, isLoading: reportLoading, isError } = useReport(id);

    // React Query Mutations
    const createMutation = useCreateReport();
    const updateMutation = useUpdateReport();
    const submitMutation = useSubmitReport();
    const approveMutation = useApproveReport();
    const revertMutation = useRevertToDraft();

    const isSavingManual = createMutation.isPending || updateMutation.isPending || submitMutation.isPending || approveMutation.isPending || revertMutation.isPending;

    // Auto-Save Hook (Handles DB sync & Conflict Modal)
    // CRITICAL FIX: Must be called before early returns to satisfy Rules of Hooks (Error #310)
    const { lastSaved, isSaving, saveError } = useAutoSave(draft, isDirty);

    useEffect(() => {
        if (!id && editIdParam) {
            navigate(`/edit/${editIdParam}`, { replace: true });
        }
    }, [editIdParam, id, navigate]);

    // Cleanup on unmount to prevent stale state when navigating back
    useEffect(() => {
        return () => {
            resetWizard();
        };
    }, [resetWizard]);

    // Initialize from Params or Existing Report
    useEffect(() => {
        // Debug logging to trace Edit mode data flow
        console.log('[ReportWizard] Init Effect:', { id, existingReport, isViewMode, reportLoading });

        if (existingReport && id) {
            setDraft({
                ...existingReport,
                school_id: parseInt(existingReport.school_id, 10),
            });

            // Hydrate responses and photos from server
            if (existingReport.responses) {
                setResponses(existingReport.responses);
            }
            if (existingReport.photos) {
                setPhotos(existingReport.photos);
            }

            // If explicitly in view mode or report is approved, jump to Review/Summary
            // [STATUS-MANAGEMENT] Submitted reports are now editable in Edit mode
            if (isViewMode || existingReport.status === 'approved') {
                setCurrentStep(6);
            }
        } else {
            const schoolId = schoolParam || stateSchoolId;
            if (schoolId) {
                setDraft({ school_id: parseInt(schoolId, 10) });
            }
        }
    }, [existingReport, id, isViewMode, schoolParam, stateSchoolId, setDraft, setResponses, setPhotos, setCurrentStep]);

    // Step Definitions
    const steps = [
        { id: 1, title: 'Select School', component: StepSchool },
        { id: 2, title: 'Report Details', component: StepMetadata },
        { id: 3, title: 'Checklist', component: StepChecklist },
        { id: 4, title: 'Photos', component: StepPhotos },
        { id: 5, title: 'AI Summary', component: StepAISummary },
        { id: 6, title: 'Review & Submit', component: StepReview },
    ];

    // Ensure state is correctly interpreted
    const stepNumber = parseInt(currentStep, 10) || 1;
    const safeStep = Math.min(Math.max(stepNumber, 1), steps.length);
    const currentStepDef = steps[safeStep - 1];
    const CurrentComponent = currentStepDef ? currentStepDef.component : null;

    console.log('[ReportWizard] Layout Sync:', { currentStep, stepNumber, safeStep, hasComponent: !!CurrentComponent });

    useEffect(() => {
        if (hasHydrated && currentStep !== safeStep) {
            console.log('[ReportWizard] Correcting step mismatch:', { currentStep, safeStep });
            setCurrentStep(safeStep);
        }
    }, [currentStep, safeStep, setCurrentStep, hasHydrated]);

    // Navigation Handlers
    const nextStep = () => {
        console.log('[ReportWizard] nextStep triggered', { currentStep, school_id: draft.school_id });
        // AUDIT GUARDRAIL: Strict Payload Check
        if (currentStep === 1 && (!draft.school_id || draft.school_id === 0)) {
            addToast({ type: 'error', message: 'Please select a school to continue.' });
            return;
        }

        if (currentStep < steps.length) {
            console.log('[ReportWizard] Calling storeNextStep');
            storeNextStep();
        }
    };

    const prevStep = () => {
        if (currentStep > 1) {
            storePrevStep();
        } else {
            if (confirm('Exit wizard? Unsaved changes will be lost.')) {
                navigate('/');
            }
        }
    };

    const updateDraft = React.useCallback((updates) => {
        setDraft(updates);
        setIsDirty(true);
    }, [setDraft, setIsDirty]);

    // Use current ID from hook or param
    const reportState = draft;
    const effectivelyReadOnly = isViewMode || reportState.status === 'approved';

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

    const handleApprove = async () => {
        try {
            await approveMutation.mutateAsync(id);
            addToast({ type: 'success', message: 'Report approved successfully!' });
            navigate('/reports');
        } catch (error) {
            addToast({ type: 'error', message: 'Failed to approve report.' });
        }
    };

    const handleRevert = async () => {
        if (!confirm('Are you sure you want to revert this approved report to draft? This will allow editing again.')) return;
        try {
            await revertMutation.mutateAsync(id);
            addToast({ type: 'success', message: 'Report reverted to draft status.' });
            navigate(`/edit/${id}`); // Transition to edit mode immediately
        } catch (error) {
            addToast({ type: 'error', message: 'Failed to revert report to draft.' });
        }
    };

    // LOADING RENDER (Fetch or Hydration)
    if ((id && reportLoading) || (!id && !hasHydrated)) {
        return (
            <div className="flex flex-col items-center justify-center min-h-[50vh]">
                <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-brand-ink"></div>
                <span className="mt-4 text-brand-ink/60 font-medium font-outfit">
                    {id ? 'Loading Report Details...' : 'Restoring Draft...'}
                </span>
            </div>
        );
    }

    // ERROR RENDER
    if (id && isError) {
        return (
            <div className="flex items-center justify-center min-h-[50vh]">
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

    return (
        <div className="w-full bg-brand-cream/30 backdrop-blur-sm rounded-3xl shadow-sm border border-brand-ink/10 overflow-hidden min-h-[60vh] md:min-h-[600px] flex flex-col font-outfit">
            {/* Wizard Header */}
            <div className="bg-brand-cream/50 px-8 py-6 border-b border-brand-ink/5 flex justify-between items-center">
                <h2 className="text-2xl font-serif font-bold text-brand-ink">
                    {isViewMode ? 'View Report' : (id ? 'Edit Report' : 'Create New Report')}
                </h2>
                <div className="text-sm text-brand-ink/60 font-medium">
                    Step {safeStep} of {steps.length}: <span className="text-brand-ink ml-1 font-bold">{steps[safeStep - 1]?.title}</span>
                </div>
            </div>

            {/* Progress Bar */}
            <div className="w-full bg-brand-ink/5 h-1.5">
                <div
                    className="bg-brand-secondary h-1.5 transition-all duration-300 ease-in-out"
                    style={{ width: `${(safeStep / steps.length) * 100}%` }}
                ></div>
            </div>

            {/* Step Content */}
            <div className="flex-1 p-8 overflow-y-auto custom-scrollbar">
                {CurrentComponent ? (
                    <CurrentComponent
                        draft={draft}
                        updateDraft={updateDraft}
                        nextStep={nextStep}
                        isViewMode={isViewMode}
                        readOnly={effectivelyReadOnly}
                    />
                ) : (
                    <div className="p-8 text-center text-red-500">
                        Error: Component for step {safeStep} not found.
                    </div>
                )}
            </div>

            {/* Wizard Footer */}
            <div className="px-8 py-6 border-t border-brand-ink/5 bg-brand-cream/50 flex justify-between items-center">
                <button
                    onClick={prevStep}
                    className="px-6 py-2.5 border border-brand-ink/10 rounded-2xl text-brand-ink hover:bg-brand-ink/5 font-bold text-sm transition-all"
                >
                    {safeStep === 1 ? 'Cancel' : 'Back'}
                </button>

                <div className="flex gap-3">
                    {id && (
                        <a
                            href={`#/print/${id}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="px-6 py-2.5 border border-brand-ink/10 rounded-2xl text-brand-ink hover:bg-brand-ink/5 font-bold text-sm transition-all flex items-center gap-2"
                        >
                            <Printer className="w-4 h-4 text-brand-ink/40" />
                            Print Report
                        </a>
                    )}
                    {!effectivelyReadOnly && (
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
                    )}

                    {safeStep < steps.length ? (
                        <button
                            onClick={nextStep}
                            className="px-6 py-2.5 bg-brand-ink hover:bg-brand-ink/90 text-brand-cream rounded-2xl font-bold text-sm transition-all shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled={safeStep === 1 && !draft.school_id}
                        >
                            Next Step
                        </button>
                    ) : (
                        <div className="flex gap-2">
                            {isViewMode && existingReport?.status === 'submitted' && (
                                <button
                                    onClick={() => navigate(`/edit/${id}`)}
                                    className="px-6 py-2.5 bg-brand-ink/10 hover:bg-brand-ink/20 text-brand-ink rounded-2xl font-bold text-sm transition-all flex items-center gap-2"
                                >
                                    Edit Report
                                </button>
                            )}
                            {isViewMode && existingReport?.status === 'submitted' && capabilities?.cqa_approve_reports && (
                                <button
                                    onClick={handleApprove}
                                    className="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-2xl font-bold text-sm transition-all shadow-md hover:shadow-lg flex items-center gap-2"
                                    disabled={approveMutation.isPending}
                                >
                                    {approveMutation.isPending ? 'Approving...' : 'Approve Report'}
                                </button>
                            )}
                            {isViewMode && existingReport?.status === 'approved' && capabilities?.cqa_approve_reports && (
                                <button
                                    onClick={handleRevert}
                                    className="px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-2xl font-bold text-sm transition-all shadow-md hover:shadow-lg flex items-center gap-2"
                                    disabled={revertMutation.isPending}
                                >
                                    {revertMutation.isPending ? 'Reverting...' : 'Revert to Draft'}
                                </button>
                            )}
                            <button
                                onClick={isViewMode ? () => navigate('/reports') : handleSubmit}
                                className={`px-6 py-2.5 ${isViewMode ? 'bg-brand-ink' : 'bg-chroma-green'} hover:opacity-90 text-white rounded-2xl font-bold text-sm transition-all shadow-md hover:shadow-lg`}
                            >
                                {effectivelyReadOnly ? 'Exit View' : 'Submit Report'}
                            </button>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default ReportWizard;
