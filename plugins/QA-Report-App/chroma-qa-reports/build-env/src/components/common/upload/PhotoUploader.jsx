import React, { useCallback, useState } from 'react';
import { useDropzone } from 'react-dropzone';
import { Upload } from 'lucide-react';
import useUIStore from '@stores/useUIStore';

const PhotoUploader = ( { onUpload } ) => {
    const { addToast } = useUIStore();
    const [ uploading, setUploading ] = useState( false );

    const onDrop = useCallback(
        async ( acceptedFiles ) => {
            if ( acceptedFiles.length === 0 ) {
                return;
            }

            setUploading( true );
            try {
                await onUpload( acceptedFiles );
            } catch ( error ) {
                console.error( 'Upload preparation failed', error );
                addToast( { type: 'error', message: error.message || 'Failed to prepare files.' } );
            } finally {
                setUploading( false );
            }
        },
        [ onUpload, addToast ]
    );

    const { getRootProps, getInputProps, isDragActive } = useDropzone( {
        onDrop,
        accept: { 'image/*': [ '.jpeg', '.jpg', '.png' ] },
        maxSize: 10485760, // 10MB
        multiple: true,
    } );

    return (
        <div className="w-full">
            <div
                { ...getRootProps() }
                className={ `
                    border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors
                    ${
                        isDragActive
                            ? 'border-cqa-primary bg-indigo-50'
                            : 'border-gray-300 hover:border-cqa-primary hover:bg-gray-50'
                    }
                ` }
            >
                <input { ...getInputProps() } />
                <div className="flex flex-col items-center gap-3">
                    <div className="p-3 bg-indigo-100 rounded-full text-cqa-primary">
                        <Upload size={ 24 } />
                    </div>
                    <div>
                        <p className="text-gray-700 font-medium">
                            { isDragActive ? 'Drop photos here...' : 'Click or Drag photos to upload' }
                        </p>
                        <p className="text-sm text-gray-500 mt-1">JPG, PNG up to 10MB each</p>
                    </div>
                </div>
            </div>

            { /* Uploading Status Overlay usage example can be handled by parent */ }
            { uploading && (
                <div className="mt-2 text-sm text-cqa-primary flex items-center gap-2">
                    <span className="spinner w-4 h-4 border-2 border-cqa-primary border-t-transparent rounded-full animate-spin"></span>
                    Processing uploads...
                </div>
            ) }
        </div>
    );
};

export default PhotoUploader;
