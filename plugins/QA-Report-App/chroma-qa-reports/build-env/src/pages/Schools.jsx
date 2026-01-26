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
import { useSchools } from '../hooks/useQueries';
import { cn } from '../utils/helpers';
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

export function SchoolsPage() {
    const [globalFilter, setGlobalFilter] = useState('');
    const [sorting, setSorting] = useState([]);
    const [regionFilter, setRegionFilter] = useState('');

    const { data, isLoading, error } = useSchools();
    const schools = data?.data || [];

    // Get unique regions
    const regions = useMemo(() => {
        const unique = [...new Set(schools.map(s => s.region).filter(Boolean))];
        return unique.sort();
    }, [schools]);

    // Filter by region
    const filteredSchools = useMemo(() => {
        if (!regionFilter) return schools;
        return schools.filter(s => s.region === regionFilter);
    }, [schools, regionFilter]);

    const columns = useMemo(() => [
        {
            accessorKey: 'name',
            header: 'School Name',
            cell: ({ row }) => (
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                        <Building2 className="w-5 h-5 text-primary-600" />
                    </div>
                    <div>
                        <Link
                            to={`/schools/${row.original.id}`}
                            className="font-medium text-gray-900 hover:text-primary-600"
                        >
                            {row.original.name}
                        </Link>
                        {row.original.address && (
                            <p className="text-sm text-gray-500 flex items-center gap-1">
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
                <span className="px-2 py-1 bg-gray-100 text-gray-700 text-sm rounded">
                    {getValue() || '—'}
                </span>
            ),
        },
        {
            accessorKey: 'tier',
            header: 'Tier',
            cell: ({ getValue }) => {
                const tier = getValue();
                return tier ? (
                    <span className="px-2 py-1 bg-primary-100 text-primary-700 text-sm font-medium rounded">
                        Tier {tier}
                    </span>
                ) : '—';
            },
        },
        {
            accessorKey: 'reports_count',
            header: 'Reports',
            cell: ({ getValue }) => (
                <div className="flex items-center gap-1 text-gray-600">
                    <FileText className="w-4 h-4" />
                    {getValue() || 0}
                </div>
            ),
        },
        {
            id: 'actions',
            header: '',
            cell: ({ row }) => (
                <div className="flex items-center gap-2">
                    <Link
                        to={`/create?school=${row.original.id}`}
                        className="px-3 py-1.5 text-sm bg-primary-50 text-primary-700 rounded-lg hover:bg-primary-100 transition-colors"
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
                <Loader2 className="w-8 h-8 text-primary-600 animate-spin" />
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
            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Schools</h1>
                    <p className="text-gray-600">{schools.length} schools total</p>
                </div>
                <Link
                    to="/schools/new"
                    className="btn btn-primary flex items-center gap-2"
                >
                    <Plus className="w-4 h-4" />
                    Add School
                </Link>
            </div>

            {/* Filters */}
            <div className="flex flex-col sm:flex-row gap-4">
                <div className="relative flex-1">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                    <input
                        type="text"
                        placeholder="Search schools..."
                        value={globalFilter}
                        onChange={(e) => setGlobalFilter(e.target.value)}
                        className="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    />
                </div>
                <select
                    value={regionFilter}
                    onChange={(e) => setRegionFilter(e.target.value)}
                    className="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
                    <option value="">All Regions</option>
                    {regions.map(region => (
                        <option key={region} value={region}>{region}</option>
                    ))}
                </select>
            </div>

            {/* Table */}
            <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table className="w-full">
                    <thead className="bg-gray-50 border-b border-gray-200">
                        {table.getHeaderGroups().map(headerGroup => (
                            <tr key={headerGroup.id}>
                                {headerGroup.headers.map(header => (
                                    <th
                                        key={header.id}
                                        className="px-4 py-3 text-left text-sm font-medium text-gray-700"
                                    >
                                        {header.isPlaceholder ? null : (
                                            <div
                                                className={cn(
                                                    'flex items-center gap-1',
                                                    header.column.getCanSort() && 'cursor-pointer select-none'
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
                    <tbody className="divide-y divide-gray-100">
                        {table.getRowModel().rows.map(row => (
                            <tr key={row.id} className="hover:bg-gray-50">
                                {row.getVisibleCells().map(cell => (
                                    <td key={cell.id} className="px-4 py-3">
                                        {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>

                {table.getRowModel().rows.length === 0 && (
                    <div className="py-12 text-center text-gray-500">
                        <Building2 className="w-12 h-12 mx-auto mb-4 opacity-50" />
                        <p>No schools found</p>
                    </div>
                )}

                {/* Pagination */}
                <div className="px-4 py-3 border-t border-gray-200 flex items-center justify-between">
                    <div className="text-sm text-gray-600">
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
                            className="p-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <ChevronLeft className="w-4 h-4" />
                        </button>
                        <span className="text-sm text-gray-600">
                            Page {table.getState().pagination.pageIndex + 1} of {table.getPageCount()}
                        </span>
                        <button
                            onClick={() => table.nextPage()}
                            disabled={!table.getCanNextPage()}
                            className="p-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <ChevronRight className="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default SchoolsPage;
