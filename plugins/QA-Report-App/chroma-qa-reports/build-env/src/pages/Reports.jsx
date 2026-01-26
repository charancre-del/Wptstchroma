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
import { useReports } from '@hooks/useQueries';
import { formatDate, cn } from '@utils/helpers';
import {
    Search,
    Plus,
    FileText,
    Calendar,
    Building2,
    ChevronUp,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Loader2,
    AlertCircle,
    Clock,
    CheckCircle,
    XCircle,
    Eye,
    Download,
    MoreHorizontal,
} from 'lucide-react';
import * as DropdownMenu from '@radix-ui/react-dropdown-menu';

const STATUS_CONFIG = {
    draft: { label: 'Draft', color: 'bg-gray-100 text-gray-700', icon: Clock },
    pending: { label: 'Pending Review', color: 'bg-amber-100 text-amber-700', icon: Clock },
    approved: { label: 'Approved', color: 'bg-green-100 text-green-700', icon: CheckCircle },
    rejected: { label: 'Needs Revision', color: 'bg-red-100 text-red-700', icon: XCircle },
};

const REPORT_TYPES = {
    'tier-1': 'Tier 1 Assessment',
    'tier-2': 'Tier 2 CQI',
    'follow-up': 'Follow-up Visit',
};

export function ReportsPage() {
    const [globalFilter, setGlobalFilter] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [typeFilter, setTypeFilter] = useState('');
    const [sorting, setSorting] = useState([{ id: 'visit_date', desc: true }]);

    const { data, isLoading, error } = useReports();
    const reports = Array.isArray(data) ? data : (data?.data || []);

    // Apply filters
    const filteredReports = useMemo(() => {
        return reports.filter(report => {
            if (statusFilter && report.status !== statusFilter) return false;
            if (typeFilter && report.report_type !== typeFilter) return false;
            return true;
        });
    }, [reports, statusFilter, typeFilter]);

    const columns = useMemo(() => [
        {
            accessorKey: 'school_name',
            header: 'School',
            cell: ({ row }) => (
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                        <Building2 className="w-5 h-5 text-primary-600" />
                    </div>
                    <div>
                        <Link
                            to={`/reports/${row.original.id}`}
                            className="font-medium text-gray-900 hover:text-primary-600"
                        >
                            {row.original.school_name || 'Unknown School'}
                        </Link>
                        <p className="text-sm text-gray-500">
                            {REPORT_TYPES[row.original.report_type] || row.original.report_type}
                        </p>
                    </div>
                </div>
            ),
        },
        {
            accessorKey: 'visit_date',
            header: 'Visit Date',
            cell: ({ getValue }) => (
                <div className="flex items-center gap-2 text-gray-600">
                    <Calendar className="w-4 h-4" />
                    {formatDate(getValue()) || '—'}
                </div>
            ),
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ getValue }) => {
                const status = getValue() || 'draft';
                const config = STATUS_CONFIG[status] || STATUS_CONFIG.draft;
                const Icon = config.icon;
                return (
                    <span className={cn('px-2.5 py-1 rounded-full text-xs font-medium flex items-center gap-1 w-fit', config.color)}>
                        <Icon className="w-3 h-3" />
                        {config.label}
                    </span>
                );
            },
        },
        {
            accessorKey: 'author_name',
            header: 'Author',
            cell: ({ getValue }) => (
                <span className="text-gray-600">{getValue() || '—'}</span>
            ),
        },
        {
            accessorKey: 'created_at',
            header: 'Created',
            cell: ({ getValue }) => (
                <span className="text-sm text-gray-500">
                    {formatDate(getValue()) || '—'}
                </span>
            ),
        },
        {
            id: 'actions',
            header: '',
            cell: ({ row }) => (
                <DropdownMenu.Root>
                    <DropdownMenu.Trigger asChild>
                        <button className="p-2 hover:bg-gray-100 rounded-lg">
                            <MoreHorizontal className="w-4 h-4 text-gray-500" />
                        </button>
                    </DropdownMenu.Trigger>
                    <DropdownMenu.Portal>
                        <DropdownMenu.Content
                            className="bg-white rounded-lg shadow-lg border border-gray-200 py-1 min-w-[160px] z-50"
                            sideOffset={5}
                            align="end"
                        >
                            <DropdownMenu.Item asChild>
                                <Link
                                    to={`/reports/${row.original.id}`}
                                    className="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer"
                                >
                                    <Eye className="w-4 h-4" />
                                    View Report
                                </Link>
                            </DropdownMenu.Item>
                            {row.original.status !== 'draft' && (
                                <DropdownMenu.Item asChild>
                                    <a
                                        href={`${window.cqaData.restUrl}reports/${row.original.id}/pdf?format=download`}
                                        className="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer"
                                    >
                                        <Download className="w-4 h-4" />
                                        Download PDF
                                    </a>
                                </DropdownMenu.Item>
                            )}
                            {row.original.status === 'draft' && (
                                <DropdownMenu.Item asChild>
                                    <Link
                                        to={`/create?edit=${row.original.id}`}
                                        className="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer"
                                    >
                                        <FileText className="w-4 h-4" />
                                        Continue Editing
                                    </Link>
                                </DropdownMenu.Item>
                            )}
                        </DropdownMenu.Content>
                    </DropdownMenu.Portal>
                </DropdownMenu.Root>
            ),
        },
    ], []);

    const table = useReactTable({
        data: filteredReports,
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

    // Status counts
    const statusCounts = useMemo(() => {
        return reports.reduce((acc, r) => {
            const status = r.status || 'draft';
            acc[status] = (acc[status] || 0) + 1;
            return acc;
        }, {});
    }, [reports]);

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
                <p className="text-red-700">Failed to load reports. Please try again.</p>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Reports</h1>
                    <p className="text-gray-600">{reports.length} reports total</p>
                </div>
                <Link to="/create" className="btn btn-primary flex items-center gap-2">
                    <Plus className="w-4 h-4" />
                    New Report
                </Link>
            </div>

            {/* Status Pills */}
            <div className="flex flex-wrap gap-2">
                <button
                    onClick={() => setStatusFilter('')}
                    className={cn(
                        'px-3 py-1.5 rounded-full text-sm font-medium transition-colors',
                        !statusFilter
                            ? 'bg-primary-100 text-primary-700'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                    )}
                >
                    All ({reports.length})
                </button>
                {Object.entries(STATUS_CONFIG).map(([status, config]) => (
                    <button
                        key={status}
                        onClick={() => setStatusFilter(status)}
                        className={cn(
                            'px-3 py-1.5 rounded-full text-sm font-medium transition-colors',
                            statusFilter === status
                                ? config.color
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                        )}
                    >
                        {config.label} ({statusCounts[status] || 0})
                    </button>
                ))}
            </div>

            {/* Filters */}
            <div className="flex flex-col sm:flex-row gap-4">
                <div className="relative flex-1">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                    <input
                        type="text"
                        placeholder="Search reports..."
                        value={globalFilter}
                        onChange={(e) => setGlobalFilter(e.target.value)}
                        className="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    />
                </div>
                <select
                    value={typeFilter}
                    onChange={(e) => setTypeFilter(e.target.value)}
                    className="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
                    <option value="">All Types</option>
                    {Object.entries(REPORT_TYPES).map(([value, label]) => (
                        <option key={value} value={value}>{label}</option>
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
                        <FileText className="w-12 h-12 mx-auto mb-4 opacity-50" />
                        <p>No reports found</p>
                        <Link to="/create" className="text-primary-600 hover:underline mt-2 inline-block">
                            Create your first report
                        </Link>
                    </div>
                )}

                {/* Pagination */}
                <div className="px-4 py-3 border-t border-gray-200 flex items-center justify-between">
                    <div className="text-sm text-gray-600">
                        Showing {table.getState().pagination.pageIndex * table.getState().pagination.pageSize + 1} to{' '}
                        {Math.min(
                            (table.getState().pagination.pageIndex + 1) * table.getState().pagination.pageSize,
                            filteredReports.length
                        )}{' '}
                        of {filteredReports.length}
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
                            Page {table.getState().pagination.pageIndex + 1} of {table.getPageCount() || 1}
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

export default ReportsPage;
