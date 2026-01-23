import React, { useState, useMemo } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import PDFCard from '../common/PDFCard';

const LessonPlanSection = ({ items, type, onView, onDelete }) => {
    // Group items by 'group' (which contains Month Name or Quarter)
    const groupedItems = useMemo(() => {
        const groups = {};
        items.forEach(item => {
            const groupName = item.group || 'General';
            if (!groups[groupName]) groups[groupName] = [];
            groups[groupName].push(item);
        });
        return groups;
    }, [items]);

    const groupNames = useMemo(() => Object.keys(groupedItems), [groupedItems]);

    // Track which groups are expanded. Collapsed by default.
    const [expandedGroups, setExpandedGroups] = useState([]);

    const toggleGroup = (name) => {
        setExpandedGroups(prev =>
            prev.includes(name) ? prev.filter(g => g !== name) : [...prev, name]
        );
    };

    if (items.length === 0) return <div style={{ color: '#888', fontStyle: 'italic', padding: '20px' }}>No plans available for this year.</div>;

    return (
        <div className="expandable-section">
            {groupNames.map((name, index) => (
                <div key={name} style={{ marginBottom: '15px' }}>
                    <button
                        onClick={() => toggleGroup(name)}
                        style={{
                            width: '100%',
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'center',
                            padding: '16px 24px',
                            background: expandedGroups.includes(name) ? 'rgba(157, 130, 83, 0.08)' : 'white',
                            border: '1px solid rgba(0,0,0,0.05)',
                            borderRadius: '16px',
                            cursor: 'pointer',
                            transition: 'all 0.3s ease'
                        }}
                    >
                        <span style={{
                            fontSize: '1.1rem',
                            fontWeight: '600',
                            color: '#263238',
                            display: 'flex',
                            alignItems: 'center',
                            gap: '12px'
                        }}>
                            <span style={{
                                width: '32px',
                                height: '32px',
                                borderRadius: '8px',
                                background: '#9d8253',
                                color: 'white',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                fontSize: '0.9rem'
                            }}>
                                {index + 1}
                            </span>
                            {name}
                        </span>
                        <motion.span
                            animate={{ rotate: expandedGroups.includes(name) ? 180 : 0 }}
                            style={{ color: '#9d8253', fontSize: '1.2rem' }}
                        >
                            ↓
                        </motion.span>
                    </button>

                    <AnimatePresence>
                        {expandedGroups.includes(name) && (
                            <motion.div
                                initial={{ height: 0, opacity: 0, marginTop: 0 }}
                                animate={{ height: 'auto', opacity: 1, marginTop: '15px' }}
                                exit={{ height: 0, opacity: 0, marginTop: 0 }}
                                style={{ overflow: 'hidden' }}
                            >
                                <div style={{
                                    display: 'grid',
                                    gridTemplateColumns: 'repeat(auto-fill, minmax(280px, 1fr))',
                                    gap: '15px',
                                    paddingLeft: '10px'
                                }}>
                                    {groupedItems[name].map(item => (
                                        <PDFCard
                                            key={item.id}
                                            item={item}
                                            onClick={() => onView && onView(item)}
                                            onDelete={onDelete}
                                        />
                                    ))}
                                </div>
                            </motion.div>
                        )}
                    </AnimatePresence>
                </div>
            ))}
        </div>
    );
};

export default LessonPlanSection;
