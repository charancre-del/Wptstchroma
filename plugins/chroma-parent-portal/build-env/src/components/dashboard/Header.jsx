import React from 'react';
import { useAuth } from '../../context/AuthContext';

const Header = ({ user, year, setYear }) => {
    const { logout } = useAuth();
    const currentYear = new Date().getFullYear();
    const years = [currentYear + 1, currentYear, currentYear - 1]; // e.g. 2027, 2026, 2025

    return (
        <div className="portal-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '30px', paddingBottom: '20px', borderBottom: '1px solid rgba(255,255,255,0.3)' }}>
            <div className="left">
                <h1 style={{ margin: 0, color: '#333' }}>Parent Portal</h1>
                <span className="subtitle" style={{ color: '#666' }}>Chroma ELA</span>
            </div>

            <div className="right" style={{ display: 'flex', alignItems: 'center', gap: '20px' }}>
                <select
                    value={year}
                    onChange={(e) => setYear(e.target.value)}
                    style={{ padding: '8px', borderRadius: '10px', border: '1px solid #ddd' }}
                >
                    {years.map(y => <option key={y} value={y}>{y}-{y + 1} School Year</option>)}
                    {/* Assuming School Year logic? Or just Calendar Year? Task said "Year". Let's assume Calendar Year for simplicity as requested, but labelling implies school year is often expected. I'll stick to simple value. */}
                </select>

                <div className="user-profile" style={{ textAlign: 'right' }}>
                    <div style={{ fontWeight: 'bold' }}>{user.name}</div>
                    <button onClick={logout} style={{ background: 'none', border: 'none', color: '#6a11cb', cursor: 'pointer', fontSize: '12px' }}>Logout</button>
                </div>
            </div>
        </div>
    );
};

export default Header;
