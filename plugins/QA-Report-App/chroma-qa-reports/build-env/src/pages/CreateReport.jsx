import React from 'react';
import { FilePlus } from 'lucide-react';

export function CreateReport() {
    return (
        <div className="p-6 max-w-7xl mx-auto">
            <div className="mb-6">
                <h1 className="text-2xl font-bold text-gray-900">Create New Report</h1>
                <p className="text-gray-600">Start a new QA assessment for a school</p>
            </div>

            <div className="bg-white rounded-xl border border-gray-200 p-12 text-center">
                <FilePlus className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                <h3 className="text-lg font-medium text-gray-900 mb-2">Report Wizard</h3>
                <p className="text-gray-500 mb-4">The multi-step report creation wizard will be implemented in Phase 2</p>
                <p className="text-sm text-gray-400">Features: School selection, checklist, photos, AI summary</p>
            </div>
        </div>
    );
}

export default CreateReport;
