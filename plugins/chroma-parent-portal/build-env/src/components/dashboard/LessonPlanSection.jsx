import React, { useState, useMemo } from 'react';
import PDFCard from '../common/PDFCard';

const LessonPlanSection = ({ items, type, onView, onDelete }) => {
    // Group items by 'group' (which contains Month Name or Quarter)
    // Actually API returns 'group' = term name.

    // Get unique months from items
    const months = useMemo(() => {
        const m = [...new Set(items.map(i => i.group || 'General'))];
        return m;
    }, [items]);

    const [activeTab, setActiveTab] = useState(months[0] || 'General');

    // Effect to update active tab if months change (e.g. year switch)
    React.useEffect(() => {
        if (months.length > 0 && !months.includes(activeTab)) {
            setActiveTab(months[0]);
        }
    }, [months]);

    const filteredItems = items.filter(i => (i.group || 'General') === activeTab);

    if (items.length === 0) return <div style={{ color: '#888', fontStyle: 'italic' }}>No plans available for this year.</div>;

    return (
        <div>
            {/* Tabs */}
            <div className="tabs" style={{ display: 'flex', gap: '10px', overflowX: 'auto', paddingBottom: '10px', marginBottom: '15px' }}>
                {months.map(m => (
                    <button
                        key={m}
                        onClick={() => setActiveTab(m)}
                        style={{
                            background: activeTab === m ? '#6a11cb' : 'rgba(255,255,255,0.5)',
                            color: activeTab === m ? 'white' : '#333',
                            border: 'none',
                            padding: '5px 15px',
                            borderRadius: '20px',
                            cursor: 'pointer',
                            whiteSpace: 'nowrap'
                        }}
                    >
                        {m}
                    </button>
                ))}
            </div>

            {/* List */}
            <div className="list">
                {filteredItems.map(item => (
                    <PDFCard key={item.id} item={item} onClick={() => onView && onView(item)} onDelete={onDelete} />
                ))}
            </div>
        </div>
    );
};

export default LessonPlanSection;
