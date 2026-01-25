import React, { useState } from 'react';
import PDFCard from '../common/PDFCard';

const DownloadCenter = ({ resources, forms, onView, onDelete }) => {
    // Combine for ALL search
    const allItems = [...resources, ...forms];
    const [search, setSearch] = useState('');
    const [view, setView] = useState('all'); // all, policies, forms

    const filtered = allItems.filter(item => {
        // Search Filter
        if (search && !item.title.toLowerCase().includes(search.toLowerCase())) return false;

        // Type Filter
        if (view === 'policies' && !resources.includes(item)) return false;
        if (view === 'forms' && !forms.includes(item)) return false;

        return true;
    });

    return (
        <div className="download-center">
            {/* Controls */}
            <div className="controls" style={{ display: 'flex', gap: '15px', marginBottom: '20px' }}>
                <input
                    type="text"
                    placeholder="Search documents..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    style={{ flex: 1, padding: '10px', borderRadius: '8px', border: '1px solid #ddd' }}
                />
                <select value={view} onChange={(e) => setView(e.target.value)} style={{ padding: '10px', borderRadius: '8px', border: '1px solid #ddd' }}>
                    <option value="all">All Downloads</option>
                    <option value="policies">Policies & Procedures</option>
                    <option value="forms">Forms</option>
                </select>
            </div>

            {/* Grid */}
            <div className="downloads-grid" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))', gap: '15px' }}>
                {filtered.map(item => (
                    <PDFCard key={item.id} item={item} showThumb={false} onClick={() => onView && onView(item)} onDelete={onDelete} />
                ))}
                {filtered.length === 0 && <p>No documents found.</p>}
            </div>
        </div>
    );
};

export default DownloadCenter;
