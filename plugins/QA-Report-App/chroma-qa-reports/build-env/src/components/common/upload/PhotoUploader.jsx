import React, { useCallback, useState } from 'react';
import { useDropzone } from 'react-dropzone';
import { Upload, X, AlertCircle, FileImage } from 'lucide-react';
import useUIStore from '@stores/useUIStore';

const compressImage = (file, maxWidth = 2048, maxHeight = 2048, quality = 0.8) => {
    return new Promise((resolve, reject) => {
        // QAR-079: Use Blob URL instead of Base64 to save memory
        const blobUrl = URL.createObjectURL(file);
        const img = new Image();
        img.src = blobUrl;

        img.onload = () => {
            const canvas = document.createElement('canvas');
            let width = img.width;
            let height = img.height;

            if (width > height) {
                if (width > maxWidth) {
                    height *= maxWidth / width;
                    width = maxWidth;
                }
            } else {
                if (height > maxHeight) {
                    width *= maxHeight / height;
                    height = maxHeight;
                }
            }

            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, width, height);

            // Clean up source blob
            URL.revokeObjectURL(blobUrl);

            canvas.toBlob((blob) => {
                if (blob) {
                    resolve(new File([blob], file.name, { type: 'image/jpeg', lastModified: Date.now() }));
                } else {
                    reject(new Error('Canvas toBlob failed'));
                }
            }, 'image/jpeg', quality);
        };

        img.onerror = (err) => {
            URL.revokeObjectURL(blobUrl);
            reject(err);
        };
    });
};

const PhotoUploader = ({ onUpload, currentPhotos = [] }) => {
    const { addToast } = useUIStore();
    const [uploading, setUploading] = useState(false);

    const onDrop = useCallback(async (acceptedFiles) => {
        if (acceptedFiles.length === 0) return;

        setUploading(true);
        try {
            // Compress and process files locally
            const processedFiles = await Promise.all(
                acceptedFiles.map(async (file) => {
                    try {
                        // Only compress if it's an image
                        if (file.type.startsWith('image/')) {
                            return await compressImage(file);
                        }
                        return file;
                    } catch (err) {
                        console.warn('Compression failed, using original', err);
                        return file;
                    }
                })
            );

            const newPhotos = processedFiles.map(file => ({
                id: `temp-${Date.now()}-${file.name}`,
                file,
                preview: URL.createObjectURL(file), // Local preview
                name: file.name,
                size: file.size,
                status: 'pending' // pending -> uploading -> completed
            }));

            // Pass to parent handler for API upload logic or local state
            await onUpload(newPhotos);

        } catch (error) {
            console.error('Upload preparation failed', error);
            addToast({ type: 'error', message: 'Failed to prepare files.' });
        } finally {
            setUploading(false);
        }
    }, [onUpload, addToast]);

    const { getRootProps, getInputProps, isDragActive } = useDropzone({
        onDrop,
        accept: { 'image/*': ['.jpeg', '.jpg', '.png'] },
        maxSize: 10485760, // 10MB
        multiple: true
    });

    return (
        <div className="w-full">
            <div
                {...getRootProps()}
                className={`
                    border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors
                    ${isDragActive ? 'border-cqa-primary bg-indigo-50' : 'border-gray-300 hover:border-cqa-primary hover:bg-gray-50'}
                `}
            >
                <input {...getInputProps()} />
                <div className="flex flex-col items-center gap-3">
                    <div className="p-3 bg-indigo-100 rounded-full text-cqa-primary">
                        <Upload size={24} />
                    </div>
                    <div>
                        <p className="text-gray-700 font-medium">
                            {isDragActive ? 'Drop photos here...' : 'Click or Drag photos to upload'}
                        </p>
                        <p className="text-sm text-gray-500 mt-1">
                            JPG, PNG up to 10MB each
                        </p>
                    </div>
                </div>
            </div>

            {/* Uploading Status Overlay usage example can be handled by parent */}
            {uploading && (
                <div className="mt-2 text-sm text-cqa-primary flex items-center gap-2">
                    <span className="spinner w-4 h-4 border-2 border-cqa-primary border-t-transparent rounded-full animate-spin"></span>
                    Processing uploads...
                </div>
            )}
        </div>
    );
};

export default PhotoUploader;
