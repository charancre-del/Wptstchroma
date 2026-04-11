import React, { useState } from 'react';
import { X, AlertCircle } from 'lucide-react';

const PhotoThumbnail = ( { photo, onDelete, readOnly = false } ) => {
    const [ error, setError ] = useState( false );
    const [ loading, setLoading ] = useState( false );

    const handleDelete = async ( e ) => {
        e.stopPropagation();
        if ( readOnly || loading ) {
            return;
        }

        setLoading( true );
        try {
            await onDelete( photo.id );
        } finally {
            setLoading( false );
        }
    };

    return (
        <div className="relative group w-16 h-16 bg-gray-100 rounded border border-gray-200 overflow-hidden flex-shrink-0">
            { error ? (
                <div className="w-full h-full flex items-center justify-center text-gray-400">
                    <AlertCircle size={ 16 } />
                </div>
            ) : (
                <img
                    src={ photo.thumbnail_url || photo.preview || photo.url }
                    alt={ photo.caption || 'Evidence' }
                    className="w-full h-full object-cover transition-opacity group-hover:opacity-75"
                    onError={ () => setError( true ) }
                />
            ) }

            { ! readOnly && (
                <button
                    onClick={ handleDelete }
                    disabled={ loading }
                    className="absolute top-0 right-0 p-1 bg-red-500 text-white opacity-0 group-hover:opacity-100 transition-opacity rounded-bl hover:bg-red-600"
                >
                    <X size={ 10 } />
                </button>
            ) }

            { loading && (
                <div className="absolute inset-0 bg-white/50 flex items-center justify-center">
                    <span className="w-4 h-4 border-2 border-cqa-primary border-t-transparent rounded-full animate-spin"></span>
                </div>
            ) }
        </div>
    );
};

export default PhotoThumbnail;
