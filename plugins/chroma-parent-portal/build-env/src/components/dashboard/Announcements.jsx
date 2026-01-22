import React from 'react';

const Announcements = ({ items, isAdmin, onAdd, onDelete }) => {
    if (!items || (items.length === 0 && !isAdmin)) return null;

    const handleDelete = async (id) => {
        if (!window.confirm('Delete this announcement?')) return;
        try {
            const settings = window.chromaPortalSettings;
            await fetch(`${settings.root}chroma-portal/v1/content/delete/${id}`, {
                method: 'DELETE',
                headers: { 'X-WP-Nonce': settings.nonce }
            });
            onDelete();
        } catch (e) { alert('Failed to delete'); }
    };

    return (
        <div className="announcements-bar" style={{ marginBottom: '30px' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <h4 style={{ margin: '0 0 10px 0', color: '#ff6b6b' }}>Important Updates</h4>
                {isAdmin && <button onClick={onAdd} style={{ fontSize: '10px' }}>+ Add Alert</button>}
            </div>

            {items.length === 0 ? <p>No current alerts.</p> : (
                <div className="alerts-list">
                    {items.map(item => (
                        <div key={item.id} className="alert-item" style={{
                            background: item.priority === 'high' ? 'rgba(255, 107, 107, 0.15)' : 'rgba(255,255,255,0.5)',
                            borderLeft: `4px solid ${item.priority === 'high' ? '#ff6b6b' : '#2575fc'}`,
                            padding: '10px',
                            marginBottom: '10px',
                            borderRadius: '4px',
                            position: 'relative'
                        }}>
                            <strong>{item.title}</strong>
                            <div dangerouslySetInnerHTML={{ __html: item.content }} />
                            {isAdmin && (
                                <span
                                    onClick={() => handleDelete(item.id)}
                                    style={{ position: 'absolute', top: '5px', right: '5px', cursor: 'pointer', fontSize: '10px' }}
                                >
                                    🗑️
                                </span>
                            )}
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
};

export default Announcements;
