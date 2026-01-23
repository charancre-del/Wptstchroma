import React, { useState, useEffect } from 'react';
import { Document, Page, pdfjs } from 'react-pdf';
import 'react-pdf/dist/esm/Page/AnnotationLayer.css';
import 'react-pdf/dist/esm/Page/TextLayer.css';

// Configure Worker (Global)
pdfjs.GlobalWorkerOptions.workerSrc = `//cdnjs.cloudflare.com/ajax/libs/pdf.js/${pdfjs.version}/pdf.worker.min.js`;

const PDFViewerModal = ({ file, onClose }) => {
    const [numPages, setNumPages] = useState(null);
    const [pageNumber, setPageNumber] = useState(1);
    const [scale, setScale] = useState(1.0);

    if (!file) return null;

    const onDocumentLoadSuccess = ({ numPages }) => {
        setNumPages(numPages);
    };

    // Calculate optimal scale based on window width
    useEffect(() => {
        const width = window.innerWidth;
        if (width < 600) setScale(0.6);
        else if (width < 900) setScale(0.8);
        else setScale(1.0);
    }, []);

    return (
        <div className="modal-overlay" style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0,0,0,0.85)', zIndex: 2000, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center' }}>

            {/* Toolbar */}
            <div className="pdf-toolbar" style={{ width: '100%', padding: '15px', background: 'rgba(0,0,0,0.5)', display: 'flex', justifyContent: 'space-between', color: 'white', position: 'absolute', top: 0 }}>
                <div>
                    <strong>{file.title}</strong>
                    <span style={{ marginLeft: '10px', color: '#ccc' }}>Page {pageNumber} of {numPages}</span>
                </div>
                <div style={{ display: 'flex', gap: '15px' }}>
                    <a href={file.pdf_url} download className="portal-btn" style={{ fontSize: '12px', textDecoration: 'none', background: 'white', color: '#333' }}>Download PDF</a>
                    <button onClick={onClose} style={{ background: 'none', border: 'none', color: 'white', fontSize: '24px', cursor: 'pointer' }}>×</button>
                </div>
            </div>

            {/* Viewer Container */}
            <div className="pdf-container" style={{ overflowY: 'auto', height: '100%', width: '100%', display: 'flex', justifyContent: 'center', paddingTop: '60px' }}>
                <Document
                    file={file.pdf_url}
                    onLoadSuccess={onDocumentLoadSuccess}
                    loading={<div style={{ color: 'white' }}>Loading PDF...</div>}
                >
                    <Page
                        pageNumber={pageNumber}
                        scale={scale}
                        renderTextLayer={false}
                        devicePixelRatio={window.devicePixelRatio || 1} // High DPI
                    />
                </Document>
            </div>

            {/* Pagination Controls (if needed) */}
            {numPages > 1 && (
                <div style={{ position: 'absolute', bottom: '20px', background: 'white', padding: '10px', borderRadius: '20px', display: 'flex', gap: '10px' }}>
                    <button disabled={pageNumber <= 1} onClick={() => setPageNumber(prev => prev - 1)}>Prev</button>
                    <span>{pageNumber} / {numPages}</span>
                    <button disabled={pageNumber >= numPages} onClick={() => setPageNumber(prev => prev + 1)}>Next</button>
                </div>
            )}
        </div>
    );
};

export default PDFViewerModal;
