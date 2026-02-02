import React, { useState, useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import apiFetch from '@api/client';
import { Search, Building, Link as LinkIcon, AlertCircle, FileText } from 'lucide-react';

const StepSchool = ({ draft, updateDraft, nextStep }) => {
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedSchool, setSelectedSchool] = useState(null);
    const [allReports, setAllReports] = useState([]);
    const [isFetchingReports, setIsFetchingReports] = useState(false);

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

    // Fetch ALL reports for the selected school (not just approved)
    useEffect(() => {
        const fetchReportsForSchool = async () => {
            if (!selectedSchool) {
                setAllReports([]);
                return;
            }

            setIsFetchingReports(true);
            try {
                // Query: All reports for this school, sorted by date desc
                const response = await apiFetch(
                    `reports?school_id=${selectedSchool.id}&per_page=20&page=1&orderby=inspection_date&order=desc`
                );

                const rawReports = Array.isArray(response) ? response : (response.data || []);

                // QAR-FIX: Filter out the current report from comparison options
                const reports = rawReports.filter(r => parseInt(r.id) !== parseInt(draft.id));
                setAllReports(reports);

                // Auto-suggest the most recent report if not already set
                if (reports.length > 0 && draft.previous_report_id !== 0 && !draft.previous_report_id) {
                    const latest = reports[0];
                    updateDraft({
                        previous_report_id: latest.id,
                        previous_report_date: latest.inspection_date
                    });
                }
            } catch (error) {
                console.error('Failed to fetch reports for school', error);
                setAllReports([]);
            } finally {
                setIsFetchingReports(false);
            }
        };

        fetchReportsForSchool();
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

    const handleLinkOptionChange = (reportId) => {
        const selectedReport = allReports.find(r => r.id === reportId);
        if (selectedReport) {
            updateDraft({
                previous_report_id: selectedReport.id,
                previous_report_date: selectedReport.inspection_date
            });
        }
    };

    const handleNoLink = () => {
        updateDraft({
            previous_report_id: 0, // SENTINEL VALUE: Explicitly No Link
            previous_report_date: null
        });
    };

    const handleContinue = () => {
        console.log('[StepSchool] handleContinue clicked', { school_id: draft.school_id, hasNextStep: !!nextStep });
        if (draft.school_id && nextStep) {
            nextStep();
        } else {
            console.warn('[StepSchool] Cannot continue:', { school_id: draft.school_id, hasNextStep: !!nextStep });
        }
    };

    // Helper to get status badge
    const getStatusBadge = (status) => {
        const styles = {
            approved: 'bg-green-100 text-green-800',
            submitted: 'bg-blue-100 text-blue-800',
            draft: 'bg-yellow-100 text-yellow-800',
        };
        return styles[status] || 'bg-gray-100 text-gray-800';
    };

    return (
        <div className="space-y-6">
            <div className="bg-white p-6 rounded-lg border border-gray-100 shadow-sm">
                <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center gap-2">
                    <Building size={20} className="text-cqa-primary" />
                    Select School
                </h3>

                {/* Search Input */}
                {!readOnly && (
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
                )}

                {/* School List */}
                <div className="border border-gray-200 rounded-md max-h-60 overflow-y-auto divide-y divide-gray-100">
                    {(() => {
                        const schools = Array.isArray(schoolsData) ? schoolsData : (schoolsData?.data || []);

                        if (isLoadingSchools && !readOnly) {
                            return <div className="p-4 text-center text-gray-500">Loading schools...</div>;
                        }

                        if (readOnly && selectedSchool) {
                            return (
                                <div className="p-3 bg-indigo-50 border-l-4 border-cqa-primary flex justify-between items-center">
                                    <div>
                                        <div className="font-medium text-gray-900">{selectedSchool.name}</div>
                                        <div className="text-xs text-gray-500">{selectedSchool.region} • Tier {selectedSchool.tier !== undefined ? selectedSchool.tier : 1}</div>
                                    </div>
                                    <span className="text-cqa-primary font-bold text-sm">Selected</span>
                                </div>
                            );
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

            {/* Linking Section - Shows ALL reports */}
            {selectedSchool && (
                <div className="bg-blue-50 p-6 rounded-lg border border-blue-100 animate-fade-in">
                    <h3 className="text-md font-medium text-blue-900 mb-3 flex items-center gap-2">
                        <LinkIcon size={18} />
                        Report Linking
                    </h3>

                    {readOnly ? (
                        <div className="bg-white p-3 rounded-md border border-blue-100 flex items-center gap-2">
                            <FileText size={14} className="text-blue-600" />
                            <span className="text-sm text-blue-800">Linked to report from {draft.previous_report_date || 'N/A'}</span>
                        </div>
                    ) : isFetchingReports ? (
                        <div className="flex items-center gap-2 text-blue-700 text-sm">
                            <span className="spinner w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></span>
                            Checking for previous reports...
                        </div>
                    ) : allReports.length > 0 ? (
                        <div className="space-y-3">
                            <p className="text-sm text-blue-800">
                                Found <strong>{allReports.length}</strong> previous report(s) for <strong>{selectedSchool.name}</strong>. Select one to compare:
                            </p>

                            <div className="flex flex-col gap-2 max-h-48 overflow-y-auto">
                                {allReports.map((report) => (
                                    <label
                                        key={report.id}
                                        className={`
                                            flex items-center gap-3 p-3 bg-white rounded-md border cursor-pointer transition-colors
                                            ${draft.previous_report_id === report.id
                                                ? 'border-cqa-primary ring-2 ring-cqa-primary/20'
                                                : 'border-blue-200 hover:border-blue-400'}
                                        `}
                                    >
                                        <input
                                            type="radio"
                                            name="report_link"
                                            checked={draft.previous_report_id === report.id}
                                            onChange={() => handleLinkOptionChange(report.id)}
                                            className="text-cqa-primary focus:ring-cqa-primary"
                                        />
                                        <div className="flex-1">
                                            <div className="font-medium text-gray-900 flex items-center gap-2">
                                                <FileText size={14} />
                                                {report.inspection_date}
                                            </div>
                                            <div className="text-xs text-gray-500">
                                                {report.report_type || 'Standard'} Report
                                            </div>
                                        </div>
                                        <span className={`px-2 py-0.5 rounded text-xs font-medium capitalize ${getStatusBadge(report.status)}`}>
                                            {report.status}
                                        </span>
                                        <div className="bg-gray-100 px-2 py-1 rounded text-xs font-mono text-gray-600">
                                            ID: {report.id}
                                        </div>
                                    </label>
                                ))}

                                {/* No Link Option */}
                                <label className={`
                                    flex items-center gap-3 p-3 bg-white rounded-md border cursor-pointer transition-colors
                                    ${draft.previous_report_id === 0
                                        ? 'border-cqa-primary ring-2 ring-cqa-primary/20'
                                        : 'border-blue-200 hover:border-blue-400'}
                                `}>
                                    <input
                                        type="radio"
                                        name="report_link"
                                        checked={draft.previous_report_id === 0}
                                        onChange={handleNoLink}
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
                            <span className="text-sm">No previous reports found. This will be a fresh report.</span>
                        </div>
                    )}
                </div>
            )}

            {/* Continue Button */}
            {selectedSchool && !readOnly && (
                <div className="flex justify-end pt-4">
                    <button
                        onClick={handleContinue}
                        className="px-6 py-2.5 bg-cqa-primary text-white rounded-lg font-medium hover:bg-cqa-primary/90 transition-colors shadow-sm"
                    >
                        Continue to Next Step
                    </button>
                </div>
            )}
        </div>
    );
};

export default StepSchool;
