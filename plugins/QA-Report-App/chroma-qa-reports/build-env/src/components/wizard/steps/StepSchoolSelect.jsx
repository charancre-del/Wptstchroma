import React, { useState, useMemo } from 'react';
import { useSchools } from '@hooks/useQueries';
import { useReportWizardStore } from '@stores';
import { cn } from '@utils/helpers';
import { Search, MapPin, Building2, Check, AlertCircle } from 'lucide-react';

export function StepSchoolSelect({ onNext }) {
    const { data: schools, isLoading, error } = useSchools();
    const { report, setReport } = useReportWizardStore();
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedRegion, setSelectedRegion] = useState('');

    // Get unique regions
    const regions = useMemo(() => {
        if (!schools) return [];
        const uniqueRegions = [...new Set(schools.map(s => s.region).filter(Boolean))];
        return uniqueRegions.sort();
    }, [schools]);

    // Filter schools
    const filteredSchools = useMemo(() => {
        if (!schools) return [];
        return schools.filter(school => {
            const matchesSearch = !searchQuery ||
                school.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
                school.address?.toLowerCase().includes(searchQuery.toLowerCase());
            const matchesRegion = !selectedRegion || school.region === selectedRegion;
            return matchesSearch && matchesRegion;
        });
    }, [schools, searchQuery, selectedRegion]);

    const handleSelect = (school) => {
        setReport({
            ...report,
            school_id: school.id,
            school_name: school.name,
            school_tier: school.tier,
        });
    };

    const handleContinue = () => {
        if (report?.school_id) {
            onNext();
        }
    };

    if (isLoading) {
        return (
            <div className="flex items-center justify-center py-12">
                <div className="w-8 h-8 border-2 border-primary-600 border-t-transparent rounded-full animate-spin" />
            </div>
        );
    }

    if (error) {
        return (
            <div className="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
                <AlertCircle className="w-12 h-12 text-red-500 mx-auto mb-4" />
                <p className="text-red-700">Failed to load schools. Please try again.</p>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-2xl font-bold text-gray-900 mb-2">Select a School</h2>
                <p className="text-gray-600">Choose the school you're conducting the QA assessment for.</p>
            </div>

            {/* Filters */}
            <div className="flex flex-col sm:flex-row gap-4">
                <div className="relative flex-1">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                    <input
                        type="text"
                        placeholder="Search schools..."
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        className="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    />
                </div>
                <select
                    value={selectedRegion}
                    onChange={(e) => setSelectedRegion(e.target.value)}
                    className="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
                    <option value="">All Regions</option>
                    {regions.map(region => (
                        <option key={region} value={region}>{region}</option>
                    ))}
                </select>
            </div>

            {/* School Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {filteredSchools.map(school => {
                    const isSelected = report?.school_id === school.id;
                    return (
                        <button
                            key={school.id}
                            onClick={() => handleSelect(school)}
                            className={cn(
                                'relative p-4 rounded-xl border-2 text-left transition-all',
                                isSelected
                                    ? 'border-primary-500 bg-primary-50 ring-2 ring-primary-200'
                                    : 'border-gray-200 bg-white hover:border-primary-300 hover:shadow-md'
                            )}
                        >
                            {isSelected && (
                                <div className="absolute top-3 right-3 w-6 h-6 bg-primary-500 rounded-full flex items-center justify-center">
                                    <Check className="w-4 h-4 text-white" />
                                </div>
                            )}
                            <div className="flex items-start gap-3">
                                <div className={cn(
                                    'w-10 h-10 rounded-lg flex items-center justify-center',
                                    isSelected ? 'bg-primary-100' : 'bg-gray-100'
                                )}>
                                    <Building2 className={cn(
                                        'w-5 h-5',
                                        isSelected ? 'text-primary-600' : 'text-gray-500'
                                    )} />
                                </div>
                                <div className="flex-1 min-w-0">
                                    <h3 className="font-semibold text-gray-900 truncate">
                                        {school.name}
                                    </h3>
                                    {school.address && (
                                        <p className="text-sm text-gray-500 flex items-center gap-1 mt-1">
                                            <MapPin className="w-3 h-3 flex-shrink-0" />
                                            <span className="truncate">{school.address}</span>
                                        </p>
                                    )}
                                    <div className="flex items-center gap-2 mt-2">
                                        {school.tier && (
                                            <span className="px-2 py-0.5 bg-primary-100 text-primary-700 text-xs font-medium rounded">
                                                Tier {school.tier}
                                            </span>
                                        )}
                                        {school.region && (
                                            <span className="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">
                                                {school.region}
                                            </span>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </button>
                    );
                })}
            </div>

            {filteredSchools.length === 0 && (
                <div className="text-center py-12 text-gray-500">
                    <Building2 className="w-12 h-12 mx-auto mb-4 opacity-50" />
                    <p>No schools found matching your criteria.</p>
                </div>
            )}

            {/* Selected School Banner */}
            {report?.school_id && (
                <div className="bg-green-50 border border-green-200 rounded-xl p-4 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <Check className="w-5 h-5 text-green-600" />
                        </div>
                        <div>
                            <p className="text-sm text-green-600">Selected School</p>
                            <p className="font-semibold text-green-900">{report.school_name}</p>
                        </div>
                    </div>
                    <button
                        onClick={handleContinue}
                        className="btn btn-primary"
                    >
                        Continue
                    </button>
                </div>
            )}
        </div>
    );
}

export default StepSchoolSelect;
