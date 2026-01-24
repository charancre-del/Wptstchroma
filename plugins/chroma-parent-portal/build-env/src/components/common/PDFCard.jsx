import React from 'react';

const PDFCard = ({ item, onClick, onDelete, showThumb = true }) => {
    // Determine icon based on group or title
    const icon = item.pdf_url ? '📄' : (item.event_date ? '📅' : '⚠️');

    const handleDelete = async (e) => {
        e.stopPropagation();
        if (!window.confirm('Are you sure you want to delete this content?')) return;

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

    return (
        <div className="pdf-card" onClick={() => onClick(item)} style={{
            background: 'white',
            borderRadius: '10px',
            padding: '15px',
            marginBottom: '10px',
            boxShadow: '0 2px 5px rgba(0,0,0,0.05)',
            cursor: 'pointer',
            position: 'relative',
            display: 'flex',
            alignItems: 'center',
            gap: '15px'
        }}>
            <div className="icon" style={{ fontSize: '24px', background: '#f0f2f5', width: '50px', height: '50px', display: 'flex', alignItems: 'center', justifyContent: 'center', borderRadius: '8px' }}>
                {icon}
            </div>
            <div className="info" style={{ flex: 1, minWidth: 0 }}>
                <h5 style={{ margin: '0 0 5px 0', fontSize: '16px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{item.title}</h5>
                <div style={{ display: 'flex', gap: '10px', alignItems: 'center' }}>
                    <span style={{ fontSize: '12px', color: '#888', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>{item.group || 'Document'}</span>
                    {item.event_date && (
                        <span style={{ fontSize: '12px', color: '#6a11cb', fontWeight: '600', whiteSpace: 'nowrap' }}>
                            {new Date(item.event_date).toLocaleDateString()}
                        </span>
                    )}
                </div>
            </div>
            <button className="download-btn" style={{ border: '1px solid #ddd', background: 'none', borderRadius: '50%', width: '30px', height: '30px', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                ⬇
            </button>

            {item.can_edit && (
                <div className="admin-overlays" style={{ position: 'absolute', top: '5px', right: '5px', display: 'flex', gap: '5px' }}>
                    <span title="Edit" onClick={(e) => { e.stopPropagation(); window.open('/wp-admin/post.php?post=' + item.id + '&action=edit', '_blank'); }} style={{ cursor: 'pointer', fontSize: '12px' }}>✏️</span>
                    <span title="Delete" onClick={handleDelete} style={{ cursor: 'pointer', fontSize: '12px' }}>🗑️</span>
                </div>
            )}
        </div>
    );
};

export default PDFCard;
