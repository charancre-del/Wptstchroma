import React, { useState, useRef, useMemo, useEffect } from 'react';
import { MessageSquare, Camera, Loader2, Link2, Layers3 } from 'lucide-react';
import { useReportWizardStore } from '@stores/index';
import apiFetch from '@api/client';
import { compressImage } from '../../utils/image';
import PhotoThumbnail from '../common/PhotoThumbnail';
import useUIStore from '../../stores/useUIStore';

const ChecklistItem = ( { item, sectionKey, response, allResponses = {}, onChange, readOnly = false } ) => {
    const { addToast } = useUIStore();
    const fileInputRef = useRef( null );
    const [ uploading, setUploading ] = useState( false );

    // Get global store actions and data
    const draft = useReportWizardStore( ( s ) => s.report );
    const updateDraft = useReportWizardStore( ( s ) => s.updateReportData );

    // Filter photos for this specific item
    const allPhotos = useMemo( () => draft.photos || [], [ draft.photos ] );
    const itemPhotos = useMemo(
        () => allPhotos.filter( ( p ) => p.item_key === ( item.key || item.id ) ),
        [ allPhotos, item.key, item.id ]
    );

    const itemKey = item.key || item.id;
    const { label, description, weight } = item;
    const { notes = '' } = response;
    const [ currentNotes, setCurrentNotes ] = useState( notes );
    const sourceResponse = useMemo( () => {
        if ( ! item?.shared_with ) {
            return null;
        }

        return allResponses?.[ item.shared_with.section_key ]?.[ item.shared_with.item_key ] || null;
    }, [ allResponses, item ] );
    const isLinkedExact = item.entry_mode === 'shared_exact' && !! item.shared_with;
    const isLinkedRefinement = item.entry_mode === 'linked_refinement' && !! item.shared_with;
    const effectiveRating = response.rating || sourceResponse?.rating || 'na';
    const inheritedNotes = sourceResponse?.notes || '';
    const displayNotes = isLinkedExact ? response.notes || inheritedNotes : currentNotes;

    useEffect( () => {
        setCurrentNotes( notes || '' );
    }, [ notes ] );

    const handleFileSelect = async ( e ) => {
        const files = e.target.files;
        if ( ! files || files.length === 0 ) {
            return;
        }

        setUploading( true );
        try {
            const file = files[ 0 ];
            const compressedFile = await compressImage( file );

            const formData = new FormData();
            formData.append( 'photos[]', compressedFile );
            formData.append( 'section_key', sectionKey );
            formData.append( 'item_key', itemKey );
            formData.append( 'caption', `Evidence for: ${ label }` );

            const res = await apiFetch( `reports/${ draft.id }/photos`, {
                method: 'POST',
                body: formData,
            } );

            if ( res.success && ( res.data || res.photos ) ) {
                // Update global store with new photos
                const newPhotos = res.data || res.photos;
                updateDraft( { photos: [ ...allPhotos, ...newPhotos ] } );
                addToast( { type: 'success', message: 'Photo uploaded' } );
            }
        } catch ( error ) {
            console.error( 'Upload failed', error );
            addToast( { type: 'error', message: 'Upload failed' } );
        } finally {
            setUploading( false );
            if ( fileInputRef.current ) {
                fileInputRef.current.value = '';
            }
        }
    };

    const handleDeletePhoto = async ( photoId ) => {
        try {
            const updatedPhotos = allPhotos.filter( ( p ) => p.id !== photoId );
            updateDraft( { photos: updatedPhotos } );
            await apiFetch( `reports/${ draft.id }/photos/${ photoId }`, { method: 'DELETE' } );
            addToast( { type: 'success', message: 'Photo deleted' } );
        } catch ( error ) {
            console.error( 'Delete failed', error );
            addToast( { type: 'error', message: 'Failed to delete photo' } );
        }
    };

    if ( isLinkedExact ) {
        return (
            <div className="bg-slate-50 border border-dashed border-slate-300 rounded-lg p-4 shadow-sm">
                <div className="flex flex-col md:flex-row md:items-start justify-between gap-4">
                    <div className="flex-1">
                        <div className="flex flex-wrap items-center gap-2 mb-2">
                            <span className="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded uppercase tracking-wider">
                                Item { itemKey }
                            </span>
                            <span className="text-[10px] font-bold uppercase tracking-wide bg-slate-200 text-slate-700 px-2 py-0.5 rounded-full">
                                Covered by Tier 1
                            </span>
                        </div>
                        <h4 className="text-sm font-semibold text-gray-800 leading-tight mb-2">{ label }</h4>
                        <div className="flex items-start gap-2 text-xs text-gray-600 bg-white border border-slate-200 rounded-lg p-3">
                            <Link2 size={ 14 } className="mt-0.5 text-slate-400 flex-shrink-0" />
                            <div>
                                <p className="font-semibold text-gray-700">
                                    Linked to: { item.shared_with?.label || 'Tier 1 source item' }
                                </p>
                                <p className="mt-1">
                                    This Tier 2 checkpoint is automatically satisfied from the linked Tier 1 answer so
                                    the same content does not need to be entered twice.
                                </p>
                                { displayNotes && (
                                    <p className="mt-2 italic text-gray-500">&quot;{ displayNotes }&quot;</p>
                                ) }
                            </div>
                        </div>
                    </div>

                    <div className="self-start">
                        <span
                            className={ `
                                px-3 py-1.5 rounded-md text-[10px] font-bold uppercase tracking-wide
                                ${
                                    effectiveRating === 'yes'
                                        ? 'bg-green-500 text-white'
                                        : effectiveRating === 'sometimes'
                                        ? 'bg-amber-500 text-white'
                                        : effectiveRating === 'no'
                                        ? 'bg-red-500 text-white'
                                        : 'bg-gray-200 text-gray-600'
                                }
                            ` }
                        >
                            { effectiveRating === 'na' ? 'Waiting for Tier 1' : effectiveRating }
                        </span>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="bg-white border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-all duration-200">
            { /* Header: Label and Rating */ }
            <div className="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-4">
                <div className="flex-1">
                    <div className="flex items-center gap-2 mb-1">
                        <span className="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded uppercase tracking-wider">
                            Item { itemKey }
                        </span>
                        { weight > 1 && (
                            <span className="text-[10px] bg-amber-100 text-amber-700 font-bold px-1.5 py-0.5 rounded">
                                High Impact (x{ weight })
                            </span>
                        ) }
                        { isLinkedRefinement && (
                            <span className="text-[10px] font-bold uppercase tracking-wide bg-violet-100 text-violet-700 px-2 py-0.5 rounded-full">
                                Tier 2 refinement
                            </span>
                        ) }
                    </div>
                    <h4 className="text-sm font-semibold text-gray-800 leading-tight mb-1">{ label }</h4>
                    { description && <p className="text-xs text-gray-500 italic">{ description }</p> }
                    { isLinkedRefinement && (
                        <div className="mt-3 flex items-start gap-2 text-xs text-gray-600 bg-violet-50 border border-violet-100 rounded-lg p-3">
                            <Layers3 size={ 14 } className="mt-0.5 text-violet-400 flex-shrink-0" />
                            <div>
                                <p className="font-semibold text-gray-700">
                                    Linked Tier 1 baseline: { item.shared_with?.label || 'Tier 1 source item' }
                                </p>
                                <p className="mt-1">
                                    The compliance rating is inherited from the linked Tier 1 checkpoint. Add Tier 2
                                    refinement notes only when there is deeper CQI detail to capture.
                                </p>
                                { inheritedNotes && (
                                    <p className="mt-2 italic text-gray-500">&quot;{ inheritedNotes }&quot;</p>
                                ) }
                            </div>
                        </div>
                    ) }
                </div>

                { isLinkedRefinement ? (
                    <div className="self-start">
                        <span
                            className={ `
                                px-3 py-1.5 rounded-md text-[10px] font-bold uppercase tracking-wide
                                ${
                                    effectiveRating === 'yes'
                                        ? 'bg-green-500 text-white'
                                        : effectiveRating === 'sometimes'
                                        ? 'bg-amber-500 text-white'
                                        : effectiveRating === 'no'
                                        ? 'bg-red-500 text-white'
                                        : 'bg-gray-200 text-gray-600'
                                }
                            ` }
                        >
                            { effectiveRating === 'na' ? 'Rate Tier 1 First' : `Inherited: ${ effectiveRating }` }
                        </span>
                    </div>
                ) : (
                    <div className="flex items-center bg-gray-50 p-1 rounded-lg border border-gray-100 self-start">
                        { [
                            { val: 'yes', label: 'Yes', color: 'bg-green-500' },
                            { val: 'sometimes', label: 'Sometimes', color: 'bg-amber-500' },
                            { val: 'no', label: 'No', color: 'bg-red-500' },
                            { val: 'na', label: 'N/A', color: 'bg-gray-400' },
                        ].map( ( option ) => (
                            <button
                                key={ option.val }
                                onClick={ () => ! readOnly && onChange( itemKey, { ...response, rating: option.val } ) }
                                className={ `
                                    px-3 py-1.5 rounded-md text-[10px] font-bold uppercase transition-all
                                    ${
                                        effectiveRating === option.val
                                            ? `${ option.color } text-white shadow-sm scale-110`
                                            : 'text-gray-400 hover:text-gray-600 hover:bg-white'
                                    }
                                    ${ readOnly && effectiveRating !== option.val ? 'opacity-30 cursor-default' : '' }
                                ` }
                            >
                                { option.label }
                            </button>
                        ) ) }
                    </div>
                ) }
            </div>

            { /* Additional Inputs (Notes / Photos) */ }
            <div className="flex flex-col gap-3">
                { /* Notes Toggle / Input */ }
                <div className="relative">
                    <MessageSquare size={ 16 } className="absolute top-3 left-3 text-gray-400" />
                    <textarea
                        className={ `w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm outline-none transition-shadow ${
                            readOnly || isLinkedExact
                                ? 'bg-gray-50 text-gray-600 cursor-default'
                                : 'focus:ring-1 focus:ring-cqa-primary focus:border-cqa-primary'
                        }` }
                        placeholder={
                            readOnly
                                ? ''
                                : isLinkedRefinement
                                ? 'Add Tier 2 refinement notes if deeper follow-up is needed...'
                                : 'Add notes...'
                        }
                        value={ displayNotes }
                        rows={ 5 }
                        readOnly={ readOnly || isLinkedExact }
                        onChange={ ( e ) => {
                            if ( ! readOnly && ! isLinkedExact ) {
                                setCurrentNotes( e.target.value );
                                onChange( itemKey, { ...response, notes: e.target.value } );
                            }
                        } }
                    />
                </div>

                { /* Photo Evidence Bar */ }
                <div className="flex items-center justify-between min-h-[40px] pt-1 border-t border-gray-50">
                    <div className="flex flex-wrap gap-2 items-center">
                        { itemPhotos.length > 0 ? (
                            itemPhotos.map( ( photo ) => (
                                <PhotoThumbnail
                                    key={ photo.id }
                                    photo={ photo }
                                    onDelete={ handleDeletePhoto }
                                    readOnly={ readOnly }
                                />
                            ) )
                        ) : (
                            <span className="text-[10px] text-gray-400 italic">No evidence attached</span>
                        ) }
                        { uploading && (
                            <div className="w-16 h-16 flex items-center justify-center bg-gray-50 rounded border border-dashed border-cqa-primary">
                                <Loader2 className="w-5 h-5 text-cqa-primary animate-spin" />
                            </div>
                        ) }
                    </div>

                    { ! readOnly && ! isLinkedExact && (
                        <div className="flex-shrink-0">
                            <input
                                type="file"
                                ref={ fileInputRef }
                                className="hidden"
                                accept="image/*"
                                onChange={ handleFileSelect }
                            />
                            <button
                                onClick={ () => fileInputRef.current?.click() }
                                disabled={ uploading }
                                className="text-xs text-indigo-600 font-semibold flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 rounded-full transition-colors disabled:opacity-50"
                            >
                                <Camera size={ 14 } />
                                { itemPhotos.length > 0 ? 'Add More' : 'Add Photo' }
                            </button>
                        </div>
                    ) }
                </div>
            </div>
        </div>
    );
};

export default ChecklistItem;
