import React, { useState } from 'react';

const FeedbackSection = () => {
    const [isLoading, setIsLoading] = useState(true);

    return (
        <div className="view-container">
            <div className="section-header" style={{ marginBottom: '20px' }}>
                <h2>Parent Feedback</h2>
                <p className="text-sm text-gray-500">We appreciate your feedback! Please fill out the form below.</p>
            </div>

            <div className="feedback-container" style={{
                background: 'white',
                borderRadius: '16px',
                overflow: 'hidden',
                boxShadow: '0 4px 15px rgba(0,0,0,0.05)',
                position: 'relative',
                minHeight: '800px'
            }}>
                {isLoading && (
                    <div style={{
                        position: 'absolute',
                        top: 0,
                        left: 0,
                        right: 0,
                        bottom: 0,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        background: '#f8f9fa'
                    }}>
                        <div className="spinner" style={{
                            width: '40px',
                            height: '40px',
                            border: '4px solid #f3f3f3',
                            borderTop: '4px solid #263238',
                            borderRadius: '50%',
                            animation: 'spin 1s linear infinite'
                        }}></div>
                    </div>
                )}

                <iframe
                    src="https://docs.google.com/forms/d/e/1FAIpQLSfpzvZje8ST9pLT_RDOFcfGLUMCpX4vbh71GLKgJkjw3m2Pbg/viewform?embedded=true"
                    width="100%"
                    height="800"
                    frameBorder="0"
                    marginHeight="0"
                    marginWidth="0"
                    onLoad={() => setIsLoading(false)}
                    style={{
                        opacity: isLoading ? 0 : 1,
                        transition: 'opacity 0.3s ease'
                    }}
                    title="Parent Feedback Form"
                >
                    Loading…
                </iframe>
            </div>
        </div>
    );
};

export default FeedbackSection;
