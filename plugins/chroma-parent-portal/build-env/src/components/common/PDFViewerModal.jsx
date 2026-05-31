import React from 'react';

const PDFViewerModal = ({ file, onClose }) => {
    if (!file) return null;

    const pdfUrl = file.pdf_url || file.url || '';
    const viewerUrl = pdfUrl ? `${pdfUrl}#toolbar=1&navpanes=0&view=FitH` : '';

    return (
        <div className="modal-overlay" style={{
            position: 'fixed',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            background: 'rgba(15, 23, 42, 0.95)',
            backdropFilter: 'blur(8px)',
            zIndex: 999999,
            display: 'flex',
            flexDirection: 'column'
        }}>
            <div className="pdf-toolbar" style={{
                width: '100%',
                padding: '12px 24px',
                background: 'rgba(255, 255, 255, 0.05)',
                borderBottom: '1px solid rgba(255, 255, 255, 0.1)',
                display: 'flex',
                justifyContent: 'space-between',
                alignItems: 'center',
                color: 'white'
            }}>
                <div style={{ display: 'flex', flexDirection: 'column' }}>
                    <span style={{ fontSize: '14px', fontWeight: '700', letterSpacing: '0.01em' }}>{file.title}</span>
                    <span style={{ fontSize: '12px', color: 'rgba(255,255,255,0.6)' }}>PDF document</span>
                </div>
                <div style={{ display: 'flex', gap: '12px', alignItems: 'center' }}>
                    <a href={pdfUrl} download className="portal-btn" style={{
                        fontSize: '12px',
                        textDecoration: 'none',
                        background: 'white',
                        color: '#0f172a',
                        padding: '8px 16px',
                        borderRadius: '8px',
                        fontWeight: '600',
                        transition: 'all 0.2s'
                    }}>Download</a>
                    <button onClick={onClose} style={{
                        background: 'rgba(255,255,255,0.1)',
                        border: 'none',
                        color: 'white',
                        fontSize: '20px',
                        width: '36px',
                        height: '36px',
                        borderRadius: '50%',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        cursor: 'pointer',
                        transition: 'background 0.2s'
                    }}>x</button>
                </div>
            </div>

            <div className="pdf-viewport" style={{
                flex: 1,
                padding: 0,
                display: 'flex',
                background: '#0f172a'
            }}>
                {viewerUrl ? (
                    <iframe
                        title={file.title || 'PDF preview'}
                        src={viewerUrl}
                        style={{
                            width: '100%',
                            height: '100%',
                            border: 0,
                            background: '#fff'
                        }}
                    />
                ) : (
                    <div style={{
                        color: 'white',
                        width: '100%',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        padding: '40px',
                        textAlign: 'center'
                    }}>
                        This document does not have a PDF preview URL.
                    </div>
                )}
            </div>
        </div>
    );
};

export default PDFViewerModal;
