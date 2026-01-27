import React, { useState, useMemo } from 'react';
import { Link } from 'react-router-dom';
import {
    useReactTable,
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    flexRender,
} from '@tanstack/react-table';
import { useSchools } from '@hooks/useQueries';
import { cn } from '@utils/helpers';
import {
    Search,
    Plus,
    Building2,
    MapPin,
    ChevronUp,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Loader2,
    FileText,
    AlertCircle,
} from 'lucide-react';

import { useSearchParams } from 'react-router-dom';

export function SchoolsPage() {
    const [searchParams] = useSearchParams();
    const initialRegion = searchParams.get('region') || '';
    const initialStatus = searchParams.get('status') || '';

    const [globalFilter, setGlobalFilter] = useState('');
    const [sorting, setSorting] = useState([]);
    const [regionFilter, setRegionFilter] = useState(initialRegion);
    const [statusFilter, setStatusFilter] = useState(initialStatus);

    const { data, isLoading, error } = useSchools();
    const schools = Array.isArray(data) ? data : (data?.data || []);

    // Get unique regions
    const regions = useMemo(() => {
        const unique = [...new Set(schools.map(s => s.region).filter(Boolean))];
        return unique.sort();
    }, [schools]);

    // Filter by region and status
    const filteredSchools = useMemo(() => {
        let result = schools;
        if (regionFilter) {
            result = result.filter(s => s.region === regionFilter);
        }
        if (statusFilter === 'overdue') {
            // Filter logic for overdue schools (assuming 'is_overdue' or calculating from last_inspection)
            const ninetyDaysAgo = new Date();
            ninetyDaysAgo.setDate(ninetyDaysAgo.getDate() - 90);
            result = result.filter(s => {
                const lastDate = s.last_inspection_date ? new Date(s.last_inspection_date) : null;
                // If never inspected, it's overdue? Or if last inspection > 90 days
                return !lastDate || lastDate < ninetyDaysAgo;
            });
        }
        return result;
    }, [schools, regionFilter, statusFilter]);

    const columns = useMemo(() => [
        {
            accessorKey: 'name',
            header: 'School Name',
            cell: ({ row }) => (
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 bg-chroma-blue/10 rounded-xl flex items-center justify-center">
                        <Building2 className="w-5 h-5 text-chroma-blue" />
                    </div>
                    <div>
                        <Link
                            to={`/reports?school_id=${row.original.id}`}
                            className="text-indigo-600 hover:text-indigo-900 font-medium"
                            onClick={(e) => e.stopPropagation()}
                        >
                            {row.original.name}
                        </Link>
                        {row.original.address && (
                            <p className="text-sm text-brand-ink/60 flex items-center gap-1">
                                <MapPin className="w-3 h-3" />
                                {row.original.address}
                            </p>
                        )}
                    </div>
                </div>
            ),
        },
        {
            accessorKey: 'region',
            header: 'Region',
            cell: ({ getValue }) => (
                <span className="px-3 py-1 bg-brand-ink/5 text-brand-ink/80 text-sm font-medium rounded-full">
                    {getValue() || '—'}
                </span>
            ),
        },
        {
            header: 'Reports',
            accessorKey: 'reports_count',
            cell: ({ row }) => (
                <Link
                    to={`/reports?school_id=${row.original.id}`}
                    className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 hover:bg-gray-200"
                    onClick={(e) => e.stopPropagation()}
                >
                    {row.original.reports_count || 0}
                </Link>
            )
        },
        {
            accessorKey: 'tier',
            header: 'Tier',
            cell: ({ getValue }) => {
                const tier = getValue() || 0;
                const colors = {
                    0: 'bg-gray-100 text-gray-800',
                    1: 'bg-blue-100 text-blue-800',
                    2: 'bg-green-100 text-green-800',
                    3: 'bg-orange-100 text-orange-800',
                    4: 'bg-red-100 text-red-800'
                };
                return (
                    <span className={`px-3 py-1 text-xs font-bold rounded-full ${colors[tier] || colors[0]}`}>
                        Tier {tier}
                    </span>
                );
            },
        },
        {
            id: 'actions',
            header: '',
            cell: ({ row }) => (
                <div className="flex items-center gap-2">
                    <Link
                        to={`/create?school=${row.original.id}`}
                        className="px-4 py-1.5 text-sm bg-brand-ink text-white rounded-xl hover:bg-brand-ink/90 transition-colors font-medium shadow-sm"
                    >
                        New Report
                    </Link>
                </div>
            ),
        },
    ], []);

    const table = useReactTable({
        data: filteredSchools,
        columns,
        state: {
            globalFilter,
            sorting,
        },
        onGlobalFilterChange: setGlobalFilter,
        onSortingChange: setSorting,
        getCoreRowModel: getCoreRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
        getSortedRowModel: getSortedRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        initialState: {
            pagination: { pageSize: 10 },
        },
    });

    if (isLoading) {
        return (
            <div className="flex items-center justify-center py-16">
                <Loader2 className="w-8 h-8 text-chroma-blue animate-spin" />
            </div>
        );
    }

    if (error) {
        return (
            <div className="bg-chroma-red/5 border border-chroma-red/20 rounded-3xl p-8 text-center">
                <AlertCircle className="w-12 h-12 text-chroma-red mx-auto mb-4" />
                <p className="text-brand-ink font-medium">Failed to load schools. Please try again.</p>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-3xl font-serif font-bold text-brand-ink">Schools</h1>
                    <p className="text-brand-ink/60 font-medium mt-1">{schools.length} schools total</p>
                </div>
                <a
                    href={window?.cqaData?.adminUrl ? `${window.cqaData.adminUrl}?page=chroma-qa-reports-schools` : '#'}
                    className="btn bg-brand-ink text-white hover:bg-brand-ink/90 rounded-xl px-6 py-2.5 flex items-center gap-2 font-medium shadow-sm transition-all hover:scale-105 active:scale-95"
                >
                    <Plus className="w-4 h-4" />
                    Manage Schools
                </a>
            </div>

            {/* Filters */}
            <div className="flex flex-col sm:flex-row gap-4">
                <div className="relative flex-1">
                    <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-brand-ink/40" />
                    <input
                        type="text"
                        placeholder="Search schools..."
                        value={globalFilter}
                        onChange={(e) => setGlobalFilter(e.target.value)}
                        className="w-full pl-12 pr-4 py-3 border border-brand-ink/10 rounded-2xl focus:ring-2 focus:ring-chroma-blue/20 focus:border-chroma-blue transition-all bg-white"
                    />
                </div>
                <select
                    value={regionFilter}
                    onChange={(e) => setRegionFilter(e.target.value)}
                    className="px-4 py-3 border border-brand-ink/10 rounded-2xl focus:ring-2 focus:ring-chroma-blue/20 focus:border-chroma-blue bg-white text-brand-ink font-medium"
                >
                    <option value="">All Regions</option>
                    {regions.map(region => (
                        <option key={region} value={region}>{region}</option>
                    ))}
                </select>
            </div>

            {/* Table */}
            <div className="bg-white/80 backdrop-blur-sm rounded-3xl border border-brand-ink/5 overflow-hidden shadow-sm">
                <table className="w-full">
                    <thead className="bg-brand-cream border-b border-brand-ink/5">
                        {table.getHeaderGroups().map(headerGroup => (
                            <tr key={headerGroup.id}>
                                {headerGroup.headers.map(header => (
                                    <th
                                        key={header.id}
                                        className="px-6 py-4 text-left text-sm font-bold text-brand-ink/60 uppercase tracking-wider"
                                    >
                                        {header.isPlaceholder ? null : (
                                            <div
                                                className={cn(
                                                    'flex items-center gap-2',
                                                    header.column.getCanSort() && 'cursor-pointer select-none hover:text-brand-ink transition-colors'
                                                )}
                                                onClick={header.column.getToggleSortingHandler()}
                                            >
                                                {flexRender(header.column.columnDef.header, header.getContext())}
                                                {header.column.getIsSorted() === 'asc' && <ChevronUp className="w-4 h-4" />}
                                                {header.column.getIsSorted() === 'desc' && <ChevronDown className="w-4 h-4" />}
                                            </div>
                                        )}
                                    </th>
                                ))}
                            </tr>
                        ))}
                    </thead>
                    <tbody className="divide-y divide-brand-ink/5">
                        {table.getRowModel().rows.map(row => (
                            <tr key={row.id} className="hover:bg-brand-cream/50 transition-colors">
                                {row.getVisibleCells().map(cell => (
                                    <td key={cell.id} className="px-6 py-4">
                                        {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>

                {table.getRowModel().rows.length === 0 && (
                    <div className="py-16 text-center text-brand-ink/40">
                        <Building2 className="w-12 h-12 mx-auto mb-4 opacity-50" />
                        <p className="font-medium">No schools found</p>
                    </div>
                )}

                {/* Pagination */}
                <div className="px-6 py-4 border-t border-brand-ink/5 flex items-center justify-between bg-brand-cream/20">
                    <div className="text-sm text-brand-ink/60 font-medium">
                        Showing {table.getState().pagination.pageIndex * table.getState().pagination.pageSize + 1} to{' '}
                        {Math.min(
                            (table.getState().pagination.pageIndex + 1) * table.getState().pagination.pageSize,
                            filteredSchools.length
                        )}{' '}
                        of {filteredSchools.length}
                    </div>
                    <div className="flex items-center gap-2">
                        <button
                            onClick={() => table.previousPage()}
                            disabled={!table.getCanPreviousPage()}
                            className="p-2 border border-brand-ink/10 rounded-xl hover:bg-brand-ink/5 text-brand-ink disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                        >
                            <ChevronLeft className="w-5 h-5" />
                        </button>
                        <span className="text-sm text-brand-ink font-bold">
                            Page {table.getState().pagination.pageIndex + 1} of {table.getPageCount()}
                        </span>
                        <button
                            onClick={() => table.nextPage()}
                            disabled={!table.getCanNextPage()}
                            className="p-2 border border-brand-ink/10 rounded-xl hover:bg-brand-ink/5 text-brand-ink disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                        >
                            <ChevronRight className="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default SchoolsPage;
