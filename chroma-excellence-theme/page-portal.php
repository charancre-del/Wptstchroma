<?php
/**
 * Template Name: Portal
 * Description: Standalone Single Page App for School Directors to manage TV content.
 */

// If user somehow gets here and they are logged in as WP Admin, that's fine, but this system uses Google Auth independently.
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Director Portal | Chroma</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'], serif: ['Playfair Display', 'serif'] },
                    colors: {
                        brand: { ink: '#263238', cream: '#FFFCF8' },
                        chroma: { red: '#D67D6B', yellow: '#E6BE75', green: '#8DA399', blue: '#4A6C7C', blueDark: '#2F4858' }
                    }
                }
            }
        }
    </script>
    <!-- Fonts & Icons -->
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700&family=Playfair+Display:wght@700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Identity -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>

<body class="bg-brand-cream text-brand-ink min-h-screen">

    <!-- ================= CAUTION: CONFIG ================= -->
    <!-- TODO: PASTE YOUR GOOGLE CLIENT ID HERE -->
    <div id="g_config" data-client-id="REPLACE_WITH_YOUR_GOOGLE_CLIENT_ID_FROM_ENV_LOCAL"></div>
    <!-- =================================================== -->

    <!-- LOGIN VIEW -->
    <div id="login-view" class="min-h-screen flex flex-col items-center justify-center p-6 hidden">
        <div class="w-full max-w-md bg-white rounded-3xl p-8 shadow-xl border border-chroma-blue/10 text-center">
            <div class="flex justify-center -space-x-2 mb-6">
                <span class="w-4 h-4 rounded-full bg-chroma-red"></span>
                <span class="w-4 h-4 rounded-full bg-chroma-yellow"></span>
                <span class="w-4 h-4 rounded-full bg-chroma-green"></span>
                <span class="w-4 h-4 rounded-full bg-chroma-blue"></span>
            </div>
            <h1 class="font-serif text-3xl font-bold mb-2">Director Portal</h1>
            <p class="text-brand-ink/60 mb-8">Sign in to manage your school's TV dashboard.</p>

            <div class="flex justify-center">
                <!-- Google Button Container -->
                <div id="g_id_onload" data-context="signin" data-ux_mode="popup"
                    data-callback="handleCredentialResponse" data-auto_prompt="false">
                </div>
                <div class="g_id_signin" data-type="standard" data-shape="pill" data-theme="outline"
                    data-text="signin_with" data-size="large" data-logo_alignment="left">
                </div>
            </div>
            <p id="login-error" class="mt-4 text-red-500 text-sm font-bold hidden"></p>
        </div>
    </div>

    <!-- DASHBOARD VIEW -->
    <div id="dashboard-view" class="min-h-screen pb-20 hidden">
        <!-- Header -->
        <header class="bg-white border-b border-brand-ink/5 sticky top-0 z-50">
            <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
                <h1 class="font-serif text-xl font-bold">Chroma Portal <span class="text-brand-ink/40">|</span> <span
                        id="school-title">Loading...</span></h1>
                <div class="flex items-center gap-4">
                    <a id="preview-link" href="#" target="_blank"
                        class="text-sm font-bold text-chroma-blue flex items-center gap-2 hover:underline">
                        <i class="fa-solid fa-external-link-alt"></i> Preview TV
                    </a>
                    <button onclick="logout()" class="text-red-500 hover:bg-red-50 p-2 rounded-full">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </button>
                </div>
            </div>
        </header>

        <main class="max-w-5xl mx-auto px-6 py-8">
            <form id="dashboard-form" onsubmit="saveChanges(event)" class="space-y-8">

                <div class="flex justify-end sticky top-24 z-40">
                    <button type="submit" id="save-btn"
                        class="bg-chroma-blue text-white px-8 py-3 rounded-full font-bold shadow-lg hover:bg-chroma-blueDark transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-save"></i> <span id="save-text">Save Changes</span>
                    </button>
                </div>

                <!-- General Settings -->
                <section class="bg-white p-6 rounded-3xl shadow-sm border border-brand-ink/5">
                    <h2 class="font-serif text-2xl font-bold mb-6 text-brand-ink">General Settings</h2>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Welcome Message Override</label>
                            <input name="welcome_override" placeholder="Default: Welcome to Chroma..."
                                class="w-full p-3 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-chroma-blue">
                            <p class="text-xs text-brand-ink/40 mt-1">Leave empty to show standard welcome.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Weekly Menu URL</label>
                            <input name="menu" placeholder="https://..."
                                class="w-full p-3 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-chroma-blue">
                        </div>
                    </div>
                </section>

                <!-- Newsletter -->
                <section class="bg-white p-6 rounded-3xl shadow-sm border border-brand-ink/5">
                    <h2 class="font-serif text-2xl font-bold mb-6 text-chroma-red">Newsletter</h2>
                    <div class="grid gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Title</label>
                            <input name="newsletter[title]"
                                class="w-full p-3 rounded-xl border border-gray-200 bg-gray-50 font-bold focus:outline-none focus:border-chroma-red">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Body Text (Short)</label>
                            <textarea name="newsletter[body]" rows="3"
                                class="w-full p-3 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-chroma-red"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Drive Link (Share URL)</label>
                            <input name="newsletter[url]" placeholder="https://drive.google.com/..."
                                class="w-full p-3 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-chroma-red">
                        </div>
                    </div>
                </section>

                <!-- Star Educator -->
                <section class="bg-white p-6 rounded-3xl shadow-sm border border-brand-ink/5">
                    <h2 class="font-serif text-2xl font-bold mb-6 text-chroma-blue">Star Educator</h2>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Name</label>
                            <input name="eom[name]"
                                class="w-full p-3 rounded-xl border border-gray-200 bg-gray-50 font-bold focus:outline-none focus:border-chroma-blue">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Photo URL</label>
                            <input name="eom[photo_url]" placeholder="https://..."
                                class="w-full p-3 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-chroma-blue">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold uppercase mb-1">Blurb / Quote</label>
                            <textarea name="eom[blurb]" rows="2"
                                class="w-full p-3 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-chroma-blue"></textarea>
                        </div>
                    </div>
                </section>

                <!-- Chroma Cares -->
                <section class="bg-white p-6 rounded-3xl shadow-sm border border-brand-ink/5">
                    <h2 class="font-serif text-2xl font-bold mb-6 text-chroma-green">Chroma Cares</h2>
                    <div class="grid gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Title</label>
                            <input name="chroma_cares[title]" placeholder="Global Default"
                                class="w-full p-3 rounded-xl border border-gray-200 bg-gray-50 font-bold focus:outline-none focus:border-chroma-green">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Body</label>
                            <textarea name="chroma_cares[body]" rows="2" placeholder="Global Default"
                                class="w-full p-3 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-chroma-green"></textarea>
                        </div>
                    </div>
                </section>

                <!-- Notices -->
                <section class="bg-white p-6 rounded-3xl shadow-sm border border-brand-ink/5">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="font-serif text-2xl font-bold text-chroma-yellow">Notices</h2>
                        <button type="button" onclick="addNotice()"
                            class="text-xs bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded-full font-bold flex items-center gap-1">
                            <i class="fa-solid fa-plus"></i> Add
                        </button>
                    </div>
                    <div id="notices-list" class="space-y-4">
                        <!-- Dynamic Notices -->
                    </div>
                </section>

                <!-- Today & Celebrations -->
                <section class="bg-white p-6 rounded-3xl shadow-sm border border-brand-ink/5">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="font-serif text-2xl font-bold">Today & Celebrations</h2>
                    </div>
                    <div class="grid md:grid-cols-2 gap-8">
                        <!-- Today -->
                        <div>
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-bold">Schedule</h3>
                                <button type="button" onclick="addTodayItem()"
                                    class="text-xs bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded-full font-bold flex items-center gap-1">
                                    <i class="fa-solid fa-plus"></i> Add
                                </button>
                            </div>
                            <div id="today-list" class="space-y-2">
                                <!-- Dynamic Today Items -->
                            </div>
                        </div>
                        <!-- Celebrations -->
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                            <h3 class="font-bold mb-3 text-sm uppercase text-brand-ink/50">Celebrations</h3>
                            <div class="space-y-2">
                                <input name="celebrations[]" placeholder="e.g. Happy Birthday Sarah! 🎂"
                                    class="w-full p-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:border-chroma-blue">
                                <input name="celebrations[]" placeholder="e.g. Pre-K Graduation 🎓"
                                    class="w-full p-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:border-chroma-blue">
                                <input name="celebrations[]" placeholder="e.g. Welcome Ms. Johnson!"
                                    class="w-full p-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:border-chroma-blue">
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Visuals -->
                <section class="bg-white p-6 rounded-3xl shadow-sm border border-brand-ink/5">
                    <h2 class="font-serif text-2xl font-bold mb-6">Visuals</h2>
                    <div class="grid gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">YouTube URL (Overrides
                                Slideshow)</label>
                            <input name="youtube" placeholder="https://youtube.com/..."
                                class="w-full p-3 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-chroma-blue">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Slideshow Images (URLs)</label>
                            <div class="space-y-2">
                                <input name="slideshow[]" placeholder="Image URL 1"
                                    class="w-full p-3 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-chroma-blue">
                                <input name="slideshow[]" placeholder="Image URL 2"
                                    class="w-full p-3 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-chroma-blue">
                                <input name="slideshow[]" placeholder="Image URL 3"
                                    class="w-full p-3 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-chroma-blue">
                            </div>
                        </div>
                    </div>
                </section>

            </form>
        </main>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        const API_URL = '/wp-json'; // Relative to WP
        let currentSchoolId = null;

        // --- INIT ---
        window.onload = function () {
            // Set Google Client ID from config
            const clientId = document.getElementById('g_config').getAttribute('data-client-id');
            const gIdContainer = document.getElementById('g_id_onload');
            if (gIdContainer && clientId !== 'REPLACE_WITH_YOUR_GOOGLE_CLIENT_ID_FROM_ENV_LOCAL') {
                gIdContainer.setAttribute('data-client_id', clientId);
            }

            checkSession();
        };

        function checkSession() {
            const token = localStorage.getItem('chroma_token');
            if (token) {
                showDashboard();
            } else {
                document.getElementById('login-view').classList.remove('hidden');
                document.getElementById('dashboard-view').classList.add('hidden');
            }
        }

        // --- AUTH ---
        async function handleCredentialResponse(response) {
            try {
                const res = await fetch(`${API_URL}/chroma/v1/auth/google`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_token: response.credential })
                });

                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Login failed');

                localStorage.setItem('chroma_token', data.token);
                localStorage.setItem('chroma_school_id', data.school_id);
                showDashboard();

            } catch (err) {
                document.getElementById('login-error').innerText = err.message;
                document.getElementById('login-error').classList.remove('hidden');
            }
        }

        function logout() {
            localStorage.clear();
            window.location.reload();
        }

        // --- DASHBOARD ---
        async function showDashboard() {
            document.getElementById('login-view').classList.add('hidden');
            document.getElementById('dashboard-view').classList.remove('hidden');

            const token = localStorage.getItem('chroma_token');
            try {
                const res = await fetch(`${API_URL}/chroma/v1/portal/me`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                if (res.status === 401) { logout(); return; }

                const data = await res.json();
                currentSchoolId = data.id;
                populateForm(data);

            } catch (err) {
                console.error(err);
                logout();
            }
        }

        function populateForm(data) {
            document.getElementById('school-title').innerText = data.title;
            document.getElementById('preview-link').href = `/tv/${data.slug}`;

            const c = data.content || {};

            // Simple inputs
            setVal('welcome_override', c.welcome_override);
            setVal('menu', c.menu);
            setVal('youtube', c.youtube);

            // Nested objects
            setVal('newsletter[title]', c.newsletter?.title);
            setVal('newsletter[body]', c.newsletter?.body);
            setVal('newsletter[url]', c.newsletter?.url);

            setVal('eom[name]', c.eom?.name);
            setVal('eom[photo_url]', c.eom?.photo_url);
            setVal('eom[blurb]', c.eom?.blurb);

            setVal('chroma_cares[title]', c.chroma_cares?.title);
            setVal('chroma_cares[body]', c.chroma_cares?.body);

            // Arrays (Fixed inputs)
            const slideInputs = document.querySelectorAll('input[name="slideshow[]"]');
            (c.slideshow || []).forEach((url, i) => { if (slideInputs[i]) slideInputs[i].value = url; });

            const celebInputs = document.querySelectorAll('input[name="celebrations[]"]');
            (c.celebrations || []).forEach((str, i) => { if (celebInputs[i]) celebInputs[i].value = str; });

            // Dynamic Lists
            const noticesList = document.getElementById('notices-list');
            noticesList.innerHTML = '';
            (c.announcements || []).forEach(n => addNotice(n));

            const todayList = document.getElementById('today-list');
            todayList.innerHTML = '';
            (c.today || []).forEach(t => addTodayItem(t));
        }

        function setVal(name, val) {
            const el = document.querySelector(`[name="${name}"]`);
            if (el) el.value = val || '';
        }

        // --- DYNAMIC FIELDS ---
        function addNotice(data = { title: '', body: '', priority: 'normal' }) {
            const id = Date.now() + Math.random();
            const html = `
                <div class="p-4 bg-gray-50 rounded-2xl flex gap-4 items-start relative group notice-item">
                    <div class="flex-1 grid gap-2">
                        <input name="notice_title[]" value="${esc(data.title)}" placeholder="Title" class="font-bold bg-transparent border-b border-gray-200 focus:outline-none">
                        <textarea name="notice_body[]" placeholder="Details..." rows="2" class="text-sm bg-transparent border-b border-gray-200 focus:outline-none resize-none">${dirEsc(data.body)}</textarea>
                        <select name="notice_priority[]" class="text-xs bg-white rounded-md p-1 border border-gray-200 w-32">
                            <option value="normal" ${data.priority === 'normal' ? 'selected' : ''}>Normal</option>
                            <option value="high" ${data.priority === 'high' ? 'selected' : ''}>High Priority</option>
                        </select>
                    </div>
                    <button type="button" onclick="this.closest('.notice-item').remove()" class="text-red-400 hover:text-red-600">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>`;
            document.getElementById('notices-list').insertAdjacentHTML('beforeend', html);
        }

        function addTodayItem(data = { time: '', label: '' }) {
            const html = `
                <div class="flex gap-2 items-center today-item">
                    <input name="today_time[]" value="${esc(data.time)}" placeholder="9:00 AM" class="w-24 p-2 rounded-lg border border-gray-200 bg-gray-50 text-sm font-bold">
                    <input name="today_label[]" value="${esc(data.label)}" placeholder="Event Name" class="flex-1 p-2 rounded-lg border border-gray-200 bg-gray-50 text-sm">
                    <button type="button" onclick="this.closest('.today-item').remove()" class="text-red-400 hover:text-red-600">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>`;
            document.getElementById('today-list').insertAdjacentHTML('beforeend', html);
        }

        function esc(str) { return (str || '').replace(/"/g, '&quot;'); }
        function dirEsc(str) { return (str || ''); } // Textarea raw content

        // --- SAVE ---
        async function saveChanges(e) {
            e.preventDefault();
            const btn = document.getElementById('save-btn');
            const btnText = document.getElementById('save-text');
            const originalText = btnText.innerText;

            btn.disabled = true;
            btnText.innerText = 'Saving...';

            const form = document.getElementById('dashboard-form');
            const fd = new FormData(form);

            // Construct Payload manually to handle arrays properly
            const payload = {
                welcome_override: fd.get('welcome_override'),
                menu: fd.get('menu'),
                youtube: fd.get('youtube'),
                newsletter: {
                    title: fd.get('newsletter[title]'),
                    body: fd.get('newsletter[body]'),
                    url: fd.get('newsletter[url]'),
                },
                eom: {
                    name: fd.get('eom[name]'),
                    photo_url: fd.get('eom[photo_url]'),
                    blurb: fd.get('eom[blurb]'),
                },
                chroma_cares: {
                    title: fd.get('chroma_cares[title]'),
                    body: fd.get('chroma_cares[body]'),
                },
                slideshow: Array.from(document.querySelectorAll('input[name="slideshow[]"]')).map(i => i.value).filter(Boolean),
                celebrations: Array.from(document.querySelectorAll('input[name="celebrations[]"]')).map(i => i.value).filter(Boolean),

                // Dynamic Arrays
                announcements: [],
                today: []
            };

            // Parse Notices
            const nTitles = document.getElementsByName('notice_title[]');
            const nBodies = document.getElementsByName('notice_body[]');
            const nPriorities = document.getElementsByName('notice_priority[]');
            for (let i = 0; i < nTitles.length; i++) {
                if (nTitles[i].value) {
                    payload.announcements.push({
                        title: nTitles[i].value,
                        body: nBodies[i].value,
                        priority: nPriorities[i].value
                    });
                }
            }

            // Parse Today
            const tTimes = document.getElementsByName('today_time[]');
            const tLabels = document.getElementsByName('today_label[]');
            for (let i = 0; i < tLabels.length; i++) {
                if (tLabels[i].value) {
                    payload.today.push({
                        time: tTimes[i].value,
                        label: tLabels[i].value
                    });
                }
            }

            try {
                const token = localStorage.getItem('chroma_token');
                const res = await fetch(`${API_URL}/chroma/v1/portal/school/${currentSchoolId}`, {
                    method: 'PATCH',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                if (!res.ok) throw new Error('Save failed');

                alert('Saved successfully!');
            } catch (err) {
                alert('Error saving: ' + err.message);
            } finally {
                btn.disabled = false;
                btnText.innerText = originalText;
            }
        }
    </script>
</body>

</html>