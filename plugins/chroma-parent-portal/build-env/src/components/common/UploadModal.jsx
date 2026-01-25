import React, { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';

const UploadModal = ({ isOpen, onClose, type, onSuccess }) => {
    const { user } = useAuth();
    const [file, setFile] = useState(null);
    const [title, setTitle] = useState('');
    const [group, setGroup] = useState('');
    const [year, setYear] = useState(new Date().getFullYear().toString());
    const [eventDate, setEventDate] = useState('');
    const [isUploading, setIsUploading] = useState(false);

    // Dynamic taxonomy values from WordPress
    const [availableYears, setAvailableYears] = useState([]);
    const [months, setMonths] = useState([]);
    const [quarters, setQuarters] = useState([]);
    const [categories, setCategories] = useState([]);

    // Fetch taxonomy data when modal opens
    useEffect(() => {
        if (!isOpen) return;

        const settings = window.chromaPortalSettings;
        const fetchTaxonomies = async () => {
            try {
                // Fetch years
                const yearsRes = await fetch(`${settings.root}chroma-portal/v1/years`, {
                    headers: {
                        'X-Portal-Token': user.token,
                        'X-WP-Nonce': settings.nonce
                    }
                });
                if (yearsRes.ok) {
                    const years = await yearsRes.json();
                    setAvailableYears(years);
                    if (years.length > 0 && !year) {
                        setYear(years[0].label); // Default to first available year LABEL
                    }
                }

                // Fetch months
                const monthsRes = await fetch(`${settings.root}chroma-portal/v1/taxonomy/portal_month`, {
                    headers: {
                        'X-Portal-Token': user.token,
                        'X-WP-Nonce': settings.nonce
                    }
                });
                if (monthsRes.ok) {
                    setMonths(await monthsRes.json());
                }

                // Fetch quarters
                const quartersRes = await fetch(`${settings.root}chroma-portal/v1/taxonomy/portal_quarter`, {
                    headers: {
                        'X-Portal-Token': user.token,
                        'X-WP-Nonce': settings.nonce
                    }
                });
                if (quartersRes.ok) {
                    setQuarters(await quartersRes.json());
                }

                // Fetch categories
                const categoriesRes = await fetch(`${settings.root}chroma-portal/v1/taxonomy/portal_category`, {
                    headers: {
                        'X-Portal-Token': user.token,
                        'X-WP-Nonce': settings.nonce
                    }
                });
                if (categoriesRes.ok) {
                    setCategories(await categoriesRes.json());
                }
            } catch (e) {
                console.error('[UploadModal] Failed to fetch taxonomies:', e);
            }
        };

        fetchTaxonomies();
    }, [isOpen, user.token]);

    if (!isOpen) return null;

    const handleSubmit = async (e) => {
        e.preventDefault();
        setIsUploading(true);
        const settings = window.chromaPortalSettings;

        try {
            // 1. Upload File (If provided, optional for announcements/events maybe? No, plan says PDF)
            let fileId = 0;
            if (file) {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('title', title);

                const uploadRes = await fetch(`${settings.root}wp/v2/media`, {
                    method: 'POST',
                    headers: { 'X-WP-Nonce': settings.nonce },
                    body: formData
                });
                const uploadJson = await uploadRes.json();
                fileId = uploadJson.id;
            }

            // 2. Create Content
            const contentData = {
                title,
                post_type: type,
                year, // Now sending the label (e.g. "2026-2027")
                month: group,
                file_id: fileId,
                event_date: eventDate
            };

            const createRes = await fetch(`${settings.root}chroma-portal/v1/content/create`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': settings.nonce
                },
                body: JSON.stringify(contentData)
            });

            const createJson = await createRes.json();

            if (createJson.success) {
                onSuccess();
                onClose();
            } else {
                alert('Error creating content: ' + (createJson.message || 'Unknown error'));
            }

        } catch (e) {
            console.error(e);
            alert('Upload Failed');
        } finally {
            setIsUploading(false);
        }
    };

    const getGroupOptions = () => {
        if (type === 'cp_lesson_plan') return months;
        if (type === 'cp_meal_plan') return quarters;
        return categories;
    };

    const isFileRequired = !['cp_announcement', 'cp_event'].includes(type);

    return (
        <div className="modal-overlay" style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0,0,0,0.6)', backdropFilter: 'blur(5px)', zIndex: 1000, display: 'flex', justifyContent: 'center', alignItems: 'center' }}>
            <div className="glass-panel" style={{ background: 'white', padding: '30px', width: '95%', maxWidth: '500px', borderRadius: '15px', boxShadow: '0 20px 40px rgba(0,0,0,0.2)' }}>
                <h2 style={{ marginBottom: '20px' }}>Add {type.replace('cp_', '').replace('_', ' ')}</h2>
                <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '15px' }}>

                    <label style={{ fontSize: '14px', fontWeight: '600' }}>Title</label>
                    <input type="text" placeholder="e.g. January Lesson Plan" value={title} onChange={e => setTitle(e.target.value)} required style={{ padding: '12px', borderRadius: '8px', border: '1px solid #ddd' }} />

                    {isFileRequired && (
                        <>
                            <label style={{ fontSize: '14px', fontWeight: '600' }}>Upload PDF</label>
                            <input type="file" onChange={e => setFile(e.target.files[0])} accept=".pdf" required={isFileRequired} style={{ padding: '10px', background: '#f9f9f9', borderRadius: '8px' }} />
                        </>
                    )}

                    <div style={{ display: 'flex', gap: '15px' }}>
                        <div style={{ flex: 1 }}>
                            <label style={{ fontSize: '14px', fontWeight: '600', display: 'block', marginBottom: '5px' }}>Year</label>
                            <select value={year} onChange={e => setYear(e.target.value)} style={{ padding: '12px', width: '100%', borderRadius: '8px', border: '1px solid #ddd' }}>
                                {availableYears.length > 0 ? (
                                    availableYears.map(y => <option key={y.label} value={y.label}>{y.label}</option>)
                                ) : (
                                    <option value={new Date().getFullYear()}>{new Date().getFullYear()}</option>
                                )}
                            </select>
                        </div>
                        <div style={{ flex: 1 }}>
                            <label style={{ fontSize: '14px', fontWeight: '600', display: 'block', marginBottom: '5px' }}>Category/Group</label>
                            <select value={group} onChange={e => setGroup(e.target.value)} style={{ padding: '12px', width: '100%', borderRadius: '8px', border: '1px solid #ddd' }}>
                                <option value="">Select...</option>
                                {getGroupOptions().map(opt => <option key={opt} value={opt}>{opt}</option>)}
                            </select>
                        </div>
                    </div>

                    {type === 'cp_event' && (
                        <div>
                            <label style={{ fontSize: '14px', fontWeight: '600', display: 'block', marginBottom: '5px' }}>Event Date</label>
                            <input type="date" value={eventDate} onChange={e => setEventDate(e.target.value)} required style={{ padding: '12px', width: '100%', borderRadius: '8px', border: '1px solid #ddd' }} />
                        </div>
                    )}

                    <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '15px', marginTop: '25px' }}>
                        <button type="button" onClick={onClose} style={{ padding: '12px 20px', background: 'none', border: 'none', cursor: 'pointer', fontWeight: '600' }}>Cancel</button>
                        <button type="submit" className="portal-btn" disabled={isUploading} style={{ minWidth: '120px' }}>
                            {isUploading ? 'Saving...' : 'Save Content'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default UploadModal;
