import { useEffect, useRef, useState, useCallback } from 'react';
import useUIStore from '@stores/useUIStore';
import { useReportWizardStore } from '@stores/index';
import apiFetch from '@api/client';
import { saveLocalDraft } from '@utils/db';

const AUTOSAVE_INTERVAL = 30000; // 30 seconds

const useAutoSave = ( draft, isDirty, onServerSaveSuccess = null ) => {
    const { showConflictModal, addToast } = useUIStore();
    const [ lastSaved, setLastSaved ] = useState( null );
    const [ isSaving, setIsSaving ] = useState( false );
    const [ saveError, setSaveError ] = useState( null );

    // Use ref to access latest draft in interval without re-triggering
    const draftRef = useRef( draft );
    const isDirtyRef = useRef( isDirty );

    useEffect( () => {
        draftRef.current = draft;
        isDirtyRef.current = isDirty;
    }, [ draft, isDirty ] );

    const buildAutosaveDraft = useCallback( () => {
        const currentDraft = draftRef.current;
        const { responses, photos } = useReportWizardStore.getState();

        return {
            ...currentDraft,
            responses,
            photos,
        };
    }, [] );

    const syncServerDraftMeta = useCallback( ( savedReport ) => {
        if ( ! savedReport || typeof savedReport !== 'object' ) {
            return;
        }

        const patch = {};

        if ( savedReport.updated_at ) {
            draftRef.current.updated_at = savedReport.updated_at;
            patch.updated_at = savedReport.updated_at;
        }

        if ( savedReport.version_id ) {
            draftRef.current.version_id = savedReport.version_id;
            patch.version_id = savedReport.version_id;
        }

        if ( Object.keys( patch ).length ) {
            useReportWizardStore.getState().updateReportData( patch );
        }
    }, [] );

    const performSave = useCallback( async () => {
        if ( ! isDirtyRef.current ) {
            return;
        }

        setIsSaving( true );
        setSaveError( null );

        const currentDraft = buildAutosaveDraft();

        try {
            // 1. Always save to Local IndexedDB (Audit Finding #4: Local Backup)
            await saveLocalDraft( currentDraft );

            // 2. If it's a persisted report (has ID), try to save to Server
            if ( currentDraft.id && currentDraft.id !== 'new' ) {
                const options = {};

                // AUDIT FINDING #3: Optimistic Locking
                // Add If-Unmodified-Since header if we have a server timestamp
                if ( currentDraft.updated_at ) {
                    options.ifUnmodifiedSince = currentDraft.updated_at;
                }
                if ( currentDraft.version_id ) {
                    options.headers = { 'X-CQA-Version': currentDraft.version_id };
                }

                const response = await apiFetch( `reports/${ currentDraft.id }`, {
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
                        save_mode: 'autosave',
                        responses: currentDraft.responses,
                        photos: currentDraft.photos,
                    },
                    ...options,
                } );

                syncServerDraftMeta( response );
                if ( typeof onServerSaveSuccess === 'function' ) {
                    onServerSaveSuccess( response );
                }
            }

            setLastSaved( new Date() );
        } catch ( error ) {
            console.error( 'Autosave failed:', error );

            // Handle 409 Conflict (Audit Finding #3)
            if ( error.status === 409 ) {
                const conflictData = error.data || {};
                const updatedBy = conflictData.updated_by || conflictData.details?.updated_by || 'Unknown';
                const updatedAt = conflictData.updated_at || conflictData.details?.updated_at;

                showConflictModal( {
                    updatedBy,
                    updatedAt,
                    onOverwrite: async () => {
                        // Force save without If-Unmodified-Since header
                        try {
                            const forceSaveResponse = await apiFetch( `reports/${ currentDraft.id }`, {
                                method: 'PUT',
                                body: {
                                    status: currentDraft.status || 'draft',
                                    school_id: currentDraft.school_id,
                                    report_type: currentDraft.report_type,
                                    inspection_date: currentDraft.inspection_date,
                                    overall_rating: currentDraft.overall_rating,
                                    closing_notes: currentDraft.closing_notes,
                                    previous_report_id: currentDraft.previous_report_id,
                                    save_mode: 'autosave',
                                    responses: currentDraft.responses,
                                    photos: currentDraft.photos,
                                },
                                // No ifUnmodifiedSince = force overwrite
                            } );
                            syncServerDraftMeta( forceSaveResponse );
                            if ( typeof onServerSaveSuccess === 'function' ) {
                                onServerSaveSuccess( forceSaveResponse );
                            }
                            showConflictModal( null ); // Close modal
                            addToast( { type: 'success', message: 'Report saved (overwritten server version)' } );
                            setLastSaved( new Date() );
                        } catch ( forceSaveError ) {
                            console.error( 'Force save failed:', forceSaveError );
                            addToast( { type: 'error', message: 'Failed to force save. Please try again.' } );
                        }
                    },
                    onReload: () => {
                        window.location.reload();
                    },
                } );
            } else if ( error.status === 401 ) {
                // Session expired handled globally by App.jsx
            } else {
                // Network or other error -> Offline Mode implied
                setSaveError( 'Offline: Saved locally' );
            }
        } finally {
            setIsSaving( false );
        }
    }, [ addToast, buildAutosaveDraft, onServerSaveSuccess, showConflictModal, syncServerDraftMeta ] );

    useEffect( () => {
        const interval = setInterval( performSave, AUTOSAVE_INTERVAL );
        return () => clearInterval( interval );
    }, [ performSave ] );

    // Also save on unmount/leave?
    // useEffect(() => () => performSave(), []);

    return { lastSaved, isSaving, saveError, performSave };
};

export default useAutoSave;
