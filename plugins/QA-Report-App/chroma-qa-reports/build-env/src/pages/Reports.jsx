import React, { useState, useMemo, useEffect } from 'react';
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
    Eye,
    Download,
    MoreHorizontal,
} from 'lucide-react';
import * as DropdownMenu from '@radix-ui/react-dropdown-menu';

const STATUS_CONFIG = {
    draft: { label: 'Draft', color: 'bg-brand-ink/5 text-brand-ink/60', icon: Clock },
    submitted: { label: 'Submitted', color: 'bg-chroma-yellow/10 text-chroma-yellow', icon: Clock },
    approved: { label: 'Approved', color: 'bg-chroma-green/10 text-chroma-green', icon: CheckCircle },
};

const REPORT_TYPES = {
    tier1: 'Tier 1 Assessment',
    tier1_tier2: 'Tier 1 + Tier 2 (CQI)',
    new_acquisition: 'New Acquisition',
};
import { useSearchParams } from 'react-router-dom';

export function ReportsPage() {
    const [searchParams, setSearchParams] = useSearchParams();
    const initialStatus = searchParams.get('status') || '';
    const initialType = searchParams.get('type') || '';
    const initialAuthor = searchParams.get('author') || '';
    const initialSchoolId = searchParams.get('school_id') || '';

    const [globalFilter, setGlobalFilter] = useState('');
    const [statusFilter, setStatusFilter] = useState(initialStatus);
    const [typeFilter, setTypeFilter] = useState(initialType);
    const [authorFilter, setAuthorFilter] = useState(initialAuthor);
    const [schoolFilter] = useState(initialSchoolId);
    const [sorting, setSorting] = useState([{ id: 'visit_date', desc: true }]);

    // Sync URL with state
    useEffect(() => {
        const params = new URLSearchParams();
        if (statusFilter) params.set('status', statusFilter);
        if (typeFilter) params.set('type', typeFilter);
        if (authorFilter) params.set('author', authorFilter);
        if (schoolFilter) params.set('school_id', schoolFilter);
        setSearchParams(params);
    }, [statusFilter, typeFilter, authorFilter, schoolFilter, setSearchParams]);

    const { data, isLoading, error } = useReports();
    const reports = Array.isArray(data) ? data : (data?.data || []);

    // Apply filters
    const filteredReports = useMemo(() => {
        return reports.filter(report => {
            if (statusFilter && report.status !== statusFilter) return false;
            if (typeFilter && report.report_type !== typeFilter) return false;
            if (authorFilter === 'me' && report.is_mine !== true) return false; // Assuming 'is_mine' or similar prop exists from API
            if (schoolFilter && String(report.school_id) !== String(schoolFilter)) return false;
            return true;
        });
    }, [reports, statusFilter, typeFilter, authorFilter, schoolFilter]);

    const columns = useMemo(() => [
        {
            accessorKey: 'school_name',
            header: 'School',
            cell: ({ row }) => (
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 bg-chroma-blue/10 rounded-xl flex items-center justify-center">
                        <Building2 className="w-5 h-5 text-chroma-blue" />
                    </div>
                    <div>
                        <Link
                            to={`/reports/${row.original.id}`}
                            className="font-bold text-brand-ink hover:text-chroma-blue transition-colors font-outfit"
                        >
                            {row.original.school_name || 'Unknown School'}
                        </Link>
                        <p className="text-sm text-brand-ink/60 font-medium">
                            {row.original.report_type_label || REPORT_TYPES[row.original.report_type] || row.original.report_type}
                        </p>
                    </div>
                </div>
            ),
        },
        {
            accessorKey: 'visit_date',
            header: 'Visit Date',
            cell: ({ getValue }) => (
                <div className="flex items-center gap-2 text-brand-ink/60 font-medium">
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
                    <span className={cn('px-3 py-1.5 rounded-full text-xs font-bold flex items-center gap-1.5 w-fit uppercase tracking-wider', config.color)}>
                        <Icon className="w-3.5 h-3.5" />
                        {config.label}
                    </span>
                );
            },
        },
        {
            accessorKey: 'author_name',
            header: 'Author',
            cell: ({ getValue }) => (
                <span className="text-brand-ink/60 font-medium">{getValue() || '—'}</span>
            ),
        },
        {
            accessorKey: 'created_at',
            header: 'Created',
            cell: ({ getValue }) => (
                <span className="text-sm text-brand-ink/40 font-medium">
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
                        <button className="p-2 hover:bg-brand-ink/5 rounded-lg text-brand-ink/40 hover:text-brand-ink transition-colors">
                            <MoreHorizontal className="w-4 h-4" />
                        </button>
                    </DropdownMenu.Trigger>
                    <DropdownMenu.Portal>
                        <DropdownMenu.Content
                            className="bg-white rounded-xl shadow-lg border border-brand-ink/5 py-2 min-w-[180px] z-50 animate-in fade-in zoom-in-95 duration-200"
                            sideOffset={5}
                            align="end"
                        >
                            <DropdownMenu.Item asChild>
                                <Link
                                    to={`/reports/${row.original.id}`}
                                    className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-brand-ink hover:bg-brand-cream/50 cursor-pointer"
                                >
                                    <Eye className="w-4 h-4 text-brand-ink/40" />
                                    View Report
                                </Link>
                            </DropdownMenu.Item>
                            {row.original.status !== 'draft' && (
                                <DropdownMenu.Item asChild>
                                    <a
                                        href={`${window.cqaData.restUrl}reports/${row.original.id}/pdf?format=download`}
                                        className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-brand-ink hover:bg-brand-cream/50 cursor-pointer"
                                    >
                                        <Download className="w-4 h-4 text-brand-ink/40" />
                                        Download PDF
                                    </a>
                                </DropdownMenu.Item>
                            )}
                            {row.original.status === 'draft' && (
                                <DropdownMenu.Item asChild>
                                <Link
                                    to={`/edit/${row.original.id}`}
                                    className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-brand-ink hover:bg-brand-cream/50 cursor-pointer"
                                >
                                    <FileText className="w-4 h-4 text-brand-ink/40" />
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
                <Loader2 className="w-8 h-8 text-chroma-blue animate-spin" />
            </div>
        );
    }

    if (error) {
        return (
            <div className="bg-chroma-red/5 border border-chroma-red/20 rounded-3xl p-8 text-center">
                <AlertCircle className="w-12 h-12 text-chroma-red mx-auto mb-4" />
                <p className="text-brand-ink font-medium">Failed to load reports. Please try again.</p>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-3xl font-serif font-bold text-brand-ink">Reports</h1>
                    <p className="text-brand-ink/60 font-medium mt-1">{reports.length} reports total</p>
                </div>
                <Link to="/create" className="btn bg-brand-ink text-white hover:bg-brand-ink/90 rounded-xl px-6 py-2.5 flex items-center gap-2 font-medium shadow-sm transition-all hover:scale-105 active:scale-95">
                    <Plus className="w-4 h-4" />
                    New Report
                </Link>
            </div>

            {/* Status Pills */}
            <div className="flex flex-wrap gap-2">
                <button
                    onClick={() => setStatusFilter('')}
                    className={cn(
                        'px-4 py-2 rounded-full text-sm font-bold transition-all border',
                        !statusFilter
                            ? 'bg-brand-ink text-white border-brand-ink'
                            : 'bg-white text-brand-ink/60 border-brand-ink/5 hover:border-brand-ink/20 hover:text-brand-ink'
                    )}
                >
                    All ({reports.length})
                </button>
                {Object.entries(STATUS_CONFIG).map(([status, config]) => (
                    <button
                        key={status}
                        onClick={() => setStatusFilter(status)}
                        className={cn(
                            'px-4 py-2 rounded-full text-sm font-bold transition-all border',
                            statusFilter === status
                                ? config.color + ' border-transparent ring-2 ring-offset-2 ring-transparent'
                                : 'bg-white text-brand-ink/60 border-brand-ink/5 hover:border-brand-ink/20 hover:text-brand-ink'
                        )}
                    >
                        {config.label} ({statusCounts[status] || 0})
                    </button>
                ))}
            </div>

            {/* Filters */}
            <div className="flex flex-col sm:flex-row gap-4">
                <div className="relative flex-1">
                    <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-brand-ink/30" />
                    <input
                        type="text"
                        placeholder="Search reports by school..."
                        value={globalFilter}
                        onChange={(e) => setGlobalFilter(e.target.value)}
                        className="w-full pl-12 pr-4 py-3 border border-brand-ink/10 rounded-2xl focus:ring-2 focus:ring-chroma-blue/20 focus:border-chroma-blue bg-white text-brand-ink placeholder:text-brand-ink/30 transition-all shadow-sm"
                    />
                </div>
                <select
                    value={typeFilter}
                    onChange={(e) => setTypeFilter(e.target.value)}
                    className="px-6 py-3 border border-brand-ink/10 rounded-2xl focus:ring-2 focus:ring-chroma-blue/20 focus:border-chroma-blue bg-white text-brand-ink font-medium shadow-sm cursor-pointer hover:border-brand-ink/20 transition-all"
                >
                    <option value="">All Types</option>
                    {Object.entries(REPORT_TYPES).map(([value, label]) => (
                        <option key={value} value={value}>{label}</option>
                    ))}
                </select>
            </div>

            {/* Table */}
            <div className="bg-white rounded-3xl border border-brand-ink/5 shadow-sm overflow-hidden">
                <table className="w-full">
                    <thead className="bg-brand-cream border-b border-brand-ink/5">
                        {table.getHeaderGroups().map(headerGroup => (
                            <tr key={headerGroup.id}>
                                {headerGroup.headers.map(header => (
                                    <th
                                        key={header.id}
                                        className="px-6 py-4 text-left text-xs font-bold text-brand-ink/50 uppercase tracking-wider"
                                    >
                                        {header.isPlaceholder ? null : (
                                            <div
                                                className={cn(
                                                    'flex items-center gap-1',
                                                    header.column.getCanSort() && 'cursor-pointer select-none hover:text-brand-ink transition-colors'
                                                )}
                                                onClick={header.column.getToggleSortingHandler()}
                                            >
                                                {flexRender(header.column.columnDef.header, header.getContext())}
                                                {header.column.getIsSorted() === 'asc' && <ChevronUp className="w-3 h-3" />}
                                                {header.column.getIsSorted() === 'desc' && <ChevronDown className="w-3 h-3" />}
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
                        <FileText className="w-12 h-12 mx-auto mb-4 opacity-30" />
                        <p className="font-medium">No reports found matching your criteria.</p>
                        <Link to="/create" className="text-chroma-blue hover:underline mt-2 inline-block font-bold">
                            Create your first report
                        </Link>
                    </div>
                )}

                {/* Pagination */}
                <div className="px-6 py-4 border-t border-brand-ink/5 flex items-center justify-between bg-white">
                    <div className="text-sm font-medium text-brand-ink/40">
                        Showing <span className="text-brand-ink">{table.getState().pagination.pageIndex * table.getState().pagination.pageSize + 1}</span> to{' '}
                        <span className="text-brand-ink">
                            {Math.min(
                                (table.getState().pagination.pageIndex + 1) * table.getState().pagination.pageSize,
                                filteredReports.length
                            )}
                        </span>{' '}
                        of <span className="text-brand-ink">{filteredReports.length}</span>
                    </div>
                    <div className="flex items-center gap-2">
                        <button
                            onClick={() => table.previousPage()}
                            disabled={!table.getCanPreviousPage()}
                            className="p-2 border border-brand-ink/10 rounded-xl hover:bg-brand-cream hover:border-brand-ink/20 disabled:opacity-30 disabled:cursor-not-allowed transition-all text-brand-ink"
                        >
                            <ChevronLeft className="w-5 h-5" />
                        </button>
                        <span className="text-sm font-bold text-brand-ink text-center min-w-[3rem]">
                            Page {table.getState().pagination.pageIndex + 1} / {table.getPageCount() || 1}
                        </span>
                        <button
                            onClick={() => table.nextPage()}
                            disabled={!table.getCanNextPage()}
                            className="p-2 border border-brand-ink/10 rounded-xl hover:bg-brand-cream hover:border-brand-ink/20 disabled:opacity-30 disabled:cursor-not-allowed transition-all text-brand-ink"
                        >
                            <ChevronRight className="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default ReportsPage;
