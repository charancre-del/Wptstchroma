import React, { useState, useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import apiFetch from '@api/client';
import { Search, Building, Link as LinkIcon, AlertCircle, FileText } from 'lucide-react';

const StepSchool = ({ draft, updateDraft, nextStep }) => {
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedSchool, setSelectedSchool] = useState(null);
    const [latestReport, setLatestReport] = useState(null);
    const [isFetchingLatest, setIsFetchingLatest] = useState(false);

    // Fetch Schools (Paginated/Search)
    const { data: schoolsData, isLoading: isLoadingSchools } = useQuery({
        queryKey: ['schools', searchTerm],
        queryFn: () => apiFetch(`schools?per_page=100&search=${searchTerm}`),
        keepPreviousData: true,
    });

    // Auto-select school if in draft
    useEffect(() => {
        const schools = Array.isArray(schoolsData) ? schoolsData : (schoolsData?.data || []);
        if (draft.school_id && schools.length > 0) {
            const found = schools.find(s => s.id === draft.school_id);
            if (found) setSelectedSchool(found);
        }
    }, [draft.school_id, schoolsData]);

    // AUDIT FINDING #2: Explicitly fetch latest approved report
    // Triggered immediately when a school is selected
    useEffect(() => {
        const fetchLatestReport = async () => {
            if (!selectedSchool) {
                setLatestReport(null);
                return;
            }

            setIsFetchingLatest(true);
            try {
                // Query: school_id=X, status=approved, limit=1, desc sort
                const response = await apiFetch(
                    `reports?school_id=${selectedSchool.id}&status=approved&per_page=1&page=1&orderby=inspection_date&order=desc`
                );

                // Check if response is array or data envelope
                const reports = Array.isArray(response) ? response : (response.data || []);

                if (reports.length > 0) {
                    const latest = reports[0];
                    setLatestReport(latest);

                    // Auto-suggest link if not already set (Default Behavior)
                    // If user manually set previous_report_id to 0 (No Link), respect it.
                    if (draft.previous_report_id !== 0 && (!draft.previous_report_id || draft.previous_report_id !== latest.id)) {
                        updateDraft({
                            previous_report_id: latest.id,
                            previous_report_date: latest.inspection_date
                        });
                    }
                } else {
                    setLatestReport(null);
                    updateDraft({ previous_report_id: 0, previous_report_date: null });
                }
            } catch (error) {
                console.error('Failed to fetch latest report', error);
                setLatestReport(null);
            } finally {
                setIsFetchingLatest(false);
            }
        };

        fetchLatestReport();
    }, [selectedSchool?.id, updateDraft, draft.previous_report_id]);

    const handleSelectSchool = (school) => {
        setSelectedSchool(school);
        updateDraft({
            school_id: school.id,
            school_name: school.name,
            // Reset link state on school change to trigger fresh fetch logic
            previous_report_id: undefined
        });
    };

    const handleLinkOptionChange = (option) => { // 'link' or 'none'
        if (option === 'link' && latestReport) {
            updateDraft({
                previous_report_id: latestReport.id,
                previous_report_date: latestReport.inspection_date
            });
        } else {
            updateDraft({
                previous_report_id: 0, // SENTINEL VALUE: Explicitly No Link
                previous_report_date: null
            });
        }
    };

    return (
        <div className="space-y-6">
            <div className="bg-white p-6 rounded-lg border border-gray-100 shadow-sm">
                <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center gap-2">
                    <Building size={20} className="text-cqa-primary" />
                    Select School
                </h3>

                {/* Search Input */}
                <div className="relative mb-4">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" size={18} />
                    <input
                        type="text"
                        placeholder="Search schools..."
                        className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-cqa-primary focus:border-cqa-primary outline-none transition-shadow"
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                    />
                </div>

                {/* School List */}
                <div className="border border-gray-200 rounded-md max-h-60 overflow-y-auto divide-y divide-gray-100">
                    {(() => {
                        const schools = Array.isArray(schoolsData) ? schoolsData : (schoolsData?.data || []);

                        if (isLoadingSchools) {
                            return <div className="p-4 text-center text-gray-500">Loading schools...</div>;
                        }

                        if (schools.length > 0) {
                            return schools.map((school) => (
                                <div
                                    key={school.id}
                                    onClick={() => handleSelectSchool(school)}
                                    className={`
                                        p-3 cursor-pointer hover:bg-gray-50 flex justify-between items-center transition-colors
                                        ${draft.school_id === school.id ? 'bg-indigo-50 border-l-4 border-cqa-primary' : ''}
                                    `}
                                >
                                    <div>
                                        <div className="font-medium text-gray-900">{school.name}</div>
                                        <div className="text-xs text-gray-500">{school.region} • Tier {school.tier !== undefined ? school.tier : 1}</div>
                                    </div>
                                    {draft.school_id === school.id && (
                                        <span className="text-cqa-primary font-bold text-sm">Selected</span>
                                    )}
                                </div>
                            ));
                        }

                        return <div className="p-4 text-center text-gray-500">No schools found.</div>;
                    })()}
                </div>
            </div>

            {/* Linking Section (Audit Finding) */}
            {selectedSchool && (
                <div className="bg-blue-50 p-6 rounded-lg border border-blue-100 animate-fade-in">
                    <h3 className="text-md font-medium text-blue-900 mb-3 flex items-center gap-2">
                        <LinkIcon size={18} />
                        Report Linking
                    </h3>

                    {isFetchingLatest ? (
                        <div className="flex items-center gap-2 text-blue-700 text-sm">
                            <span className="spinner w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></span>
                            Checking for previous reports...
                        </div>
                    ) : latestReport ? (
                        <div className="space-y-3">
                            <p className="text-sm text-blue-800">
                                We found a previous approved report for <strong>{selectedSchool.name}</strong> from <strong>{latestReport.inspection_date}</strong>.
                            </p>

                            <div className="flex flex-col gap-2">
                                <label className="flex items-center gap-3 p-3 bg-white rounded-md border border-blue-200 cursor-pointer hover:border-blue-400 transition-colors">
                                    <input
                                        type="radio"
                                        name="report_link"
                                        checked={draft.previous_report_id === latestReport.id}
                                        onChange={() => handleLinkOptionChange('link')}
                                        className="text-cqa-primary focus:ring-cqa-primary"
                                    />
                                    <div className="flex-1">
                                        <div className="font-medium text-gray-900">Compare with Previous Report</div>
                                        <div className="text-xs text-gray-500">
                                            This will show "Improved/Regressed" indicators in the PDF.
                                        </div>
                                    </div>
                                    <div className="bg-gray-100 px-2 py-1 rounded text-xs font-mono text-gray-600">ID: {latestReport.id}</div>
                                </label>

                                <label className="flex items-center gap-3 p-3 bg-white rounded-md border border-blue-200 cursor-pointer hover:border-blue-400 transition-colors">
                                    <input
                                        type="radio"
                                        name="report_link"
                                        checked={draft.previous_report_id === 0}
                                        onChange={() => handleLinkOptionChange('none')}
                                        className="text-cqa-primary focus:ring-cqa-primary"
                                    />
                                    <div className="flex-1">
                                        <div className="font-medium text-gray-900">Do Not Link (Fresh Start)</div>
                                        <div className="text-xs text-gray-500">
                                            No comparison data will be generated.
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    ) : (
                        <div className="flex items-center gap-2 text-blue-800 bg-white p-3 rounded-md border border-blue-100">
                            <AlertCircle size={18} className="text-blue-500" />
                            <span className="text-sm">No previous approved reports found. This will be a fresh report.</span>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
};

export default StepSchool;
