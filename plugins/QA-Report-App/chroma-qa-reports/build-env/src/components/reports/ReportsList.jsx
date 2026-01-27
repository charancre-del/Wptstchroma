import React, { useState, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import {
    useReactTable,
    getCoreRowModel,
    flexRender,
} from '@tanstack/react-table';
import { Link, useSearchParams } from 'react-router-dom';
import apiFetch from '@api/client';
import {
    Search, FileText, Calendar, User, ArrowUpDown,
    ChevronLeft, ChevronRight, RefreshCw, Filter, CheckCircle, Clock
} from 'lucide-react';

const ReportsList = () => {
    // URL Params for initial state
    const [searchParams] = useSearchParams();
    const initialSchoolId = searchParams.get('school_id') || '';

    // State
    const [pagination, setPagination] = useState({ pageIndex: 0, pageSize: 15 });
    const [sorting, setSorting] = useState([{ id: 'date', desc: true }]);
    const [statusFilter, setStatusFilter] = useState(''); // '' = All
    const [searchQuery, setSearchQuery] = useState(initialSchoolId); // Pre-fill if linked from Schools List

    // Fetch Data
    const { data, isLoading, isError, refetch } = useQuery({
        queryKey: ['reports', pagination.pageIndex, pagination.pageSize, sorting, statusFilter, searchQuery],
        queryFn: async () => {
            const params = new URLSearchParams({
                page: pagination.pageIndex + 1,
                per_page: pagination.pageSize,
                search: searchQuery, // Server handles searching school name or ID
                status: statusFilter,
                orderby: sorting[0]?.id || 'date',
                order: sorting[0]?.desc ? 'desc' : 'asc',
            });
            const response = await apiFetch(`reports?${params.toString()}`);
            return response;
        },
        keepPreviousData: true,
    });

    // Columns
    const columns = useMemo(() => [
        {
            accessorKey: 'school_name',
            header: 'School',
            cell: info => <span className="font-medium text-gray-900">{info.getValue()}</span>
        },
        {
            accessorKey: 'inspection_date', // Mapped from 'date'
            id: 'date',
            header: ({ column }) => (
                <button
                    className="flex items-center gap-1 font-bold text-gray-700 hover:text-cqa-primary"
                    onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}
                >
                    Date
                    <ArrowUpDown size={14} />
                </button>
            ),
            cell: info => (
                <div className="flex items-center gap-2 text-gray-600">
                    <Calendar size={14} />
                    {info.getValue()}
                </div>
            )
        },
        {
            accessorKey: 'author_name',
            header: 'Inspector',
            cell: info => (
                <div className="flex items-center gap-2 text-gray-600">
                    <User size={14} />
                    {info.getValue()}
                </div>
            )
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: info => {
                const status = info.getValue();
                let badgeClass = 'bg-gray-100 text-gray-800';
                if (status === 'approved') badgeClass = 'bg-green-100 text-green-800';
                if (status === 'submitted') badgeClass = 'bg-blue-100 text-blue-800';
                if (status === 'draft') badgeClass = 'bg-yellow-100 text-yellow-800';

                return (
                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium uppercase tracking-wide ${badgeClass}`}>
                        {status || 'Draft'}
                    </span>
                );
            }
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => {
                const report = row.original;
                return (
                    <div className="flex items-center gap-3">
                        {report.status === 'draft' ? (
                            <Link
                                to={`/edit/${report.id}`}
                                className="text-cqa-primary hover:text-indigo-800 text-sm font-medium flex items-center gap-1"
                            >
                                <Clock size={14} /> Resume
                            </Link>
                        ) : (
                            <Link
                                to={`/reports/${report.id}`}
                                className="text-gray-600 hover:text-gray-900 text-sm flex items-center gap-1"
                            >
                                <FileText size={14} /> View
                            </Link>
                        )}
                    </div>
                );
            }
        }
    ], []);

    const table = useReactTable({
        data: data?.data || [],
        columns,
        pageCount: data?.meta?.total_pages || -1,
        state: { pagination, sorting },
        onPaginationChange: setPagination,
        onSortingChange: setSorting,
        manualPagination: true,
        manualSorting: true,
        getCoreRowModel: getCoreRowModel(),
    });

    const tabs = [
        { id: '', label: 'All Reports' },
        { id: 'draft', label: 'Drafts' },
        { id: 'submitted', label: 'Submitted' },
        { id: 'approved', label: 'Approved' },
    ];

    return (
        <div className="space-y-6 animate-fade-in">
            {/* Header */}
            <div className="flex justify-between items-center">
                <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <FileText className="text-cqa-primary" /> Reports
                </h1>
                <Link to="/create" className="bg-cqa-primary text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition font-medium">
                    New Report
                </Link>
            </div>

            {/* Filters Toolbar */}
            <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                {/* Tabs */}
                <div className="flex border-b border-gray-200 bg-gray-50">
                    {tabs.map(tab => (
                        <button
                            key={tab.id}
                            onClick={() => { setStatusFilter(tab.id); setPagination(prev => ({ ...prev, pageIndex: 0 })); }}
                            className={`
                                px-6 py-3 text-sm font-medium transition-colors border-b-2
                                ${statusFilter === tab.id
                                    ? 'border-cqa-primary text-cqa-primary bg-white'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-100'}
                            `}
                        >
                            {tab.label}
                        </button>
                    ))}
                </div>

                {/* Search Bar */}
                <div className="p-4 flex justify-between gap-4">
                    <div className="relative flex-1 max-w-md">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
                        <input
                            type="text"
                            placeholder="Search by school, inspector..."
                            className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-cqa-primary focus:border-cqa-primary"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                        />
                    </div>
                    <button onClick={() => refetch()} className="p-2 text-gray-500 hover:text-cqa-primary transition" title="Refresh">
                        <RefreshCw size={20} className={isLoading ? 'animate-spin' : ''} />
                    </button>
                </div>
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
                                        Loading reports...
                                    </td>
                                </tr>
                            ) : isError ? (
                                <tr>
                                    <td colSpan={columns.length} className="px-6 py-12 text-center text-red-500">
                                        Error loading reports.
                                    </td>
                                </tr>
                            ) : table.getRowModel().rows.length === 0 ? (
                                <tr>
                                    <td colSpan={columns.length} className="px-6 py-12 text-center text-gray-500">
                                        No reports found.
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

export default ReportsList;
