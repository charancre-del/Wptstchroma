import React, { useEffect, useMemo, useRef, useState } from 'react';
import apiFetch from '@api/client';
import useUIStore from '@stores/useUIStore';
import { compressImage } from '../../../utils/image';
import PhotoUploader from '../../common/upload/PhotoUploader';
import PhotoThumbnail from '../../common/PhotoThumbnail';

const getRestBaseUrl = () => {
    const restUrl = window?.cqaData?.restUrl || '/wp-json/cqa/v1/';
    return restUrl.replace( /\/$/, '' );
};

const MAX_PARALLEL_UPLOADS = 4;

const buildFileFingerprint = ( file ) => `${ file.name }::${ file.size }::${ file.lastModified || 0 }`;

const uploadPhotoWithProgress = ( reportId, file, caption, onProgress ) =>
    new Promise( ( resolve, reject ) => {
        const xhr = new XMLHttpRequest();
        const formData = new FormData();
        formData.append( 'photos[]', file );

        if ( caption ) {
            formData.append( 'caption', caption );
        }

        xhr.open( 'POST', `${ getRestBaseUrl() }/reports/${ reportId }/photos`, true );
        xhr.withCredentials = true;

        if ( window?.cqaData?.nonce ) {
            xhr.setRequestHeader( 'X-WP-Nonce', window.cqaData.nonce );
        }

        xhr.upload.onprogress = ( event ) => {
            if ( event.lengthComputable && typeof onProgress === 'function' ) {
                onProgress( Math.min( 100, Math.round( ( event.loaded / event.total ) * 100 ) ) );
            }
        };

        xhr.onload = () => {
            let payload = {};

            try {
                payload = xhr.responseText ? JSON.parse( xhr.responseText ) : {};
            } catch ( error ) {
                reject( new Error( 'Invalid upload response received.' ) );
                return;
            }

            if ( xhr.status >= 200 && xhr.status < 300 ) {
                resolve( payload );
                return;
            }

            reject( new Error( payload?.message || 'Photo upload failed.' ) );
        };

        xhr.onerror = () => reject( new Error( 'Photo upload failed due to a network error.' ) );
        xhr.send( formData );
    } );

