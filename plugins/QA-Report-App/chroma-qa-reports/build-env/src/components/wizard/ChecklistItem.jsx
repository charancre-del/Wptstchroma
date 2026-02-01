import React, { useState, useRef, useMemo } from 'react';
import { MessageSquare, Camera, Loader2 } from 'lucide-react';
import { useReportWizardStore } from '@stores/index';
import apiFetch from '@api/client';
import { compressImage } from '../../utils/image';
import PhotoThumbnail from '../common/PhotoThumbnail';
import useUIStore from '../../stores/useUIStore';

const ChecklistItem = ({ item, sectionKey, response, onChange, readOnly = false }) => {
    const { addToast } = useUIStore();
    const fileInputRef = useRef(null);
    const [uploading, setUploading] = useState(false);

    // Get global store actions and data
    const draft = useReportWizardStore(s => s.report);
    const updateDraft = useReportWizardStore(s => s.updateReportData);

    // Filter photos for this specific item
    const allPhotos = draft.photos || [];
    const itemPhotos = useMemo(() =>
        allPhotos.filter(p => p.item_key === (item.key || item.id)),
        [allPhotos, item.key, item.id]
    );

    const { id: itemKey, label, description, weight } = item;
    const { rating = 'na', notes = '' } = response;
    const [currentNotes, setCurrentNotes] = useState(notes);

    const handleFileSelect = async (e) => {
        const files = e.target.files;
        if (!files || files.length === 0) return;

        setUploading(true);
        try {
            const file = files[0];
            const compressedFile = await compressImage(file);

            const formData = new FormData();
            formData.append('photos[]', compressedFile);
            formData.append('section_key', sectionKey);
            formData.append('item_key', itemKey);
            formData.append('caption', `Evidence for: ${label}`);

            const res = await apiFetch(`reports/${draft.id}/photos`, {
                method: 'POST',
                body: formData
            });

            if (res.success && res.photos) {
                // Update global store with new photos
                updateDraft({ photos: [...allPhotos, ...res.photos] });
                addToast({ type: 'success', message: 'Photo uploaded' });
            }
        } catch (error) {
            console.error('Upload failed', error);
            addToast({ type: 'error', message: 'Upload failed' });
        } finally {
            setUploading(false);
            if (fileInputRef.current) fileInputRef.current.value = '';
        }
    };

    const handleDeletePhoto = async (photoId) => {
        try {
            const updatedPhotos = allPhotos.filter(p => p.id !== photoId);
            updateDraft({ photos: updatedPhotos });
            await apiFetch(`reports/${draft.id}/photos/${photoId}`, { method: 'DELETE' });
            addToast({ type: 'success', message: 'Photo deleted' });
        } catch (error) {
            console.error('Delete failed', error);
            addToast({ type: 'error', message: 'Failed to delete photo' });
        }
    };

    return (
        <div className="bg-white border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-all duration-200">
            {/* Header: Label and Rating */}
            <div className="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-4">
                <div className="flex-1">
                    <div className="flex items-center gap-2 mb-1">
                        <span className="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded uppercase tracking-wider">
                            Item {itemKey}
                        </span>
                        {weight > 1 && (
                            <span className="text-[10px] bg-amber-100 text-amber-700 font-bold px-1.5 py-0.5 rounded">
                                High Impact (x{weight})
                            </span>
                        )}
                    </div>
                    <h4 className="text-sm font-semibold text-gray-800 leading-tight mb-1">{label}</h4>
                    {description && <p className="text-xs text-gray-500 italic">{description}</p>}
                </div>

                <div className="flex items-center bg-gray-50 p-1 rounded-lg border border-gray-100 self-start">
                    {[
                        { val: 'yes', label: 'Yes', color: 'bg-green-500' },
                        { val: 'sometimes', label: 'Sometimes', color: 'bg-amber-500' },
                        { val: 'no', label: 'No', color: 'bg-red-500' },
                        { val: 'na', label: 'N/A', color: 'bg-gray-400' }
                    ].map((option) => (
                        <button
                            key={option.val}
                            onClick={() => !readOnly && onChange(itemKey, { ...response, rating: option.val })}
                            className={`
                                px-3 py-1.5 rounded-md text-[10px] font-bold uppercase transition-all
                                ${rating === option.val
                                    ? `${option.color} text-white shadow-sm scale-110`
                                    : 'text-gray-400 hover:text-gray-600 hover:bg-white'
                                }
                                ${readOnly && rating !== option.val ? 'opacity-30 cursor-default' : ''}
                            `}
                        >
                            {option.label}
                        </button>
                    ))}
                </div>
            </div>

            {/* Additional Inputs (Notes / Photos) */}
            <div className="flex flex-col gap-3">
                {/* Notes Toggle / Input */}
                <div className="relative">
                    <MessageSquare size={16} className="absolute top-3 left-3 text-gray-400" />
                    <textarea
                        className={`w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm outline-none transition-shadow ${readOnly ? 'bg-gray-50 text-gray-600 cursor-default' : 'focus:ring-1 focus:ring-cqa-primary focus:border-cqa-primary'}`}
                        placeholder={readOnly ? "" : "Add notes..."}
                        value={currentNotes}
                        rows={5}
                        readOnly={readOnly}
                        onChange={(e) => {
                            if (!readOnly) {
                                setCurrentNotes(e.target.value);
                                onChange(itemKey, { ...response, notes: e.target.value });
                            }
                        }}
                    />
                </div>

                {/* Photo Evidence Bar */}
                <div className="flex items-center justify-between min-h-[40px] pt-1 border-t border-gray-50">
                    <div className="flex flex-wrap gap-2 items-center">
                        {itemPhotos.length > 0 ? (
                            itemPhotos.map(photo => (
                                <PhotoThumbnail
                                    key={photo.id}
                                    photo={photo}
                                    onDelete={handleDeletePhoto}
                                    readOnly={readOnly}
                                />
                            ))
                        ) : (
                            <span className="text-[10px] text-gray-400 italic">No evidence attached</span>
                        )}
                        {uploading && (
                            <div className="w-16 h-16 flex items-center justify-center bg-gray-50 rounded border border-dashed border-cqa-primary">
                                <Loader2 className="w-5 h-5 text-cqa-primary animate-spin" />
                            </div>
                        )}
                    </div>

                    {!readOnly && (
                        <div className="flex-shrink-0">
                            <input
                                type="file"
                                ref={fileInputRef}
                                className="hidden"
                                accept="image/*"
                                onChange={handleFileSelect}
                            />
                            <button
                                onClick={() => fileInputRef.current?.click()}
                                disabled={uploading}
                                className="text-xs text-indigo-600 font-semibold flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 rounded-full transition-colors disabled:opacity-50"
                            >
                                <Camera size={14} />
                                {itemPhotos.length > 0 ? 'Add More' : 'Add Photo'}
                            </button>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default ChecklistItem;
