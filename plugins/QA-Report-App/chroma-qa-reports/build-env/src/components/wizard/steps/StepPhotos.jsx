import React, { useEffect, useMemo, useRef, useState } from 'react';
import apiFetch from '@api/client';
import useUIStore from '@stores/useUIStore';
import PhotoUploader from '../../common/upload/PhotoUploader';
import PhotoThumbnail from '../../common/PhotoThumbnail';

const StepPhotos = ( { draft, updateDraft, readOnly = false, photos = [], setPhotos } ) => {
    const { addToast } = useUIStore();
    const [ uploading, setUploading ] = useState( false );
    const [ pendingPhotos, setPendingPhotos ] = useState( [] );

    // Use photos prop from parent store
    const currentPhotos = photos || [];
    const latestPhotosRef = useRef( currentPhotos );

    useEffect( () => {
        latestPhotosRef.current = currentPhotos;
    }, [ currentPhotos ] );

    const allVisiblePhotos = useMemo( () => [ ...pendingPhotos, ...currentPhotos ], [ pendingPhotos, currentPhotos ] );

    const mergePhotos = ( incomingPhotos ) => {
        const latestPhotos = latestPhotosRef.current || [];
        const merged = [ ...latestPhotos ];

        incomingPhotos.forEach( ( photo ) => {
            if ( ! merged.some( ( existing ) => String( existing.id ) === String( photo.id ) ) ) {
                merged.push( photo );
            }
        } );

        if ( setPhotos ) {
            setPhotos( merged );
        } else {
            updateDraft( { photos: merged } );
        }
    };

    const handleUpload = async ( newFiles ) => {
        setUploading( true );
        if ( ! draft || ! draft.id ) {
            console.error( '[StepPhotos] Missing draft ID', draft );
            addToast( { type: 'error', message: 'Error: Report ID missing. Please save draft first.' } );
            setUploading( false );
            return;
        }

        const reportId = draft.id;
        console.log( '[StepPhotos] Uploading to report:', reportId );
        setPendingPhotos( ( prev ) => [ ...prev, ...newFiles ] );

        let uploadedCount = 0;
        let failedCount = 0;

        try {
            for ( const fileObj of newFiles ) {
                const formData = new FormData();
                formData.append( 'photos[]', fileObj.file );

                const response = await apiFetch( `reports/${ reportId }/photos`, {
                    method: 'POST',
                    body: formData,
                } );

                if ( response.success ) {
                    if ( response.version_id || response.updated_at ) {
                        updateDraft?.( {
                            ...( response.version_id ? { version_id: response.version_id } : {} ),
                            ...( response.updated_at ? { updated_at: response.updated_at } : {} ),
                        } );
                    }

                    const uploadedPhotos = Array.isArray( response.data ) ? response.data : response.data ? [ response.data ] : [];
                    if ( uploadedPhotos.length > 0 ) {
                        mergePhotos( uploadedPhotos );
                        uploadedCount += uploadedPhotos.length;
                    }

                    if ( Array.isArray( response.errors ) && response.errors.length > 0 ) {
                        failedCount += response.errors.length;
                    }
                } else {
                    failedCount++;
                }
            }

            if ( uploadedCount > 0 ) {
                addToast( {
                    type: failedCount > 0 ? 'warning' : 'success',
                    message:
                        failedCount > 0
                            ? `${ uploadedCount } photo${ uploadedCount !== 1 ? 's' : '' } uploaded, ${ failedCount } failed.`
                            : `${ uploadedCount } photo${ uploadedCount !== 1 ? 's' : '' } uploaded.`,
                } );
            } else if ( failedCount > 0 ) {
                addToast( {
                    type: 'error',
                    message: 'Photo upload failed. Check file size, type, or image processing.',
                } );
            }
        } catch ( error ) {
            console.error( 'Photo upload failed', error );
            addToast( { type: 'error', message: error.message || 'Photo upload failed. Check file size/type.' } );
        } finally {
            newFiles.forEach( ( fileObj ) => {
                if ( fileObj.preview ) {
                    URL.revokeObjectURL( fileObj.preview );
                }
            } );
            const pendingIds = new Set( newFiles.map( ( fileObj ) => fileObj.id ) );
            setPendingPhotos( ( prev ) => prev.filter( ( photo ) => ! pendingIds.has( photo.id ) ) );
            setUploading( false );
        }
    };

    const handleDelete = async ( photoId ) => {
        if ( ! confirm( 'Delete this photo?' ) ) {
            return;
        }

        try {
            // Optimistic Update
            const updatedPhotos = ( latestPhotosRef.current || [] ).filter( ( p ) => p.id !== photoId );

            if ( setPhotos ) {
                setPhotos( updatedPhotos );
            } else {
                updateDraft( { photos: updatedPhotos } );
            }

            // API Call
            await apiFetch( `reports/${ draft.id }/photos/${ photoId }`, { method: 'DELETE' } );
        } catch ( error ) {
            console.error( 'Delete failed', error );
            addToast( { type: 'error', message: 'Failed to delete photo.' } );
        }
    };

    return (
        <div className="space-y-6">
            <h3 className="text-lg font-bold text-gray-800 flex items-center gap-2">
                Photos & Evidence
                { uploading && (
                    <span className="text-sm font-normal text-cqa-primary animate-pulse">(Uploading...)</span>
                ) }
            </h3>

            { ! readOnly && (
                <div className={ `transition-opacity ${ uploading ? 'opacity-50 pointer-events-none' : '' }` }>
                    <div className="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                        <PhotoUploader onUpload={ handleUpload } />
                    </div>
                </div>
            ) }

            { /* Gallery Grid */ }
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                { allVisiblePhotos.map( ( photo ) => (
                    <div
                        key={ photo.id }
                        className="relative rounded-lg overflow-hidden border border-gray-200 bg-white shadow-sm"
                    >
                        <div className="aspect-square bg-gray-100">
                            <PhotoThumbnail photo={ photo } onDelete={ handleDelete } readOnly={ readOnly || photo.status === 'pending' } />
                        </div>
                        <div className="px-3 py-2 border-t border-gray-100">
                            <p className="text-xs font-medium text-gray-700 truncate">
                                { photo.caption || photo.filename || photo.name || 'Evidence photo' }
                            </p>
                            { photo.status === 'pending' && (
                                <p className="text-[11px] text-cqa-primary mt-1 animate-pulse">Processing and uploading...</p>
                            ) }
                        </div>
                    </div>
                ) ) }
            </div>

            { /* Empty State */ }
            { allVisiblePhotos.length === 0 && (
                <div className="text-center py-10 text-gray-400 border-2 border-dashed border-gray-200 rounded-lg">
                    <p>No photos added yet.</p>
                </div>
            ) }
        </div>
    );
};

export default StepPhotos;