const StepPhotos = ( { draft, updateDraft, readOnly = false, photos = [], setPhotos } ) => {
    const { addToast } = useUIStore();
    const [ uploading, setUploading ] = useState( false );
    const [ pendingPhotos, setPendingPhotos ] = useState( [] );
    const [ captionDrafts, setCaptionDrafts ] = useState( {} );
    const [ savingCaptions, setSavingCaptions ] = useState( {} );

    const currentPhotos = photos || [];
    const latestPhotosRef = useRef( currentPhotos );

    useEffect( () => {
        latestPhotosRef.current = currentPhotos;
    }, [ currentPhotos ] );

    useEffect( () => {
        setCaptionDrafts( ( prev ) => {
            const next = { ...prev };
            currentPhotos.forEach( ( photo ) => {
                if ( ! Object.prototype.hasOwnProperty.call( next, photo.id ) ) {
                    next[ photo.id ] = photo.caption || '';
                }
            } );
            return next;
        } );
    }, [ currentPhotos ] );

    const allVisiblePhotos = useMemo( () => [ ...pendingPhotos, ...currentPhotos ], [ pendingPhotos, currentPhotos ] );

    const replacePhotos = ( nextPhotos ) => {
        if ( setPhotos ) {
            setPhotos( nextPhotos );
            return;
        }

        updateDraft( { photos: nextPhotos } );
    };

    const mergePhotos = ( incomingPhotos ) => {
        const latestPhotos = latestPhotosRef.current || [];
        const merged = [ ...latestPhotos ];

        incomingPhotos.forEach( ( photo ) => {
            const index = merged.findIndex( ( existing ) => String( existing.id ) === String( photo.id ) );
            if ( index >= 0 ) {
                merged[ index ] = { ...merged[ index ], ...photo };
            } else {
                merged.push( photo );
            }
        } );

        replacePhotos( merged );
    };

    const patchPendingPhoto = ( pendingId, patch ) => {
        setPendingPhotos( ( prev ) =>
            prev.map( ( photo ) =>
                photo.id === pendingId
                    ? {
                        ...photo,
                        ...patch,
                    }
                    : photo
            )
        );
    };

    const removePendingPhoto = ( pendingId ) => {
        setPendingPhotos( ( prev ) => {
            const photo = prev.find( ( item ) => item.id === pendingId );
            if ( photo?.preview ) {
                URL.revokeObjectURL( photo.preview );
            }
            return prev.filter( ( item ) => item.id !== pendingId );
        } );
    };

    const updateDraftVersionMeta = ( response ) => {
        if ( response?.version_id || response?.updated_at ) {
            updateDraft?.( {
                ...( response.version_id ? { version_id: response.version_id } : {} ),
                ...( response.updated_at ? { updated_at: response.updated_at } : {} ),
            } );
        }
    };

    const handleUpload = async ( acceptedFiles ) => {
        if ( ! draft || ! draft.id ) {
            addToast( { type: 'error', message: 'Error: Report ID missing. Please save draft first.' } );
            return;
        }

        const reportId = draft.id;
        const timestamp = Date.now();
        const knownFingerprints = new Set( [
            ...pendingPhotos.map( ( photo ) => photo.uploadFingerprint ).filter( Boolean ),
            ...currentPhotos.map( ( photo ) => photo.uploadFingerprint ).filter( Boolean ),
        ] );
        const nextFingerprints = new Set();

        const uniqueFiles = acceptedFiles.filter( ( file ) => {
            const fingerprint = buildFileFingerprint( file );
            if ( knownFingerprints.has( fingerprint ) || nextFingerprints.has( fingerprint ) ) {
                return false;
            }

            nextFingerprints.add( fingerprint );
            return true;
        } );

        const skippedCount = acceptedFiles.length - uniqueFiles.length;
        if ( skippedCount > 0 ) {
            addToast( {
                type: 'warning',
                message: `${ skippedCount } duplicate photo${ skippedCount !== 1 ? 's were' : ' was' } skipped.`,
            } );
        }

        if ( uniqueFiles.length === 0 ) {
            return;
        }

        const queuedPhotos = uniqueFiles.map( ( file, index ) => ( {
            id: `temp-${ timestamp }-${ index }-${ file.name }`,
            originalFile: file,
            preview: URL.createObjectURL( file ),
            name: file.name,
            size: file.size,
            uploadFingerprint: buildFileFingerprint( file ),
            status: 'queued',
            progress: 0,
            caption: '',
        } ) );

        setPendingPhotos( ( prev ) => [ ...prev, ...queuedPhotos ] );
        setUploading( true );

        const uploadSinglePhoto = async ( queued ) => {
            let uploadFile = queued.originalFile;

            patchPendingPhoto( queued.id, {
                status: 'processing',
                progress: 5,
            } );

            try {
                if ( uploadFile.type?.startsWith( 'image/' ) ) {
                    uploadFile = await compressImage( uploadFile );
                }
            } catch ( error ) {
                console.warn( 'Compression failed, using original file.', error );
            }

            patchPendingPhoto( queued.id, {
                status: 'uploading',
                progress: 10,
            } );

            try {
                const response = await uploadPhotoWithProgress(
                    reportId,
                    uploadFile,
                    queued.caption || '',
                    ( progress ) => {
                        patchPendingPhoto( queued.id, {
                            status: 'uploading',
                            progress: Math.max( 10, progress ),
                        } );
                    }
                );

                updateDraftVersionMeta( response );

                const uploadedPhotos = Array.isArray( response.data ) ? response.data : response.data ? [ response.data ] : [];
                if ( uploadedPhotos.length > 0 ) {
                    mergePhotos(
                        uploadedPhotos.map( ( photo ) => ( {
                            ...photo,
                            uploadFingerprint: queued.uploadFingerprint,
                        } ) )
                    );
                }

                const errors = Array.isArray( response.errors ) ? response.errors.length : 0;
                removePendingPhoto( queued.id );

                return {
                    uploadedCount: uploadedPhotos.length,
                    failedCount: errors,
                };
            } catch ( error ) {
                console.error( 'Photo upload failed', error );
                patchPendingPhoto( queued.id, {
                    status: 'error',
                    progress: 0,
                    errorMessage: error.message || 'Upload failed.',
                } );

                return {
                    uploadedCount: 0,
                    failedCount: 1,
                };
            }
        };

        try {
            const queue = [ ...queuedPhotos ];
            const workerCount = Math.min( MAX_PARALLEL_UPLOADS, queue.length );
            const results = [];

            const runWorker = async () => {
                while ( queue.length > 0 ) {
                    const nextPhoto = queue.shift();
                    if ( ! nextPhoto ) {
                        return;
                    }

                    results.push( await uploadSinglePhoto( nextPhoto ) );
                }
            };

            await Promise.all( Array.from( { length: workerCount }, () => runWorker() ) );

            const uploadedCount = results.reduce( ( total, item ) => total + ( item?.uploadedCount || 0 ), 0 );
            const failedCount = results.reduce( ( total, item ) => total + ( item?.failedCount || 0 ), 0 );

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
        } finally {
            setUploading( false );
        }
    };

    const handleDelete = async ( photoId ) => {
        if ( String( photoId ).startsWith( 'temp-' ) ) {
            removePendingPhoto( photoId );
            return;
        }

        if ( ! confirm( 'Delete this photo?' ) ) {
            return;
        }

        try {
            const updatedPhotos = ( latestPhotosRef.current || [] ).filter( ( p ) => p.id !== photoId );
            replacePhotos( updatedPhotos );

            const response = await apiFetch( `reports/${ draft.id }/photos/${ photoId }`, { method: 'DELETE' } );
            updateDraftVersionMeta( response );
        } catch ( error ) {
            console.error( 'Delete failed', error );
            addToast( { type: 'error', message: 'Failed to delete photo.' } );
        }
    };

    const handleCaptionChange = ( photoId, value ) => {
        setCaptionDrafts( ( prev ) => ( {
            ...prev,
            [ photoId ]: value,
        } ) );
    };

    const handleSaveCaption = async ( photoId ) => {
        const nextCaption = captionDrafts[ photoId ] || '';
        const existingPhoto = latestPhotosRef.current.find( ( photo ) => String( photo.id ) === String( photoId ) );

        if ( ! existingPhoto || nextCaption === ( existingPhoto.caption || '' ) ) {
            return;
        }

        setSavingCaptions( ( prev ) => ( {
            ...prev,
            [ photoId ]: true,
        } ) );

        try {
            const response = await apiFetch( `photos/${ photoId }`, {
                method: 'POST',
                body: { caption: nextCaption },
            } );

            updateDraftVersionMeta( response );

            const updatedPhotos = ( latestPhotosRef.current || [] ).map( ( photo ) =>
                String( photo.id ) === String( photoId )
                    ? {
                        ...photo,
                        caption: response.caption ?? nextCaption,
                    }
                    : photo
            );

            replacePhotos( updatedPhotos );
            addToast( { type: 'success', message: 'Caption saved.' } );
        } catch ( error ) {
            console.error( 'Caption save failed', error );
            addToast( { type: 'error', message: error.message || 'Failed to save caption.' } );
        } finally {
            setSavingCaptions( ( prev ) => ( {
                ...prev,
                [ photoId ]: false,
            } ) );
        }
    };

    const renderStatusText = ( photo ) => {
        if ( photo.status === 'queued' ) {
            return 'Queued for upload...';
        }

        if ( photo.status === 'processing' ) {
            return 'Processing photo...';
        }

        if ( photo.status === 'uploading' ) {
            return `Uploading... ${ photo.progress || 0 }%`;
        }

        if ( photo.status === 'error' ) {
            return photo.errorMessage || 'Upload failed.';
        }

        return null;
    };

    return (
        <div className="space-y-6">
            <h3 className="text-lg font-bold text-gray-800 flex items-center gap-2">
                Photos & Evidence
                { uploading && (
                    <span className="text-sm font-normal text-cqa-primary animate-pulse">(Uploading photos...)</span>
                ) }
            </h3>

            { ! readOnly && (
                <div className="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                    <PhotoUploader onUpload={ handleUpload } />
                    { pendingPhotos.length > 0 && (
                        <p className="mt-3 text-sm text-gray-500">
                            { pendingPhotos.length } photo{ pendingPhotos.length !== 1 ? 's are' : ' is' } queued, processing, or uploading.
                        </p>
                    ) }
                </div>
            ) }

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                { allVisiblePhotos.map( ( photo ) => {
                    const isPending = String( photo.id ).startsWith( 'temp-' );
                    const isSavingCaption = !! savingCaptions[ photo.id ];
                    const captionValue = captionDrafts[ photo.id ] ?? photo.caption ?? '';
                    const statusText = renderStatusText( photo );

                    return (
                        <div
                            key={ photo.id }
                            className="relative rounded-lg overflow-hidden border border-gray-200 bg-white shadow-sm"
                        >
                            <div className="aspect-square bg-gray-100 relative">
                                <PhotoThumbnail
                                    photo={ photo }
                                    onDelete={ handleDelete }
                                    readOnly={ readOnly }
                                />

                                { isPending && statusText && (
                                    <div className="absolute inset-x-0 bottom-0 bg-black/65 text-white px-3 py-2">
                                        <p className="text-xs font-medium">{ statusText }</p>
                                        { photo.status === 'uploading' && (
                                            <div className="mt-2 h-1.5 rounded-full bg-white/25 overflow-hidden">
                                                <div
                                                    className="h-full bg-white transition-all"
                                                    style={ { width: `${ Math.max( photo.progress || 0, 6 ) }%` } }
                                                />
                                            </div>
                                        ) }
                                    </div>
                                ) }
                            </div>

                            <div className="px-3 py-3 border-t border-gray-100 space-y-2">
                                <p className="text-xs font-medium text-gray-700 truncate">
                                    { photo.filename || photo.name || 'Evidence photo' }
                                </p>

                                { ! readOnly && ! isPending && (
                                    <div className="space-y-2">
                                        <input
                                            type="text"
                                            value={ captionValue }
                                            onChange={ ( event ) => handleCaptionChange( photo.id, event.target.value ) }
                                            placeholder="Add caption"
                                            className="w-full rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-cqa-primary focus:outline-none focus:ring-1 focus:ring-cqa-primary"
                                        />
                                        <button
                                            type="button"
                                            onClick={ () => handleSaveCaption( photo.id ) }
                                            disabled={ isSavingCaption || captionValue === ( photo.caption || '' ) }
                                            className="w-full rounded-md border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed hover:border-cqa-primary hover:text-cqa-primary transition-colors"
                                        >
                                            { isSavingCaption ? 'Saving caption...' : 'Save Caption' }
                                        </button>
                                        <button
                                            type="button"
                                            onClick={ () => handleDelete( photo.id ) }
                                            className="w-full rounded-md border border-red-200 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors"
                                        >
                                            Delete Photo
                                        </button>
                                    </div>
                                ) }

                                { isPending && statusText && (
                                    <p
                                        className={ `text-[11px] ${
                                            photo.status === 'error' ? 'text-red-600' : 'text-cqa-primary'
                                        }` }
                                    >
                                        { statusText }
                                    </p>
                                ) }
                            </div>
                        </div>
                    );
                } ) }
            </div>

            { allVisiblePhotos.length === 0 && (
                <div className="text-center py-10 text-gray-400 border-2 border-dashed border-gray-200 rounded-lg">
                    <p>No photos added yet.</p>
                </div>
            ) }
        </div>
    );
};

export default StepPhotos;
