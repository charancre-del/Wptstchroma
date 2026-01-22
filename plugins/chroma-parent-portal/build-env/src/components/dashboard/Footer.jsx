import React from 'react';

const Footer = () => {
    return (
        <div className="portal-footer" style={{ marginTop: '50px', borderTop: '1px solid rgba(0,0,0,0.05)', paddingTop: '20px', textAlign: 'center', fontSize: '12px', color: '#999' }}>
            <p>Copyright © {new Date().getFullYear()} Chroma ELA LLC. All rights reserved.</p>
            <p style={{ color: '#d9534f' }}>Unauthorized distribution of materials found on this portal is strictly prohibited.</p>
        </div>
    );
};

export default Footer;
