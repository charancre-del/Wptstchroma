import React, { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import Header from './Header';
import Footer from './Footer';
import DashboardGrid from './DashboardGrid';

const Dashboard = () => {
    const { user } = useAuth();
    const [year, setYear] = useState(new Date().getFullYear().toString());
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);

    const fetchData = async () => {
        setLoading(true);
        const settings = window.chromaPortalSettings;
        try {
            const res = await fetch(`${settings.root}chroma-portal/v1/content/dashboard?year=${year}`, {
                headers: {
                    'X-Portal-Token': user.token,
                    'X-WP-Nonce': settings.nonce
                }
            });
            const json = await res.json();
            setData(json);
        } catch (e) {
            console.error("Failed to fetch dashboard", e);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, [year, user.token]); // Refetch when year changes

    return (
        <div className="dashboard-container" style={{ padding: '20px' }}>
            <Header user={user} year={year} setYear={setYear} />

            {loading ? (
                <div style={{ textAlign: 'center', padding: '50px' }}>Loading...</div>
            ) : (
                <DashboardGrid data={data} refreshData={fetchData} />
            )}

            <Footer />
        </div>
    );
};

export default Dashboard;
