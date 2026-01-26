import React, { useState } from 'react';
import PhotoUploader from '@components/common/upload/PhotoUploader';
import apiFetch from '../../../api/client';
import useUIStore from '../../../stores/useUIStore';
import { Trash2, AlertTriangle } from 'lucide-react';

const StepPhotos = ({ draft, updateDraft, nextStep }) => {
    const { addToast } = useUIStore();
    const [photos, setPhotos] = useState(draft.photos || []);
    const [uploading, setUploading] = useState(false);

    // Initial load from draft should ideally be robust
    // For now assuming draft.photos is array of { id, url, name }

    const handleUpload = async (newFiles) => {
        setUploading(true);
        // Create FormData
        const formData = new FormData();

        // Append all files
        newFiles.forEach((fileObj) => {
            formData.append('photos[]', fileObj.file);
        });

        // Add report ID context if we had one (usually do by step 4)
        // If draft is new, we might need to create it first? 
        // Plan implies we autosave, so we should have an ID.
        // Fallback: If no ID, we must create draft first? 
        // Simplified: Assume ID exists for Step 4.
        const reportId = draft.id;

        if (!reportId) {
            addToast({ type: 'error', message: 'Report draft missing ID. Please save first.' });
            setUploading(false);
            return;
        }

        try {
            // AUDIT FINDING #1: Strict Content-Type
            // api/client.js handles NOT setting Content-Type for FormData automatically
            const response = await apiFetch(`reports/${reportId}/photos`, {
                method: 'POST',
                body: formData
            });

            if (response.success && response.data) {
                // Append new photos from server response
                const updatedPhotos = [...photos, ...response.data];
                setPhotos(updatedPhotos);
                updateDraft({ photos: updatedPhotos });
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
    };

    const handleDelete = async (photoId) => {
        if (!confirm('Delete this photo?')) return;

        try {
            // Optimistic Update
            const updatedPhotos = photos.filter(p => p.id !== photoId);
            setPhotos(updatedPhotos);
            updateDraft({ photos: updatedPhotos });

            // API Call
            await apiFetch(`reports/${draft.id}/photos/${photoId}`, { method: 'DELETE' });

        } catch (error) {
            console.error('Delete failed', error);
            addToast({ type: 'error', message: 'Failed to delete photo.' });
            // Rollback? (Optional)
        }
    };

    return (
        <div className="space-y-6">
            <h3 className="text-lg font-bold text-gray-800">Photos & Evidence</h3>

            <div className="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                <PhotoUploader onUpload={handleUpload} />
            </div>

            {/* Gallery Grid */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                {photos.map((photo) => (
                    <div key={photo.id} className="relative group aspect-square bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                        <img
                            src={photo.url || photo.preview} // Server URL or local preview 
                            alt={photo.name}
                            className="w-full h-full object-cover"
                        />

                        {/* Overlay Actions */}
                        <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            <button
                                onClick={() => handleDelete(photo.id)}
                                className="p-2 bg-red-600 text-white rounded-full hover:bg-red-700"
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
                <div className="text-center py-10 text-gray-400">
                    <p>No photos added yet.</p>
                </div>
            )}
        </div>
    );
};

export default StepPhotos;
