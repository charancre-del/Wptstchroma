import React, { useMemo, useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import PDFCard from '../common/PDFCard';

const OrganizationGroup = ({ items, onView, onDelete, emptyMessage = "No items found." }) => {
    // Group by School, then by Month
    const groupedData = useMemo(() => {
        const schools = {};
        items.forEach(item => {
            const schoolName = item.school || 'General';
            const monthName = item.group || 'Other'; // API 'group' currently holds month/category

            if (!schools[schoolName]) schools[schoolName] = {};
            if (!schools[schoolName][monthName]) schools[schoolName][monthName] = [];

            schools[schoolName][monthName].push(item);
        });
        return schools;
    }, [items]);

    const schoolNames = useMemo(() => Object.keys(groupedData).sort(), [groupedData]);
    const [expandedSchools, setExpandedSchools] = useState(schoolNames.length === 1 ? [schoolNames[0]] : []);

    const toggleSchool = (name) => {
        setExpandedSchools(prev =>
            prev.includes(name) ? prev.filter(s => s !== name) : [...prev, name]
        );
    };

    if (items.length === 0) {
        return <div style={{ color: '#888', fontStyle: 'italic', padding: '20px' }}>{emptyMessage}</div>;
    }

    return (
        <div className="organization-group-wrapper">
            {schoolNames.map(school => (
                <div key={school} className="school-group" style={{ marginBottom: '30px' }}>
                    <button
                        onClick={() => toggleSchool(school)}
                        style={{
                            width: '100%',
                            textAlign: 'left',
                            padding: '15px 20px',
                            background: '#263238',
                            color: 'white',
                            border: 'none',
                            borderRadius: '12px',
                            marginBottom: '15px',
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'center',
                            cursor: 'pointer'
                        }}
                    >
                        <h3 style={{ margin: 0, fontSize: '1.2rem' }}>{school}</h3>
                        <motion.span animate={{ rotate: expandedSchools.includes(school) ? 180 : 0 }}>
                            ↓
                        </motion.span>
                    </button>

                    <AnimatePresence>
                        {expandedSchools.includes(school) && (
                            <motion.div
                                initial={{ opacity: 0, height: 0 }}
                                animate={{ opacity: 1, height: 'auto' }}
                                exit={{ opacity: 0, height: 0 }}
                                style={{ overflow: 'hidden' }}
                            >
                                {Object.keys(groupedData[school]).sort().map(month => (
                                    <div key={month} className="month-subgroup" style={{ marginBottom: '20px', paddingLeft: '10px' }}>
                                        <h4 style={{
                                            color: '#9d8253',
                                            borderBottom: '2px solid rgba(157, 130, 83, 0.2)',
                                            paddingBottom: '5px',
                                            marginBottom: '15px'
                                        }}>
                                            {month}
                                        </h4>
                                        <div style={{
                                            display: 'grid',
                                            gridTemplateColumns: 'repeat(auto-fill, minmax(280px, 1fr))',
                                            gap: '15px'
                                        }}>
                                            {groupedData[school][month].map(item => (
                                                <PDFCard
                                                    key={item.id}
                                                    item={item}
                                                    onClick={() => onView && onView(item)}
                                                    onDelete={onDelete}
                                                />
                                            ))}
                                        </div>
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

export default OrganizationGroup;
