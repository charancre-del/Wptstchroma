import React from 'react';
import { useAuth } from '../../context/AuthContext';

const Header = ({ user, year, setYear }) => {
    const { logout } = useAuth();
    const currentYear = new Date().getFullYear();
    const years = [currentYear + 1, currentYear, currentYear - 1]; // e.g. 2027, 2026, 2025

    return (
        <div className="header-top">
            <div className="brand">
                <h1>Parent Portal</h1>
                <p>Chroma ELA</p>
            </div>

            <div className="right" style={{ display: 'flex', alignItems: 'center', gap: '20px' }}>
                <select
                    value={year}
                    onChange={(e) => setYear(e.target.value)}
                    className="glass-select"
                    style={{ padding: '10px 15px', borderRadius: '12px', border: '1px solid rgba(0,0,0,0.1)', background: 'white', fontWeight: '600' }}
                >
                    {years.map(y => <option key={y} value={y}>{y}-{y + 1} School Year</option>)}
                </select>

                <div className="user-profile">
                    <div className="name" style={{ fontWeight: '700', fontSize: '1.1rem' }}>{user.name}</div>
                    <button onClick={logout} className="logout-btn" style={{ background: 'none', border: 'none', color: '#6366f1', cursor: 'pointer', fontSize: '0.9rem', fontWeight: '600', padding: 0 }}>Logout</button>
                </div>
            </div>
        </div>
    );
};

export default Header;
