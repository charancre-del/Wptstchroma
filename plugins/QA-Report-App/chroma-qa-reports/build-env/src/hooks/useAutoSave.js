import { useEffect, useRef, useState } from 'react';
import useUIStore from '../stores/useUIStore';
import apiFetch from '../api/client';
import { saveLocalDraft } from '../utils/db';

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
                        // Only send fields relevant to draft update
                        status: currentDraft.status || 'draft',
                        // ... other fields mapping
                        closing_notes: currentDraft.closing_notes,
                        // Don't send everything blindly, but for v1 wizard, we might need a mapper
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
                        // Force save logic (omitting if-unmodified-since)
                        // To be implemented in Wizard Container or passed down
                        alert('Overwrite logic to be implemented');
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
