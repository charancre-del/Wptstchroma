import React, { useState, useEffect } from 'react';
import { Document, Page, pdfjs } from 'react-pdf';
import 'react-pdf/dist/esm/Page/AnnotationLayer.css';
import 'react-pdf/dist/esm/Page/TextLayer.css';

// Configure Worker (Global)
pdfjs.GlobalWorkerOptions.workerSrc = `//cdnjs.cloudflare.com/ajax/libs/pdf.js/${pdfjs.version}/pdf.worker.min.js`;

const PDFViewerModal = ({ file, onClose }) => {
    // Viewer state
    const [numPages, setNumPages] = useState(null);
    const [pageNumber, setPageNumber] = useState(1);
    const containerRef = React.useRef(null);
    const [containerWidth, setContainerWidth] = useState(0);

    // Calculate container width for responsive PDF
    useEffect(() => {
        const updateWidth = () => {
            if (containerRef.current) {
                setContainerWidth(containerRef.current.clientWidth);
            }
        };

        updateWidth();
        window.addEventListener('resize', updateWidth);
        return () => window.removeEventListener('resize', updateWidth);
    }, [file]);

    if (!file) return null;

    const onDocumentLoadSuccess = ({ numPages }) => {
        setNumPages(numPages);
    };

    return (
        <div className="modal-overlay" style={{
            position: 'fixed',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            background: 'rgba(15, 23, 42, 0.95)', // Deep dark slate
            backdropFilter: 'blur(8px)',
            zIndex: 999999,
            display: 'flex',
            flexDirection: 'column'
        }}>

            {/* Premium Header */}
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
                    <span style={{ fontSize: '12px', color: 'rgba(255,255,255,0.6)' }}>Page {pageNumber} of {numPages}</span>
                </div>
                <div style={{ display: 'flex', gap: '12px', alignItems: 'center' }}>
                    <a href={file.pdf_url} download className="portal-btn" style={{
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
                    }}>×</button>
                </div>
            </div>

            {/* Viewer Container - Scrollable */}
            <div
                className="pdf-viewport"
                ref={containerRef}
                style={{
                    flex: 1,
                    overflowY: 'auto',
                    padding: '0',
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    background: '#0f172a'
                }}
            >
                <div style={{ width: '100%', display: 'flex', justifyContent: 'center' }}>
                    <Document
                        file={file.pdf_url}
                        onLoadSuccess={onDocumentLoadSuccess}
                        loading={<div style={{ color: 'white', marginTop: '100px' }}>Loading Document...</div>}
                    >
                        <Page
                            pageNumber={pageNumber}
                            width={containerWidth}
                            renderTextLayer={false}
                            renderAnnotationLayer={false}
                            devicePixelRatio={Math.min(2, window.devicePixelRatio || 1)}
                        />
                    </Document>
                </div>
            </div>

            {/* Pagination Controls Footer */}
            {numPages > 1 && (
                <div style={{
                    padding: '16px',
                    background: 'rgba(15, 23, 42, 0.8)',
                    borderTop: '1px solid rgba(255, 255, 255, 0.05)',
                    display: 'flex',
                    justifyContent: 'center',
                    gap: '20px',
                    alignItems: 'center',
                    color: 'white'
                }}>
                    <button
                        disabled={pageNumber <= 1}
                        onClick={() => setPageNumber(prev => Math.max(1, prev - 1))}
                        style={{
                            background: pageNumber <= 1 ? 'rgba(255,255,255,0.05)' : 'rgba(255,255,255,0.1)',
                            border: 'none',
                            color: 'white',
                            padding: '8px 16px',
                            borderRadius: '8px',
                            cursor: pageNumber <= 1 ? 'default' : 'pointer'
                        }}
                    >
                        Previous
                    </button>
                    <span style={{ fontSize: '14px', fontWeight: '500' }}>Page {pageNumber} / {numPages}</span>
                    <button
                        disabled={pageNumber >= numPages}
                        onClick={() => setPageNumber(prev => Math.min(numPages, prev + 1))}
                        style={{
                            background: pageNumber >= numPages ? 'rgba(255,255,255,0.05)' : 'rgba(255,255,255,0.1)',
                            border: 'none',
                            color: 'white',
                            padding: '8px 16px',
                            borderRadius: '8px',
                            cursor: pageNumber >= numPages ? 'default' : 'pointer'
                        }}
                    >
                        Next
                    </button>
                </div>
            )}
        </div>
    );
};

export default PDFViewerModal;
