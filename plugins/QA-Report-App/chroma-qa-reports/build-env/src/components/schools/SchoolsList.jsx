import React, { useState, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import {
    useReactTable,
    getCoreRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    flexRender,
} from '@tanstack/react-table';
import { Link } from 'react-router-dom';
import apiFetch from '@api/client';
import { Search, Building, ArrowUpDown, ChevronLeft, ChevronRight, RefreshCw, FileText } from 'lucide-react';

const SchoolsList = () => {
    // State
    const [pagination, setPagination] = useState({ pageIndex: 0, pageSize: 10 });
    const [sorting, setSorting] = useState([{ id: 'name', desc: false }]);
    const [globalFilter, setGlobalFilter] = useState('');

    // Fetch Data
    const { data, isLoading, isError, refetch } = useQuery({
        queryKey: ['schools', pagination.pageIndex, pagination.pageSize, sorting, globalFilter],
        queryFn: async () => {
            const params = new URLSearchParams({
                page: pagination.pageIndex + 1,
                per_page: pagination.pageSize,
                search: globalFilter,
                orderby: sorting[0]?.id || 'name',
                order: sorting[0]?.desc ? 'desc' : 'asc',
            });
            const response = await apiFetch(`schools?${params.toString()}`);
            return response;
        },
        keepPreviousData: true,
    });

    // Columns
    const columns = useMemo(() => [
        {
            accessorKey: 'name',
            header: ({ column }) => (
                <button
                    className="flex items-center gap-1 font-bold text-gray-700 hover:text-cqa-primary"
                    onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}
                >
                    School Name
                    <ArrowUpDown size={14} />
                </button>
            ),
            cell: info => <span className="font-medium text-gray-900">{info.getValue()}</span>
        },
        {
            accessorKey: 'region',
            header: 'Region',
            cell: info => <span className="text-gray-600">{info.getValue()}</span>
        },
        {
            accessorKey: 'tier',
            header: 'Tier',
            cell: info => {
                const tier = info.getValue() || 0;
                const colors = {
                    0: 'bg-gray-100 text-gray-800',
                    1: 'bg-blue-100 text-blue-800',
                    2: 'bg-green-100 text-green-800',
                    3: 'bg-orange-100 text-orange-800',
                    4: 'bg-red-100 text-red-800'
                };
                return (
                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold ${colors[tier] || colors[0]}`}>
                        Tier {tier}
                    </span>
                );
            }
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex items-center gap-2">
                    <Link
                        to={`/reports?school_id=${row.original.id}`}
                        className="text-indigo-600 hover:text-indigo-900 text-sm font-medium flex items-center gap-1"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <FileText size={14} /> History
                    </Link>
                    <span className="text-gray-300">|</span>
                    <Link
                        to={`/create?school=${row.original.id}`}
                        className="text-cqa-primary hover:text-indigo-800 text-sm font-medium"
                    >
                        New Report
                    </Link>
                </div>
            )
        }
    ], []);

    // Table Instance
    const table = useReactTable({
        data: data?.data || [],
        columns,
        pageCount: data?.meta?.total_pages || -1,
        state: {
            pagination,
            sorting,
        },
        onPaginationChange: setPagination,
        onSortingChange: setSorting,
        manualPagination: true,
        manualSorting: true,
        getCoreRowModel: getCoreRowModel(),
        // Client-side filtering simulation not needed since we fetch filtered data
    });

    return (
        <div className="space-y-6 animate-fade-in">
            {/* Header */}
            <div className="flex justify-between items-center">
                <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <Building className="text-cqa-primary" /> Schools
                </h1>
                <Link to="/create" className="bg-cqa-primary text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition font-medium">
                    Create Report
                </Link>
            </div>

            {/* Toolbar */}
            <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-200 flex justify-between gap-4">
                <div className="relative flex-1 max-w-md">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
                    <input
                        type="text"
                        placeholder="Search schools..."
                        className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-cqa-primary focus:border-cqa-primary"
                        value={globalFilter}
                        onChange={(e) => {
                            setGlobalFilter(e.target.value);
                            setPagination(prev => ({ ...prev, pageIndex: 0 })); // Reset page
                        }}
                    />
                </div>
                <button
                    onClick={() => refetch()}
                    className="p-2 text-gray-500 hover:text-cqa-primary transition"
                    title="Refresh List"
                >
                    <RefreshCw size={20} className={isLoading ? 'animate-spin' : ''} />
                </button>
            </div>

            {/* Table */}
            <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            {table.getHeaderGroups().map(headerGroup => (
                                <tr key={headerGroup.id}>
                                    {headerGroup.headers.map(header => (
                                        <th key={header.id} className="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                            {flexRender(header.column.columnDef.header, header.getContext())}
                                        </th>
                                    ))}
                                </tr>
                            ))}
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {isLoading ? (
                                <tr>
                                    <td colSpan={columns.length} className="px-6 py-12 text-center text-gray-500">
                                        Loading schools...
                                    </td>
                                </tr>
                            ) : isError ? (
                                <tr>
                                    <td colSpan={columns.length} className="px-6 py-12 text-center text-red-500">
                                        Error loading schools.
                                    </td>
                                </tr>
                            ) : table.getRowModel().rows.length === 0 ? (
                                <tr>
                                    <td colSpan={columns.length} className="px-6 py-12 text-center text-gray-500">
                                        No schools found matching your search.
                                    </td>
                                </tr>
                            ) : (
                                table.getRowModel().rows.map(row => (
                                    <tr key={row.id} className="hover:bg-gray-50 transition-colors">
                                        {row.getVisibleCells().map(cell => (
                                            <td key={cell.id} className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                            </td>
                                        ))}
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                <div className="bg-gray-50 px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                    <span className="text-sm text-gray-700">
                        Page <span className="font-medium">{table.getState().pagination.pageIndex + 1}</span> of{' '}
                        <span className="font-medium">{table.getPageCount() > 0 ? table.getPageCount() : 1}</span>
                    </span>
                    <div className="flex gap-2">
                        <button
                            onClick={() => table.previousPage()}
                            disabled={!table.getCanPreviousPage()}
                            className="p-2 border border-gray-300 rounded-md bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <ChevronLeft size={16} />
                        </button>
                        <button
                            onClick={() => table.nextPage()}
                            disabled={!table.getCanNextPage()}
                            className="p-2 border border-gray-300 rounded-md bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <ChevronRight size={16} />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default SchoolsList;
