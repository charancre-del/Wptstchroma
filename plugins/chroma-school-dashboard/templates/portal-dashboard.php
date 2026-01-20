<?php
/**
 * Director Portal Template
 * Served via /portal/ endpoint
 */
$api_url = get_rest_url();
$google_client_id = trim(get_option('chroma_google_client_id', ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Director Portal | Chroma Early Learning</title>
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'], serif: ['Playfair Display', 'serif'] },
                    colors: {
                        brand: { ink: '#263238', cream: '#FFFCF8' },
                        chroma: { red: '#D67D6B', blue: '#4A6C7C', blueDark: '#2F4858', yellow: '#E6BE75' }
                    }
                }
            }
        }
    </script>

    <!-- React & Babel -->
    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    
    <!-- Google Identity -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body { background-color: #FFFCF8; color: #263238; }
        .shim-fade { animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="selection:bg-chroma-yellow/30">

    <div id="root"></div>

    <script type="text/babel">
        const { useState, useEffect, useRef } = React;
        const API_URL = "<?php echo esc_url($api_url); ?>";
        const GOOGLE_CLIENT_ID = "<?php echo esc_js($google_client_id); ?>";

        // --- COMPONENTS ---

        const ImagePreview = ({ url }) => {
            if (!url) return null;
            return (
                <div className="mt-2 w-full h-32 rounded-xl border border-gray-200 overflow-hidden bg-gray-50 flex items-center justify-center relative group">
                    <img src={url} alt="Preview" className="w-full h-full object-cover" 
                         onError={(e) => { e.target.style.display='none'; e.target.parentElement.classList.add('bg-red-50'); }} />
                    <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold">
                        Preview
                    </div>
                </div>
            );
        };

        const Toast = ({ msg, type }) => {
            if (!msg) return null;
            const colors = type === 'error' ? 'bg-red-500' : 'bg-green-500';
            return (
                <div className={`fixed bottom-6 right-6 ${colors} text-white px-6 py-3 rounded-xl shadow-2xl font-bold z-50 animate-fade-in flex items-center gap-3`}>
                    {type === 'error' ? <i className="fa-solid fa-triangle-exclamation"></i> : <i className="fa-solid fa-check"></i>}
                    {msg}
                </div>
            );
        };

        // --- MAIN APP ---

        function App() {
            const [user, setUser] = useState(null); 
            const [school, setSchool] = useState(null);
            const [loading, setLoading] = useState(true);
            const [saving, setSaving] = useState(false);
            const [toast, setToast] = useState(null);
            const [loginError, setLoginError] = useState(null); // New Error State

            // Init: Check Session
            useEffect(() => {
                const token = localStorage.getItem('chroma_token');
                const schoolId = localStorage.getItem('chroma_school_id');

                if (token && schoolId) {
                   fetchSchool(token, schoolId);
                } else {
                    setLoading(false);
                }
            }, []);

            const showToast = (msg, type='success') => {
                setToast({ msg, type });
                setTimeout(() => setToast(null), 3000);
            };

            const fetchSchool = async (token, id) => {
                try {
                    setLoading(true);
                    const res = await fetch(`${API_URL}chroma/v1/portal/me`, {
                        headers: { 'Authorization': `Bearer ${token}` }
                    });
                    
                    if (res.status === 401) {
                        logout();
                        return;
                    }

                    if (!res.ok) throw new Error('Failed to load');
                    const data = await res.json();
                    setUser({ token, schoolId: id, email: localStorage.getItem('chroma_email') });
                    setSchool(data); 
                } catch (err) {
                    console.error(err);
                    showToast('Session expired or invalid', 'error');
                    logout();
                } finally {
                    setLoading(false);
                }
            };

            const logout = () => {
                localStorage.removeItem('chroma_token');
                localStorage.removeItem('chroma_school_id');
                localStorage.removeItem('chroma_email');
                setUser(null);
                setSchool(null);
                setLoading(false);
                setLoginError(null);
            };

            const handleGoogleLogin = async (response) => {
                setLoading(true);
                setLoginError(null);
                try {
                    const res = await fetch(`${API_URL}chroma/v1/auth/google`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ token: response.credential })
                    });
                    const data = await res.json();

                    if (!res.ok) throw new Error(data.message || 'Login failed');

                    localStorage.setItem('chroma_token', data.token);
                    localStorage.setItem('chroma_school_id', data.school_id);
                    localStorage.setItem('chroma_email', data.director_email);
                    
                    fetchSchool(data.token, data.school_id);

                } catch (err) {
                    console.error(err);
                    setLoginError(err.message); // Show persistent error
                    setLoading(false);
                }
            };

            // Setup Google Button
            useEffect(() => {
                if (!user && !loading && window.google) {
                    // Re-render button logic if needed or just letting library handle it
                    try {
                        window.google.accounts.id.initialize({
                            client_id: GOOGLE_CLIENT_ID,
                            callback: handleGoogleLogin
                        });
                        window.google.accounts.id.renderButton(
                            document.getElementById("googleBtn"),
                            { theme: "outline", size: "large", width: 300 }
                        );
                    } catch(e) { console.error("Google Btn Error", e); }
                }
            }, [user, loading, loginError]); // Re-run if error clears/sets to ensure button is there

            // ... handleSave ...

            const handleSave = async (e) => {
                e.preventDefault();
                setSaving(true);
                
                // Construct payload from form
                const formData = new FormData(e.target);
                
                // Process Slideshow (Array)
                const rawObj = Object.fromEntries(formData.entries());
                const slides = [];
                for(let k in rawObj) {
                    if (k.startsWith('slideshow_img_')) slides.push(rawObj[k]);
                }
                
                const finalPayload = {
                    eom: {
                        name: rawObj['eom.name'],
                        role: rawObj['eom.role'],
                        photo_url: rawObj['eom.photo_url'],
                        blurb: rawObj['eom.blurb']
                    },
                    newsletter: {
                        title: rawObj['newsletter.title'],
                        body: rawObj['newsletter.body'],
                        url: rawObj['newsletter.url']
                    },
                    slideshow_title: rawObj['slideshow_title'],
                    slideshow: slides.filter(s => s.trim().length > 0),
                    chroma_cares: {
                        title: rawObj['chroma_cares.title'],
                        body: rawObj['chroma_cares.body']
                    },
                    celebrations: [ 
                         rawObj['cel_0'], rawObj['cel_1'], rawObj['cel_2'], rawObj['cel_3']
                    ]
                };

                try {
                    const res = await fetch(`${API_URL}chroma/v1/portal/school/${user.schoolId}`, {
                        method: 'PATCH',
                        headers: { 
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${user.token}` 
                        },
                        body: JSON.stringify(finalPayload)
                    });

                    if (res.status === 401) { logout(); return; }
                    if (!res.ok) throw new Error('Save failed');

                    showToast('Dashboard Updated!');
                    fetchSchool(user.token, user.schoolId);

                } catch (err) {
                    showToast('Error saving changes', 'error');
                } finally {
                    setSaving(false);
                }
            };
            
            // --- RENDER ---

            if (loading) return (
                <div className="h-screen w-screen flex items-center justify-center bg-brand-cream text-brand-ink">
                    <i className="fa-solid fa-circle-notch fa-spin text-4xl text-chroma-red"></i>
                </div>
            );

            if (!user) return (
                <div className="h-screen w-screen flex flex-col items-center justify-center bg-brand-cream p-6">
                    <div className="max-w-md w-full text-center">
                        <div className="mb-8 flex justify-center text-chroma-blue text-5xl"><i className="fa-solid fa-shapes"></i></div>
                        <h1 className="font-serif text-4xl font-bold mb-2">Director Portal</h1>
                        <p className="text-brand-ink/60 mb-10">Sign in to manage your school's TV dashboard.</p>
                        
                        <div className="bg-white p-8 rounded-3xl shadow-soft border border-chroma-blue/10">
                            {GOOGLE_CLIENT_ID ? (
                                <>
                                    {loginError && (
                                        <div className="mb-6 bg-red-50 border border-red-100 text-red-600 p-4 rounded-xl text-sm font-bold flex items-center gap-2 text-left">
                                            <i className="fa-solid fa-circle-exclamation text-lg"></i>
                                            <div>
                                                {loginError === 'no_school_found' ? 'No School Found' : 'Login Failed'}
                                                <div className="font-normal opacity-80 mt-1">
                                                    {loginError === 'no_school_found' 
                                                        ? "This email is not assigned to any school. Please ask an Admin to update the school settings." 
                                                        : loginError}
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                    <div id="googleBtn" className="flex justify-center"></div>
                                    <p className="mt-6 text-xs text-center text-brand-ink/40">Only authorized director emails can access.</p>
                                </>
                            ) : (
                                <div className="text-red-500 font-bold p-4 bg-red-50 rounded-xl">
                                    <i className="fa-solid fa-triangle-exclamation mr-2"></i>
                                    System Config Error
                                    <p className="text-xs text-brand-ink font-normal mt-2">
                                        Google Client ID is missing. Please ask an Admin to set it in 
                                        <strong> WP Admin &gt; Global Dashboard Settings</strong>.
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            );

            // Safe accessors
            const c = school?.content || {}; 
            const eom = c.eom || {};
            const news = c.newsletter || {};
            const cares = c.chroma_cares || {};
            const cel = c.celebrations || [];
            const slides = c.slideshow || [];

            return (
                <div className="min-h-screen bg-brand-cream pb-20">
                    {toast && <Toast msg={toast.msg} type={toast.type} />}
                    
                    {/* Header */}
                    <header className="bg-white border-b border-chroma-blue/10 sticky top-0 z-40 shadow-sm">
                        <div className="max-w-5xl mx-auto px-6 h-20 flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="text-chroma-red text-2xl"><i className="fa-solid fa-shapes"></i></div>
                                <div>
                                    <h1 className="font-bold text-lg leading-none">{school.title}</h1>
                                    <p className="text-xs text-brand-ink/50 uppercase tracking-wider font-bold">TV Dashboard Editor</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-4">
                                <a href={`/tv/${school.slug}`} target="_blank" className="text-sm font-bold text-chroma-blue hover:underline">
                                    <i className="fa-solid fa-tv mr-2"></i>View TV
                                </a>
                                <button onClick={logout} className="p-2 text-brand-ink/40 hover:text-chroma-red transition">
                                    <i className="fa-solid fa-right-from-bracket"></i>
                                </button>
                            </div>
                        </div>
                    </header>

                    {/* Form */}
                    <main className="max-w-3xl mx-auto mt-10 px-6">
                        <form onSubmit={handleSave} className="space-y-10 shim-fade">
                            
                            {/* Star Educator */}
                            <section className="bg-white rounded-[2rem] p-8 shadow-card border border-chroma-blue/5">
                                <div className="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                                    <div className="w-10 h-10 rounded-full bg-chroma-yellow/20 flex items-center justify-center text-chroma-yellow text-xl"><i className="fa-solid fa-star"></i></div>
                                    <h2 className="font-serif text-2xl font-bold">Star Educator</h2>
                                </div>
                                <div className="grid md:grid-cols-2 gap-6">
                                    <div>
                                        <label className="block text-xs font-bold uppercase mb-1">Educator Name</label>
                                        <input name="eom.name" defaultValue={eom.name} className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50 focus:border-chroma-blue outline-none transition" />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-bold uppercase mb-1">Role / Classroom</label>
                                        <input name="eom.role" defaultValue={eom.role} className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50 focus:border-chroma-blue outline-none transition" />
                                    </div>
                                    <div className="md:col-span-2">
                                        <label className="block text-xs font-bold uppercase mb-1">Photo URL</label>
                                        <input name="eom.photo_url" defaultValue={eom.photo_url} className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50 focus:border-chroma-blue outline-none transition" placeholder="https://..." />
                                        <ImagePreview url={eom.photo_url} /> 
                                        {/* Note: Auto-preview needs state. For MVP Native, we might skip live keyup preview unless we add state for every field. 
                                            Let's keep it simple: preview only initial or assume user hits save. 
                                            Actually, to be PRO, let's create a 'Watch' wrapper? 
                                            For this snippet, I'll rely on browser default value behavior, so preview won't update until save. 
                                            That's an acceptable trade-off for "Native Single File". */}
                                    </div>
                                    <div className="md:col-span-2">
                                        <label className="block text-xs font-bold uppercase mb-1">Blurb / Quote</label>
                                        <textarea name="eom.blurb" defaultValue={eom.blurb} rows="2" className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50 focus:border-chroma-blue outline-none transition"></textarea>
                                    </div>
                                </div>
                            </section>

                            {/* Slideshow */}
                            <section className="bg-white rounded-[2rem] p-8 shadow-card border border-chroma-blue/5">
                                <div className="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                                    <div className="w-10 h-10 rounded-full bg-chroma-red/20 flex items-center justify-center text-chroma-red text-xl"><i className="fa-solid fa-images"></i></div>
                                    <h2 className="font-serif text-2xl font-bold">Slideshow</h2>
                                </div>
                                <div className="space-y-6">
                                    <div>
                                        <label className="block text-xs font-bold uppercase mb-1">Section Title</label>
                                        <input name="slideshow_title" defaultValue={c.slideshow_title || 'Highlights'} className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50" />
                                    </div>
                                    <div className="space-y-4">
                                        <label className="block text-xs font-bold uppercase">Image URLs (Max 5)</label>
                                        {[0,1,2,3,4].map(i => (
                                            <input key={i} name={`slideshow_img_${i}`} defaultValue={slides[i] || ''} placeholder={`Image URL ${i+1}`} className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50 text-sm font-mono" />
                                        ))}
                                    </div>
                                </div>
                            </section>

                            {/* Newsletter */}
                            <section className="bg-white rounded-[2rem] p-8 shadow-card border border-chroma-blue/5">
                                <div className="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                                    <div className="w-10 h-10 rounded-full bg-chroma-blue/20 flex items-center justify-center text-chroma-blue text-xl"><i className="fa-solid fa-newspaper"></i></div>
                                    <h2 className="font-serif text-2xl font-bold">Newsletter</h2>
                                </div>
                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-xs font-bold uppercase mb-1">Headline</label>
                                        <input name="newsletter.title" defaultValue={news.title} className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50" />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-bold uppercase mb-1">Short Summary</label>
                                        <textarea name="newsletter.body" defaultValue={news.body} rows="3" className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50"></textarea>
                                    </div>
                                    <div>
                                        <label className="block text-xs font-bold uppercase mb-1">Link URL (Generates QR Code)</label>
                                        <input name="newsletter.url" defaultValue={news.url} placeholder="https://" className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50" />
                                    </div>
                                </div>
                            </section>

                             {/* Chroma Cares */}
                             <section className="bg-white rounded-[2rem] p-8 shadow-card border border-chroma-blue/5">
                                <div className="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                                    <div className="w-10 h-10 rounded-full bg-chroma-green/20 flex items-center justify-center text-chroma-green text-xl"><i className="fa-solid fa-heart"></i></div>
                                    <h2 className="font-serif text-2xl font-bold">Chroma Cares</h2>
                                </div>
                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-xs font-bold uppercase mb-1">Title</label>
                                        <input name="chroma_cares.title" defaultValue={cares.title} className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50" />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-bold uppercase mb-1">Body</label>
                                        <textarea name="chroma_cares.body" defaultValue={cares.body} rows="3" className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50"></textarea>
                                    </div>
                                </div>
                            </section>

                            {/* Save Button */}
                            <div className="fixed bottom-0 left-0 right-0 p-6 bg-white/80 backdrop-blur-md border-t border-gray-200 flex justify-center z-30">
                                <button disabled={saving} className="bg-chroma-blue text-white font-bold text-lg px-12 py-4 rounded-2xl shadow-xl hover:bg-chroma-blueDark transition-transform active:scale-95 flex items-center gap-3">
                                    {saving ? <i className="fa-solid fa-circle-notch fa-spin"></i> : <i className="fa-solid fa-floppy-disk"></i>}
                                    {saving ? 'Saving...' : 'Save Changes'}
                                </button>
                            </div>
                            <div className="h-24"></div>
                        </form>
                    </main>
                </div>
            );
        }

        const root = ReactDOM.createRoot(document.getElementById('root'));
        root.render(<App />);
    </script>
</body>
</html>