import React from 'react';
import useUIStore from '@stores/useUIStore';
import { AlertTriangle, RefreshCw, Save } from 'lucide-react';

const ConflictModal = () => {
    const { conflict, clearConflictModal } = useUIStore();

    if (!conflict) return null;

    const { updatedBy, updatedAt, onOverwrite, onReload } = conflict;

    const handleOverwrite = () => {
        onOverwrite();
        clearConflictModal();
    };

    const handleReload = () => {
        onReload();
        clearConflictModal();
    };

    return (
        <div className="fixed inset-0 bg-black/50 z-[100000] flex items-center justify-center p-4 backdrop-blur-sm animate-fade-in">
            <div className="bg-white rounded-lg shadow-xl max-w-md w-full overflow-hidden border border-red-100">
                <div className="p-6">
                    <div className="flex items-start gap-4">
                        <div className="p-3 bg-red-100 rounded-full text-red-600 flex-shrink-0">
                            <AlertTriangle size={24} />
                        </div>
                        <div>
                            <h3 className="text-lg font-bold text-gray-900">Editing Conflict Detected</h3>
                            <p className="mt-2 text-sm text-gray-600">
                                This report was modified by <strong className="text-gray-900">{updatedBy}</strong> at {new Date(updatedAt).toLocaleTimeString()}.
                            </p>
                            <p className="mt-2 text-sm text-gray-600">
                                Your changes cannot be saved automatically. How would you like to proceed?
                            </p>
                        </div>
                    </div>

                    <div className="mt-6 flex flex-col gap-3">
                        <button
                            onClick={handleReload}
                            className="w-full flex items-center justify-center gap-2 px-4 py-3 bg-white border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors font-medium text-sm"
                        >
                            <RefreshCw size={16} />
                            Discard my changes & Load Server Version
                        </button>

                        <button
                            onClick={handleOverwrite}
                            className="w-full flex items-center justify-center gap-2 px-4 py-3 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors font-medium text-sm shadow-sm"
                        >
                            <Save size={16} />
                            Overwrite Server Version (Force Save)
                        </button>
                    </div>
                </div>
                <div className="bg-gray-50 px-6 py-3 border-t border-gray-100 text-xs text-gray-500 text-center">
                    Confirmining "Overwrite" will replace the server version with your current draft.
                </div>
            </div>
        </div>
    );
};

export default ConflictModal;
