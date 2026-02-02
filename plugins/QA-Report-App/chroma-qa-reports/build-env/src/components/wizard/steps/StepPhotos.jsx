import React, { useState } from 'react';
import apiFetch from '@api/client';
import useUIStore from '@stores/useUIStore';
import { Camera, FileImage, Trash2, Tag, MessageSquare, AlertCircle } from 'lucide-react';
import PhotoUploader from '../../common/upload/PhotoUploader';
import PhotoThumbnail from '../../common/PhotoThumbnail';

const StepPhotos = ({ draft, updateDraft, readOnly = false }) => {
    const { addToast } = useUIStore();
    const [uploading, setUploading] = useState(false);

    // AUDIT FIX: Use draft.photos (Single Source of Truth)
    const photos = draft.photos || [];

    const handleUpload = React.useCallback(async (newFiles) => {
        setUploading(true);
        if (!draft || !draft.id) {
            console.error('[StepPhotos] Missing draft ID', draft);
            addToast({ type: 'error', message: 'Error: Report ID missing. Please save draft first.' });
            setUploading(false);
            return;
        }

        const reportId = draft.id;
        console.log('[StepPhotos] Uploading to report:', reportId);

        const formData = new FormData();
        newFiles.forEach((fileObj) => {
            formData.append('photos[]', fileObj.file);
        });

        try {
            const response = await apiFetch(`reports/${reportId}/photos`, {
                method: 'POST',
                body: formData
            });

            if (response.success && response.data) {
                const newPhotos = response.data;
                // Update Global Store directly
                updateDraft({ photos: [...photos, ...newPhotos] });
                addToast({ type: 'success', message: `${newFiles.length} photos uploaded.` });
            } else {
                throw new Error('Upload failed');
            }

        } catch (error) {
            console.error('Photo upload failed', error);
            addToast({ type: 'error', message: 'Photo upload failed. Check file size/type.' });
        } finally {
            setUploading(false);
        }
    }, [draft.id, photos, updateDraft, addToast]);

    const handleDelete = React.useCallback(async (photoId) => {
        if (!confirm('Delete this photo?')) return;

        try {
            // Optimistic Update
            const updatedPhotos = photos.filter(p => p.id !== photoId);
            updateDraft({ photos: updatedPhotos });

            // API Call
            await apiFetch(`reports/${draft.id}/photos/${photoId}`, { method: 'DELETE' });

        } catch (error) {
            console.error('Delete failed', error);
            addToast({ type: 'error', message: 'Failed to delete photo.' });
        }
    }, [draft.id, photos, updateDraft, addToast]);

    return (
        <div className="space-y-6">
            <h3 className="text-lg font-bold text-gray-800 flex items-center gap-2">
                Photos & Evidence
                {uploading && <span className="text-sm font-normal text-cqa-primary animate-pulse">(Uploading...)</span>}
            </h3>

            {!readOnly && (
                <div className={`transition-opacity ${uploading ? 'opacity-50 pointer-events-none' : ''}`}>
                    <div className="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                        <PhotoUploader onUpload={handleUpload} />
                    </div>
                </div>
            )}

            {/* Gallery Grid */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                {photos.map((photo) => (
                    <div key={photo.id} className="relative group aspect-square bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                        <PhotoThumbnail
                            photo={photo}
                            onDelete={handleDelete}
                            readOnly={readOnly}
                        />
                    </div>
                ))}
            </div>

            {/* Empty State */}
            {photos.length === 0 && (
                <div className="text-center py-10 text-gray-400 border-2 border-dashed border-gray-200 rounded-lg">
                    <p>No photos added yet.</p>
                </div>
            )}
        </div>
    );
};

export default StepPhotos;
