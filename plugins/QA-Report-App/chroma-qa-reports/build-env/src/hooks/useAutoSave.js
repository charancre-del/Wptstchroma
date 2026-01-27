import { useEffect, useRef, useState } from 'react';
import useUIStore from '@stores/useUIStore';
import apiFetch from '@api/client';
import { saveLocalDraft } from '@utils/db';

const AUTOSAVE_INTERVAL = 30000; // 30 seconds

const useAutoSave = (draft, isDirty) => {
    const { showConflictModal, addToast } = useUIStore();
    const [lastSaved, setLastSaved] = useState(null);
    const [isSaving, setIsSaving] = useState(false);
    const [saveError, setSaveError] = useState(null);

    // Use ref to access latest draft in interval without re-triggering
    const draftRef = useRef(draft);
    const isDirtyRef = useRef(isDirty);

    useEffect(() => {
        draftRef.current = draft;
        isDirtyRef.current = isDirty;
    }, [draft, isDirty]);

    const performSave = async () => {
        if (!isDirtyRef.current) return;

        setIsSaving(true);
        setSaveError(null);

        const currentDraft = draftRef.current;

        try {
            // 1. Always save to Local IndexedDB (Audit Finding #4: Local Backup)
            await saveLocalDraft(currentDraft);

            // 2. If it's a persisted report (has ID), try to save to Server
            if (currentDraft.id && currentDraft.id !== 'new') {
                const options = {};

                // AUDIT FINDING #3: Optimistic Locking
                // Add If-Unmodified-Since header if we have a server timestamp
                if (currentDraft.updated_at) {
                    options.ifUnmodifiedSince = currentDraft.updated_at;
                }

                const response = await apiFetch(`reports/${currentDraft.id}`, {
                    method: 'PUT',
                    body: {
                        // Send all relevant draft fields to ensure sync
                        status: currentDraft.status || 'draft',
                        school_id: currentDraft.school_id,
                        report_type: currentDraft.report_type,
                        inspection_date: currentDraft.inspection_date,
                        overall_rating: currentDraft.overall_rating,
                        closing_notes: currentDraft.closing_notes,
                        previous_report_id: currentDraft.previous_report_id,
                        responses: currentDraft.responses,
                        photos: currentDraft.photos,
                    },
                    ...options
                });

                // Update local 'updated_at' from server response to stay in sync
                if (response.data && response.data.updated_at) {
                    draftRef.current.updated_at = response.data.updated_at;
                }
            }

            setLastSaved(new Date());

        } catch (error) {
            console.error('Autosave failed:', error);

            // Handle 409 Conflict (Audit Finding #3)
            if (error.status === 409) {
                showConflictModal({
                    updatedBy: error.data?.details?.updated_by || 'Unknown',
                    updatedAt: error.data?.details?.updated_at,
                    onOverwrite: async () => {
                        // Force save without If-Unmodified-Since header
                        try {
                            const forceSaveResponse = await apiFetch(`reports/${currentDraft.id}`, {
                                method: 'PUT',
                                body: {
                                    status: currentDraft.status || 'draft',
                                    school_id: currentDraft.school_id,
                                    report_type: currentDraft.report_type,
                                    inspection_date: currentDraft.inspection_date,
                                    overall_rating: currentDraft.overall_rating,
                                    closing_notes: currentDraft.closing_notes,
                                    previous_report_id: currentDraft.previous_report_id,
                                    responses: currentDraft.responses,
                                    photos: currentDraft.photos,
                                }
                                // No ifUnmodifiedSince = force overwrite
                            });
                            // Update local timestamp from server response
                            if (forceSaveResponse.data?.updated_at) {
                                draftRef.current.updated_at = forceSaveResponse.data.updated_at;
                            }
                            showConflictModal(null); // Close modal
                            addToast({ type: 'success', message: 'Report saved (overwritten server version)' });
                            setLastSaved(new Date());
                        } catch (forceSaveError) {
                            console.error('Force save failed:', forceSaveError);
                            addToast({ type: 'error', message: 'Failed to force save. Please try again.' });
                        }
                    },
                    onReload: () => {
                        window.location.reload();
                    }
                });
            } else if (error.status === 401) {
                // Session expired handled globally by App.jsx
            } else {
                // Network or other error -> Offline Mode implied
                setSaveError('Offline: Saved locally');
            }
        } finally {
            setIsSaving(false);
        }
    };

    useEffect(() => {
        const interval = setInterval(performSave, AUTOSAVE_INTERVAL);
        return () => clearInterval(interval);
    }, []);

    // Also save on unmount/leave?
    // useEffect(() => () => performSave(), []); 

    return { lastSaved, isSaving, saveError, performSave };
};

export default useAutoSave;
