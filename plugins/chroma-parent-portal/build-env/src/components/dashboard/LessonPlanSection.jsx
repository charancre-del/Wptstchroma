const LessonPlanSection = ({ items, type, onView, onDelete, showClassroomFilter = false }) => {
    // Nested grouping: Month -> Classroom -> [Items]
    const groupedItems = useMemo(() => {
        const months = {};
        items.forEach(item => {
            const monthName = item.group || 'General';
            if (!months[monthName]) months[monthName] = {};

            // If item has classrooms, group by them. Otherwise use 'General'.
            const classroomNames = (item.classrooms && item.classrooms.length > 0)
                ? item.classrooms
                : ['General'];

            classroomNames.forEach(c => {
                const cName = c.charAt(0).toUpperCase() + c.slice(1).replace('-', ' ');
                if (!months[monthName][cName]) months[monthName][cName] = [];
                months[monthName][cName].push(item);
            });
        });
        return months;
    }, [items]);

    const monthNames = useMemo(() => Object.keys(groupedItems), [groupedItems]);

    // Track which months and classrooms are expanded
    const [expandedMonths, setExpandedMonths] = useState([]);
    const [expandedClassrooms, setExpandedClassrooms] = useState([]);

    const toggleMonth = (name) => {
        setExpandedMonths(prev =>
            prev.includes(name) ? prev.filter(m => m !== name) : [...prev, name]
        );
    };

    const toggleClassroom = (monthName, classroomName) => {
        const key = `${monthName}-${classroomName}`;
        setExpandedClassrooms(prev =>
            prev.includes(key) ? prev.filter(k => k !== key) : [...prev, key]
        );
    };

    const handleDelete = async (item) => {
        if (!window.confirm(`Are you sure you want to delete "${item.title}"?`)) return;

        try {
            const settings = window.chromaPortalSettings;
            const res = await fetch(`${settings.root}chroma-portal/v1/content/delete/${item.id}`, {
                method: 'DELETE',
                headers: { 'X-WP-Nonce': settings.nonce }
            });
            const data = await res.json();
            if (data.success) {
                if (onDelete) onDelete();
            } else {
                alert('Delete failed');
            }
        } catch (e) {
            alert('Error deleting content');
        }
    };

    if (items.length === 0) return <div style={{ color: '#888', fontStyle: 'italic', padding: '20px' }}>No documents found.</div>;

    const DocumentRow = ({ item }) => (
        <div style={{
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            padding: '10px 15px',
            background: 'white',
            borderBottom: '1px solid rgba(0,0,0,0.03)',
            transition: 'background 0.2s ease',
            cursor: 'pointer'
        }}
            onClick={() => onView && onView(item)}
            className="document-row-hover"
        >
            <div style={{ display: 'flex', alignItems: 'center', gap: '10px', flex: 1, minWidth: 0 }}>
                <span style={{ fontSize: '1.2rem' }}>{item.pdf_url ? '📄' : (item.event_date ? '📅' : '📃')}</span>
                <span style={{
                    fontWeight: '500',
                    color: '#263238',
                    fontSize: '0.9rem',
                    whiteSpace: 'nowrap',
                    overflow: 'hidden',
                    textOverflow: 'ellipsis'
                }}>
                    {item.title}
                </span>
            </div>
            <div style={{ display: 'flex', alignItems: 'center', gap: '15px' }}>
                <span style={{ fontSize: '0.75rem', color: '#9d8253', fontWeight: '600', textTransform: 'uppercase', letterSpacing: '0.5px' }}>View</span>
                {item.can_edit && (
                    <div style={{ display: 'flex', gap: '8px' }}>
                        <button
                            onClick={(e) => { e.stopPropagation(); window.open('/wp-admin/post.php?post=' + item.id + '&action=edit', '_blank'); }}
                            style={{ background: 'none', border: 'none', cursor: 'pointer', padding: '2px', fontSize: '0.9rem' }}
                            title="Edit"
                        >
                            ✏️
                        </button>
                        <button
                            onClick={(e) => { e.stopPropagation(); handleDelete(item); }}
                            style={{ background: 'none', border: 'none', cursor: 'pointer', padding: '2px', fontSize: '0.9rem' }}
                            title="Delete"
                        >
                            🗑️
                        </button>
                    </div>
                )}
            </div>
        </div>
    );

    return (
        <div className="nested-expandable-section">
            {monthNames.map((monthName, mIndex) => (
                <div key={monthName} style={{ marginBottom: '10px' }}>
                    {/* Month Header */}
                    <button
                        onClick={() => toggleMonth(monthName)}
                        style={{
                            width: '100%',
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'center',
                            padding: '14px 20px',
                            background: expandedMonths.includes(monthName) ? 'rgba(157, 130, 83, 0.1)' : '#f8f9fa',
                            border: '1px solid rgba(0,0,0,0.05)',
                            borderRadius: '12px',
                            cursor: 'pointer',
                            transition: 'all 0.2s ease'
                        }}
                    >
                        <span style={{ fontSize: '1rem', fontWeight: '700', color: '#263238', display: 'flex', alignItems: 'center', gap: '10px' }}>
                            <span style={{ width: '24px', height: '24px', borderRadius: '6px', background: '#9d8253', color: 'white', display: 'flex', alignItems: 'center', justifyCenter: 'center', fontSize: '0.8rem' }}>
                                {mIndex + 1}
                            </span>
                            {monthName}
                        </span>
                        <motion.span animate={{ rotate: expandedMonths.includes(monthName) ? 180 : 0 }} style={{ color: '#9d8253' }}>↓</motion.span>
                    </button>

                    <AnimatePresence>
                        {expandedMonths.includes(monthName) && (
                            <motion.div
                                initial={{ height: 0, opacity: 0 }}
                                animate={{ height: 'auto', opacity: 1 }}
                                exit={{ height: 0, opacity: 0 }}
                                style={{ overflow: 'hidden', paddingLeft: '15px', marginTop: '5px' }}
                            >
                                {Object.keys(groupedItems[monthName]).map(cName => (
                                    <div key={cName} style={{ marginBottom: '5px' }}>
                                        {/* Classroom Sub-Header (Only show if not just 'General') */}
                                        {cName !== 'General' ? (
                                            <>
                                                <button
                                                    onClick={() => toggleClassroom(monthName, cName)}
                                                    style={{
                                                        width: '100%',
                                                        display: 'flex',
                                                        justifyContent: 'space-between',
                                                        alignItems: 'center',
                                                        padding: '10px 15px',
                                                        background: 'white',
                                                        border: '1px solid rgba(0,0,0,0.03)',
                                                        borderRadius: '8px',
                                                        cursor: 'pointer',
                                                        marginBottom: '2px'
                                                    }}
                                                >
                                                    <span style={{ fontSize: '0.9rem', fontWeight: '600', color: '#546e7a' }}>📁 {cName}</span>
                                                    <motion.span animate={{ rotate: expandedClassrooms.includes(`${monthName}-${cName}`) ? 180 : 0 }} style={{ fontSize: '0.8rem' }}>↓</motion.span>
                                                </button>
                                                <AnimatePresence>
                                                    {expandedClassrooms.includes(`${monthName}-${cName}`) && (
                                                        <motion.div
                                                            initial={{ height: 0, opacity: 0 }}
                                                            animate={{ height: 'auto', opacity: 1 }}
                                                            exit={{ height: 0, opacity: 0 }}
                                                            style={{ overflow: 'hidden', borderLeft: '2px solid rgba(157, 130, 83, 0.2)', marginLeft: '10px' }}
                                                        >
                                                            {groupedItems[monthName][cName].map(item => (
                                                                <DocumentRow key={item.id} item={item} />
                                                            ))}
                                                        </motion.div>
                                                    )}
                                                </AnimatePresence>
                                            </>
                                        ) : (
                                            /* If General, just show the documents directly */
                                            <div style={{ borderRadius: '8px', overflow: 'hidden', border: '1px solid rgba(0,0,0,0.05)' }}>
                                                {groupedItems[monthName][cName].map(item => (
                                                    <DocumentRow key={item.id} item={item} />
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </motion.div>
                        )}
                    </AnimatePresence>
                </div>
            ))}
        </div>
    );
};

export default LessonPlanSection;
