<?php
/**
 * Director Portal Template
 */
$api_url = get_rest_url();
$google_client_id = trim(get_option('chroma_google_client_id', ''));

// Logic: Enable WordPress Media Library for this page
wp_enqueue_media();

// Enqueue the admin CSS that styles the media modal
wp_enqueue_style('media-views');
wp_enqueue_style('imgareaselect');
wp_enqueue_style('dashicons');
wp_enqueue_style('buttons');
wp_enqueue_style('wp-mediaelement');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Director Portal | Chroma Early Learning</title>
    <meta name="robots" content="noindex, nofollow">

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

    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background-color: #FFFCF8;
            color: #263238;
        }

        .shim-fade {
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .shadow-soft {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
        }

        .shadow-card {
            box-shadow: 0 20px 50px rgba(38, 50, 56, 0.05);
        }

        /* Hide WP Admin Bar and any theme elements injected by wp_head() */
        #wpadminbar,
        .site-header,
        .site-footer,
        .wp-block-template-part {
            display: none !important;
        }

        html {
            margin-top: 0 !important;
        }
    </style>
    <?php wp_head(); ?>
</head>

<body class="selection:bg-chroma-yellow/30">

    <div id="root"></div>

    <script type="text/babel">
        const { useState, useEffect, useRef } = React;
        const API_URL = "<?php echo esc_url($api_url); ?>";
        const GOOGLE_CLIENT_ID = "<?php echo esc_js($google_client_id); ?>";

        // --- COMPONENTS ---

        const MediaUploader = ({ value, onChange, label }) => {
            const openMedia = () => {
                const frame = wp.media({
                    title: 'Select Image',
                    multiple: false,
                    library: { type: 'image' }
                });

                frame.on('select', () => {
                    const attachment = frame.state().get('selection').first().toJSON();
                    onChange(attachment.url);
                });

                frame.open();
            };

            return (
                <div className="space-y-2">
                    <label className="block text-xs font-bold uppercase tracking-wider text-brand-ink/50">{label}</label>
                    <div className="flex items-center gap-4">
                        <div onClick={openMedia} className="flex-1 cursor-pointer group">
                            <div className="w-full p-4 rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50/50 group-hover:border-chroma-blue group-hover:bg-chroma-blue/5 transition-all flex flex-col items-center justify-center min-h-[140px]">
                                {value ? (
                                    <div className="relative w-full h-full flex flex-col items-center">
                                        <img src={value} className="h-24 w-24 object-cover rounded-xl shadow-sm mb-3" />
                                        <span className="text-xs font-bold text-chroma-blue">Change Image</span>
                                    </div>
                                ) : (
                                    <>
                                        <i className="fa-solid fa-cloud-arrow-up text-3xl text-gray-300 mb-2"></i>
                                        <span className="text-xs font-bold text-gray-400 uppercase">Upload or Select Image</span>
                                    </>
                                )}
                            </div>
                        </div>
                        {value && (
                            <button type="button" onClick={() => onChange('')} className="p-3 text-red-400 hover:text-red-600 transition">
                                <i className="fa-solid fa-trash"></i>
                            </button>
                        )}
                    </div>
                    {/* Hidden input for form capture if using native FormData */}
                    <input type="hidden" name={label.toLowerCase().replace(' ', '_')} value={value} />
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

        const FormSection = ({ title, icon, colorClass, children }) => (
            <section className="bg-white rounded-[2.5rem] p-10 shadow-card border border-chroma-blue/5 relative overflow-hidden">
                <div className={`absolute top-0 left-0 w-2 h-full ${colorClass}`}></div>
                <div className="flex items-center gap-4 mb-8">
                    <div className={`w-12 h-12 rounded-2xl ${colorClass} bg-opacity-10 flex items-center justify-center text-2xl ${colorClass.replace('bg-', 'text-')}`}>
                        <i className={`fa-solid ${icon}`}></i>
                    </div>
                    <h2 className="font-serif text-3xl font-bold tracking-tight">{title}</h2>
                </div>
                {children}
            </section>
        );

        // --- MAIN APP ---

        function App() {
            const [user, setUser] = useState(null);
            const [school, setSchool] = useState(null);
            const [loading, setLoading] = useState(true);
            const [saving, setSaving] = useState(false);
            const [toast, setToast] = useState(null);
            const [loginError, setLoginError] = useState(null);

            // Field states that need direct management for Uploader or list logic
            const [formState, setFormState] = useState({
                eomPhoto: '',
                slides: ['', '', '', '', '']
            });

            useEffect(() => {
                const token = localStorage.getItem('chroma_token');
                const schoolId = localStorage.getItem('chroma_school_id');
                if (token && schoolId) fetchSchool(token, schoolId);
                else setLoading(false);
            }, []);

            const showToast = (msg, type = 'success') => {
                setToast({ msg, type });
                setTimeout(() => setToast(null), 3000);
            };

            const fetchSchool = async (token, id) => {
                try {
                    setLoading(true);
                    const res = await fetch(`${API_URL}chroma/v1/portal/me`, {
                        headers: { 'Authorization': `Bearer ${token}` }
                    });
                    if (res.status === 401) { logout(); return; }
                    if (!res.ok) throw new Error('Failed to load');
                    const data = await res.json();
                    setUser({ token, schoolId: id, email: localStorage.getItem('chroma_email') });
                    setSchool(data);

                    // Sync controlled states
                    setFormState({
                        eomPhoto: data.content?.eom?.photo_url || '',
                        newsletterPdf: data.content?.newsletter?.pdf_url || '',
                        slides: [
                            ...(data.content?.slideshow || []),
                            '', '', '', '', ''
                        ].slice(0, 5)
                    });

                } catch (err) {
                    console.error(err);
                    showToast('Session expired', 'error');
                    logout();
                } finally {
                    setLoading(false);
                }
            };

            const logout = () => {
                localStorage.clear();
                setUser(null); setSchool(null); setLoading(false); setLoginError(null);
            };

            const handleGoogleLogin = async (response) => {
                setLoading(true); setLoginError(null);
                try {
                    const res = await fetch(`${API_URL}chroma/v1/auth/google`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ token: response.credential })
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'Login failed');

                    // Store in localStorage
                    localStorage.setItem('chroma_token', data.token);
                    localStorage.setItem('chroma_school_id', data.school_id);
                    localStorage.setItem('chroma_email', data.director_email);

                    // Store session token as cookie for PHP-side permission checks
                    document.cookie = `chroma_session=${data.token}; path=/; max-age=43200; SameSite=Lax`;

                    fetchSchool(data.token, data.school_id);
                } catch (err) {
                    setLoginError(err.message);
                    setLoading(false);
                }
            };

            useEffect(() => {
                if (!user && !loading && window.google) {
                    try {
                        window.google.accounts.id.initialize({ client_id: GOOGLE_CLIENT_ID, callback: handleGoogleLogin });
                        window.google.accounts.id.renderButton(document.getElementById("googleBtn"), { theme: "outline", size: "large", width: 300 });
                    } catch (e) { }
                }
            }, [user, loading, loginError]);

            const handleSave = async (e) => {
                e.preventDefault();
                setSaving(true);
                const formData = new FormData(e.target);
                const rawObj = Object.fromEntries(formData.entries());

                const finalPayload = {
                    eom: {
                        name: rawObj['eom.name'],
                        role: rawObj['eom.role'],
                        photo_url: formState.eomPhoto,
                        blurb: rawObj['eom.blurb']
                    },
                    newsletter: {
                        title: rawObj['newsletter.title'],
                        body: rawObj['newsletter.body'],
                        url: rawObj['newsletter.url'],
                        pdf_url: formState.newsletterPdf
                    },
                    slideshow_title: rawObj['slideshow_title'],
                    slideshow: formState.slides.filter(s => s.trim().length > 0),
                    chroma_cares: {
                        title: rawObj['chroma_cares.title'],
                        body: rawObj['chroma_cares.body']
                    },
                    celebrations: [rawObj['cel_0'], rawObj['cel_1'], rawObj['cel_2'], rawObj['cel_3'], rawObj['cel_4'], rawObj['cel_5'], rawObj['cel_6'], rawObj['cel_7']].filter(Boolean),
                    announcements: [
                        { title: rawObj['notice_0_title'], body: rawObj['notice_0_body'], priority: rawObj['notice_0_priority'] || 'normal' },
                        { title: rawObj['notice_1_title'], body: rawObj['notice_1_body'], priority: rawObj['notice_1_priority'] || 'normal' }
                    ].filter(n => n.title),
                    today: {
                        title: rawObj['today.title'],
                        items: [rawObj['td_0'], rawObj['td_1'], rawObj['td_2'], rawObj['td_3']].filter(Boolean)
                    },
                    music_url: rawObj['music_url'] || ''
                };

                try {
                    const res = await fetch(`${API_URL}chroma/v1/portal/school/${user.schoolId}`, {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${user.token}` },
                        body: JSON.stringify(finalPayload)
                    });
                    if (!res.ok) throw new Error('Save failed');
                    showToast('Dashboard Updated!');
                    fetchSchool(user.token, user.schoolId);
                } catch (err) {
                    showToast('Error saving changes', 'error');
                } finally {
                    setSaving(false);
                }
            };

            if (loading) return <div className="h-screen w-screen flex items-center justify-center bg-brand-cream text-chroma-red text-4xl"><i className="fa-solid fa-circle-notch fa-spin"></i></div>;

            if (!user) return (
                <div className="h-screen w-screen flex flex-col items-center justify-center bg-brand-cream p-6">
                    <div className="max-w-md w-full text-center">
                        <div className="mb-8 flex justify-center text-chroma-blue text-6xl"><i className="fa-solid fa-shapes"></i></div>
                        <h1 className="font-serif text-5xl font-bold mb-4 tracking-tight text-brand-ink">Director Portal</h1>
                        <p className="text-brand-ink/50 text-xl font-medium mb-12">Sign in to manage your school's display.</p>

                        <div className="bg-white p-12 rounded-[2.5rem] shadow-card border border-chroma-blue/10">
                            {GOOGLE_CLIENT_ID ? (
                                <>
                                    {loginError && <div className="mb-8 bg-red-50 text-red-600 p-4 rounded-2xl text-sm font-bold shadow-sm">{loginError}</div>}
                                    <div id="googleBtn" className="flex justify-center scale-110"></div>
                                    <p className="mt-10 text-xs font-bold uppercase tracking-widest text-brand-ink/30">Authorized Directors Only</p>
                                </>
                            ) : <div className="text-red-500 font-bold p-6 bg-red-50 rounded-2xl border border-red-100">Client ID Missing</div>}
                        </div>
                    </div>
                </div>
            );

            const c = school?.content || {};
            const tdItems = c.today?.items || [];

            return (
                <div className="min-h-screen bg-brand-cream pb-32">
                    {toast && <Toast msg={toast.msg} type={toast.type} />}

                    <header className="bg-white/80 backdrop-blur-md border-b border-chroma-blue/10 sticky top-0 z-40">
                        <div className="max-w-6xl mx-auto px-8 h-24 flex items-center justify-between">
                            <div className="flex items-center gap-4">
                                <div className="text-chroma-red text-3xl"><i className="fa-solid fa-shapes"></i></div>
                                <div>
                                    <h1 className="font-bold text-xl leading-none">{school.title}</h1>
                                    <p className="text-xs text-brand-ink/40 uppercase tracking-widest font-black mt-1">Live Dashboard Controller</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-6">
                                <a href={`/tv/${school.slug}`} target="_blank" className="bg-gray-100/80 px-5 py-3 rounded-xl font-bold text-sm text-brand-ink hover:bg-gray-200 transition">View Display</a>
                                <button onClick={logout} className="p-3 text-brand-ink/30 hover:text-chroma-red transition text-xl"><i className="fa-solid fa-right-from-bracket"></i></button>
                            </div>
                        </div>
                    </header>

                    <main className="max-w-4xl mx-auto mt-16 px-8 space-y-12">
                        <form onSubmit={handleSave} className="space-y-12 shim-fade">

                            {/* Star Educator */}
                            <FormSection title="Star Educator" icon="fa-star" colorClass="bg-chroma-yellow">
                                <div className="grid md:grid-cols-2 gap-8">
                                    <div className="space-y-6">
                                        <div className="space-y-2">
                                            <label className="block text-xs font-bold uppercase tracking-wider text-brand-ink/50">Name</label>
                                            <input name="eom.name" defaultValue={c.eom?.name} className="w-full p-4 rounded-xl border-2 border-gray-100 bg-gray-50 focus:border-chroma-blue focus:bg-white outline-none font-medium transition-all" />
                                        </div>
                                        <div className="space-y-2">
                                            <label className="block text-xs font-bold uppercase tracking-wider text-brand-ink/50">Classroom / Role</label>
                                            <input name="eom.role" defaultValue={c.eom?.role} className="w-full p-4 rounded-xl border-2 border-gray-100 bg-gray-50 focus:border-chroma-blue focus:bg-white outline-none font-medium transition-all" />
                                        </div>
                                    </div>
                                    <MediaUploader label="Teacher Photo" value={formState.eomPhoto} onChange={(url) => setFormState({ ...formState, eomPhoto: url })} />
                                    <div className="md:col-span-2 space-y-2">
                                        <label className="block text-xs font-bold uppercase tracking-wider text-brand-ink/50">A Little About Them</label>
                                        <textarea name="eom.blurb" defaultValue={c.eom?.blurb} rows="3" className="w-full p-4 rounded-xl border-2 border-gray-100 bg-gray-50 focus:border-chroma-blue focus:bg-white outline-none font-medium transition-all" />
                                    </div>
                                </div>
                            </FormSection>

                            {/* Today's Schedule */}
                            <FormSection title="Today's Schedule" icon="fa-calendar-day" colorClass="bg-chroma-blue">
                                <div className="space-y-6">
                                    <div className="space-y-2">
                                        <label className="block text-xs font-bold uppercase tracking-wider text-brand-ink/50">Header (e.g. Activity Schedule)</label>
                                        <input name="today.title" defaultValue={c.today?.title || 'Daily Schedule'} className="w-full p-4 rounded-xl border-2 border-gray-100 bg-gray-50 font-bold" />
                                    </div>
                                    <div className="grid md:grid-cols-2 gap-4">
                                        {[0, 1, 2, 3].map(i => (
                                            <input key={i} name={`td_${i}`} defaultValue={tdItems[i] || ''} placeholder={`Item ${i + 1}`} className="w-full p-4 rounded-xl border-2 border-gray-100 bg-gray-50 text-sm font-medium" />
                                        ))}
                                    </div>
                                </div>
                            </FormSection>

                            {/* Notices / Announcements */}
                            <FormSection title="Notices & Announcements" icon="fa-bullhorn" colorClass="bg-chroma-yellow">
                                <div className="space-y-6">
                                    {[0, 1].map(i => (
                                        <div key={i} className="p-6 bg-gray-50 rounded-2xl border border-gray-100 space-y-4">
                                            <div className="flex items-center justify-between">
                                                <span className="text-xs font-black uppercase tracking-widest text-brand-ink/30">Notice {i + 1}</span>
                                                <label className="flex items-center gap-2 text-xs font-bold">
                                                    <input type="checkbox" name={`notice_${i}_priority`} value="high" defaultChecked={c.announcements?.[i]?.priority === 'high'} className="accent-chroma-red w-4 h-4" />
                                                    <span className="text-red-500">Important</span>
                                                </label>
                                            </div>
                                            <input name={`notice_${i}_title`} defaultValue={c.announcements?.[i]?.title || ''} placeholder="Headline" className="w-full p-4 rounded-xl border-2 border-gray-100 bg-white font-bold" />
                                            <textarea name={`notice_${i}_body`} defaultValue={c.announcements?.[i]?.body || ''} placeholder="Details..." rows="2" className="w-full p-4 rounded-xl border-2 border-gray-100 bg-white" />
                                        </div>
                                    ))}
                                </div>
                            </FormSection>

                            {/* Slideshow */}
                            <FormSection title="Highlights Slideshow" icon="fa-camera-retro" colorClass="bg-chroma-red">
                                <div className="space-y-8">
                                    <div className="space-y-2">
                                        <label className="block text-xs font-bold uppercase tracking-wider text-brand-ink/50">Section Title</label>
                                        <input name="slideshow_title" defaultValue={c.slideshow_title || 'Campus Life'} className="w-full p-4 rounded-xl border-2 border-gray-100 bg-gray-50 font-bold" />
                                    </div>

                                    {/* Photo Upload Section */}
                                    <div>
                                        <p className="text-xs font-bold text-brand-ink/40 uppercase tracking-wider mb-4">Upload Photos</p>
                                        <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
                                            {formState.slides.map((url, i) => (
                                                <div key={i} className="space-y-2">
                                                    <div onClick={() => {
                                                        const frame = wp.media({ multiple: false });
                                                        frame.on('select', () => {
                                                            const url = frame.state().get('selection').first().toJSON().url;
                                                            const news = [...formState.slides]; news[i] = url;
                                                            setFormState({ ...formState, slides: news });
                                                        });
                                                        frame.open();
                                                    }} className="aspect-square rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 cursor-pointer overflow-hidden flex items-center justify-center hover:border-chroma-red transition-all">
                                                        {url ? <img src={url} className="w-full h-full object-cover" /> : <i className="fa-solid fa-plus text-gray-300"></i>}
                                                    </div>
                                                    {url && <button type="button" onClick={() => {
                                                        const news = [...formState.slides]; news[i] = '';
                                                        setFormState({ ...formState, slides: news });
                                                    }} className="text-[10px] font-black uppercase text-red-500 hover:text-red-700 w-full text-center">Clear</button>}
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            </FormSection>

                            {/* Newsletter */}
                            <FormSection title="Newsletter & Events" icon="fa-paper-plane" colorClass="bg-chroma-blueDark">
                                <div className="space-y-6">
                                    <div className="grid md:grid-cols-2 gap-6">
                                        <div className="space-y-2">
                                            <label className="block text-xs font-bold uppercase tracking-wider text-brand-ink/50">Headline</label>
                                            <input name="newsletter.title" defaultValue={c.newsletter?.title} className="w-full p-4 rounded-xl border-2 border-gray-100 bg-gray-50" />
                                        </div>
                                        <div className="space-y-2">
                                            <label className="block text-xs font-bold uppercase tracking-wider text-brand-ink/50">Link URL (QR)</label>
                                            <input name="newsletter.url" defaultValue={c.newsletter?.url} placeholder="https://" className="w-full p-4 rounded-xl border-2 border-gray-100 bg-gray-50" />
                                        </div>
                                    </div>
                                    <div className="space-y-2">
                                        <label className="block text-xs font-bold uppercase tracking-wider text-brand-ink/50">Quick Summary</label>
                                        <textarea name="newsletter.body" defaultValue={c.newsletter?.body} rows="3" className="w-full p-4 rounded-xl border-2 border-gray-100 bg-gray-50" />
                                    </div>
                                    {/* PDF Upload */}
                                    <div className="p-6 bg-chroma-blueDark/5 rounded-2xl border border-chroma-blueDark/10">
                                        <div className="flex items-center justify-between mb-4">
                                            <div>
                                                <h4 className="font-bold text-brand-ink">📄 Newsletter PDF</h4>
                                                <p className="text-xs text-brand-ink/50">Upload a PDF to display on the TV (auto-scrolls pages)</p>
                                            </div>
                                            {formState.newsletterPdf && (
                                                <button type="button" onClick={() => setFormState({ ...formState, newsletterPdf: '' })} className="text-xs font-bold text-red-500 hover:text-red-700">
                                                    Remove PDF
                                                </button>
                                            )}
                                        </div>
                                        <div onClick={() => {
                                            const frame = wp.media({ title: 'Select Newsletter PDF', multiple: false, library: { type: 'application/pdf' } });
                                            frame.on('select', () => {
                                                const url = frame.state().get('selection').first().toJSON().url;
                                                setFormState({ ...formState, newsletterPdf: url });
                                            });
                                            frame.open();
                                        }} className="w-full p-6 rounded-xl border-2 border-dashed border-chroma-blueDark/20 bg-white cursor-pointer hover:border-chroma-blue hover:bg-chroma-blue/5 transition-all flex items-center justify-center gap-4">
                                            {formState.newsletterPdf ? (
                                                <>
                                                    <i className="fa-solid fa-file-pdf text-3xl text-red-500"></i>
                                                    <div className="text-left">
                                                        <p className="font-bold text-brand-ink">PDF Uploaded</p>
                                                        <p className="text-xs text-brand-ink/50 truncate max-w-xs">{formState.newsletterPdf.split('/').pop()}</p>
                                                    </div>
                                                </>
                                            ) : (
                                                <>
                                                    <i className="fa-solid fa-cloud-arrow-up text-2xl text-chroma-blueDark/30"></i>
                                                    <span className="font-bold text-brand-ink/50">Click to upload PDF</span>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </FormSection>

                            {/* Celebrations */}
                            <FormSection title="Celebrations & Birthdays" icon="fa-cake-candles" colorClass="bg-chroma-red">
                                <div className="space-y-4">
                                    <p className="text-xs text-brand-ink/40 font-bold uppercase tracking-widest mb-2">Display names for the week</p>
                                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        {[0, 1, 2, 3, 4, 5, 6, 7].map(i => (
                                            <input key={i} name={`cel_${i}`} defaultValue={c.celebrations?.[i] || ''} placeholder="Name..." className="w-full p-4 rounded-xl border-2 border-gray-100 bg-gray-50 text-sm font-medium" />
                                        ))}
                                    </div>
                                </div>
                            </FormSection>

                            {/* TV Layout & Audio Settings */}
                            <FormSection title="Global TV Settings" icon="fa-gear" colorClass="bg-brand-ink">
                                <div className="space-y-6">
                                    <div className="space-y-2">
                                        <label className="block text-xs font-bold uppercase tracking-wider text-brand-ink/50">Background Music URL (YouTube or MP3)</label>
                                        <div className="flex gap-4">
                                            <div className="flex-1">
                                                <input name="music_url" defaultValue={c.music_url || ''} placeholder="https://www.youtube.com/watch?v=..." className="w-full p-4 rounded-xl border-2 border-gray-100 bg-gray-50 focus:border-chroma-blue focus:bg-white outline-none font-medium transition-all" />
                                                <p className="text-[10px] text-brand-ink/40 mt-2 italic">Tip: Use a "Lofi" or "Ambient" YouTube playlist for the best experience.</p>
                                            </div>
                                            <div className="w-20 h-20 bg-gray-100 rounded-2xl flex items-center justify-center text-brand-ink/20 text-3xl shrink-0">
                                                <i className="fa-solid fa-music"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="p-4 bg-brand-cream border border-brand-ink/5 rounded-2xl">
                                        <p className="text-xs font-bold text-brand-ink/60 mb-1">💡 Pro Tip</p>
                                        <p className="text-[11px] text-brand-ink/50 leading-relaxed">Most browsers require you to click the screen once after the page loads to enable audio playback. If music doesn't start, just tap the screen!</p>
                                    </div>
                                </div>
                            </FormSection>

                            {/* Floating Save Actions */}
                            <div className="fixed bottom-10 left-1/2 -translate-x-1/2 z-50">
                                <button disabled={saving} className="bg-brand-ink text-white font-black text-xl px-20 py-6 rounded-[2rem] shadow-2xl hover:bg-chroma-blueDark transition-all active:scale-95 flex items-center gap-4 group">
                                    {saving ? <i className="fa-solid fa-spinner fa-spin"></i> : <i className="fa-solid fa-floppy-disk group-hover:animate-bounce"></i>}
                                    {saving ? 'Syncing...' : 'Push to Display'}
                                </button>
                            </div>

                        </form>
                    </main>
                </div>
            );
        }

        const root = ReactDOM.createRoot(document.getElementById('root'));
        root.render(<App />);
    </script>
    <?php wp_footer(); ?>
</body>

</html>