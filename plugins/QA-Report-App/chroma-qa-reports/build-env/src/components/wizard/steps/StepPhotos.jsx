import React, { useState } from 'react';
import PhotoUploader from '@components/common/upload/PhotoUploader';
import apiFetch from '@api/client';
import useUIStore from '@stores/useUIStore';
import { Trash2, Image as ImageIcon, CloudOff } from 'lucide-react';

const PhotoThumbnail = ({ photo }) => {
    const [error, setError] = useState(false);

    if (error) {
        return (
            <div className="w-full h-full flex flex-col items-center justify-center bg-gray-50 text-gray-400 p-4 text-center">
                <CloudOff size={24} className="mb-2" />
                <span className="text-[10px] leading-tight">Media Missing from Cloud</span>
            </div>
        );
    }

    return (
        <img
            src={photo.thumbnail_url || photo.preview || photo.url}
            alt={photo.filename || photo.name || 'Evidence'}
            className="w-full h-full object-cover"
            onError={() => setError(true)}
        />
    );
};

const StepPhotos = ({ draft, updateDraft }) => {
    const { addToast } = useUIStore();
    const [uploading, setUploading] = useState(false);

    // AUDIT FIX: Use draft.photos (Single Source of Truth)
    const photos = draft.photos || [];

    const handleUpload = React.useCallback(async (newFiles) => {
        setUploading(true);
        const reportId = draft.id;

        if (!reportId) {
            addToast({ type: 'error', message: 'Please save the draft before uploading photos.' });
            setUploading(false);
            return;
        }

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

            <div className={`transition-opacity ${uploading ? 'opacity-50 pointer-events-none' : ''}`}>
                <div className="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                    <PhotoUploader onUpload={handleUpload} />
                </div>
            </div>

            {/* Gallery Grid */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                {photos.map((photo) => (
                    <div key={photo.id} className="relative group aspect-square bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                        <PhotoThumbnail photo={photo} />

                        {/* Overlay Actions */}
                        <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            <button
                                onClick={() => handleDelete(photo.id)}
                                className="p-2 bg-red-600 text-white rounded-full hover:bg-red-700 transition-colors"
                                title="Delete Photo"
                            >
                                <Trash2 size={16} />
                            </button>
                        </div>
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
