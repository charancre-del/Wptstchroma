'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import { useForm, useFieldArray } from 'react-hook-form'
import { LogOut, ExternalLink, Save, Plus, Trash2 } from 'lucide-react'

// Types
type DashboardData = {
    id: number
    title: string
    slug: string
    content: {
        newsletter: { title: string; body: string; url: string }
        eom: { name: string; blurb: string; photo_url: string }
        announcements: { title: string; body: string; priority: string }[]
        today: { time: string; label: string }[]
        menu: string
        youtube: string
        welcome_override: string
        slideshow: string[]
        chroma_cares: { title: string; body: string }
        celebrations: string[]
    }
}

function ImagePreview({ url }: { url: string }) {
    if (!url) return <div className="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center text-[10px] text-gray-400">No Image</div>
    return (
        <div className="w-16 h-16 rounded-lg overflow-hidden border border-gray-200">
            <img src={url} alt="Preview" className="w-full h-full object-cover" onError={(e) => (e.currentTarget.src = 'https://placehold.co/100x100?text=Error')} />
        </div>
    )
}

export default function DashboardPage() {
    const router = useRouter()
    const [loading, setLoading] = useState(true)
    const [saving, setSaving] = useState(false)
    const [school, setSchool] = useState<DashboardData | null>(null)

    const { register, control, handleSubmit, reset, watch } = useForm<DashboardData['content']>()

    // Watch values for preview
    const watchedEomPhoto = watch('eom.photo_url')
    const watchedSlides = watch('slideshow')

    // Field Arrays
    const { fields: annFields, append: addAnn, remove: removeAnn } = useFieldArray({ control, name: 'announcements' })
    const { fields: todayFields, append: addToday, remove: removeToday } = useFieldArray({ control, name: 'today' })

    // TODO: Add field array for celebrations if we want dynamic list. 
    // For now we will rely on a simple text area or placeholder if strictly requested, 
    // but the type is string[]. Let's stick to the form structure for now.

    useEffect(() => {
        const token = localStorage.getItem('chroma_token')
        if (!token) {
            router.push('/')
            return
        }

        // Fetch School Data
        fetch(`${process.env.NEXT_PUBLIC_WP_API_URL}/chroma/v1/portal/me`, {
            headers: { 'Authorization': `Bearer ${token}` }
        })
            .then(res => {
                if (res.status === 401) throw new Error('Unauthorized')
                return res.json()
            })
            .then(data => {
                setSchool(data)
                // Ensure default structure for new fields to avoid uncontrolled input warnings
                const content = {
                    ...data.content,
                    chroma_cares: data.content.chroma_cares || { title: '', body: '' },
                    celebrations: data.content.celebrations || [],
                    slideshow: data.content.slideshow || [],
                    menu: data.content.menu || '',
                    welcome_override: data.content.welcome_override || ''
                }
                reset(content)
                setLoading(false)
            })
            .catch(() => {
                localStorage.removeItem('chroma_token')
                router.push('/')
            })
    }, [router, reset])

    const onSubmit = async (data: DashboardData['content']) => {
        setSaving(true)
        const token = localStorage.getItem('chroma_token')

        try {
            const res = await fetch(`${process.env.NEXT_PUBLIC_WP_API_URL}/chroma/v1/portal/school/${school?.id}`, {
                method: 'PATCH',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })

            if (res.status === 401) {
                localStorage.removeItem('chroma_token')
                router.push('/')
                return
            }

            if (!res.ok) throw new Error('Save failed')
            alert('Saved successfully!')
        } catch (err) {
            alert('Error saving data')
        } finally {
            setSaving(false)
        }
    }

    if (loading) return <div className="min-h-screen flex items-center justify-center">Loading...</div>

    return (
        <div className="min-h-screen pb-20">
            {/* Header */}
            <header className="bg-white border-b border-brand-ink/5 sticky top-0 z-50">
                <div className="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
                    <h1 className="font-serif text-xl font-bold">Chroma Portal <span className="text-brand-ink/40">|</span> {school?.title}</h1>
                    <div className="flex items-center gap-4">
                        <a href={`${process.env.NEXT_PUBLIC_WP_API_URL}/tv/${school?.slug}`} target="_blank" className="text-sm font-bold text-chroma-blue flex items-center gap-2 hover:underline">
                            <ExternalLink size={16} /> Preview TV
                        </a>
                        <button onClick={() => { localStorage.clear(); router.push('/') }} className="text-red-500 hover:bg-red-50 p-2 rounded-full">
                            <LogOut size={20} />
                        </button>
                    </div>
                </div>
            </header>

            <main className="max-w-5xl mx-auto px-6 py-8">
                <form onSubmit={handleSubmit(onSubmit)} className="space-y-8">

                    <div className="flex justify-end sticky top-24 z-40">
                        <button disabled={saving} type="submit" className="bg-chroma-blue text-white px-8 py-3 rounded-full font-bold shadow-lg hover:bg-chroma-blueDark transition-colors flex items-center gap-2">
                            <Save size={20} /> {saving ? 'Saving...' : 'Save Changes'}
                        </button>
                    </div>

                    {/* General Settings */}
                    <section className="bg-white p-6 rounded-3xl shadow-sm border border-brand-ink/5">
                        <h2 className="font-serif text-2xl font-bold mb-6 text-brand-ink">General Settings</h2>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <label className="block text-xs font-bold uppercase mb-1">Welcome Message Override</label>
                                <input {...register('welcome_override')} placeholder="Default: Welcome to Chroma..." className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50" />
                                <p className="text-xs text-brand-ink/40 mt-1">Leave empty to show standard welcome.</p>
                            </div>
                            <div>
                                <label className="block text-xs font-bold uppercase mb-1">Weekly Menu URL</label>
                                <input {...register('menu')} placeholder="https://..." className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50" />
                            </div>
                        </div>
                    </section>

                    {/* Newsletter */}
                    <section className="bg-white p-6 rounded-3xl shadow-sm border border-brand-ink/5">
                        <h2 className="font-serif text-2xl font-bold mb-6 text-chroma-red">Newsletter</h2>
                        <div className="grid gap-4">
                            <div>
                                <label className="block text-xs font-bold uppercase mb-1">Title</label>
                                <input {...register('newsletter.title')} className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50 font-bold" />
                            </div>
                            <div>
                                <label className="block text-xs font-bold uppercase mb-1">Body Text (Short)</label>
                                <textarea {...register('newsletter.body')} rows={3} className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50" />
                            </div>
                            <div>
                                <label className="block text-xs font-bold uppercase mb-1">Drive Link (Share URL)</label>
                                <input {...register('newsletter.url')} placeholder="https://drive.google.com/..." className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50" />
                            </div>
                        </div>
                    </section>

                    {/* Employee of Month */}
                    <section className="bg-white p-6 rounded-3xl shadow-sm border border-brand-ink/5">
                        <h2 className="font-serif text-2xl font-bold mb-6 text-chroma-blue">Star Educator</h2>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <label className="block text-xs font-bold uppercase mb-1">Name</label>
                                <input {...register('eom.name')} className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50 font-bold" />
                            </div>
                            <div className="flex gap-4 items-end">
                                <div className="flex-1">
                                    <label className="block text-xs font-bold uppercase mb-1">Photo URL</label>
                                    <input {...register('eom.photo_url')} placeholder="https://..." className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50" />
                                </div>
                                <ImagePreview url={watchedEomPhoto || ''} />
                            </div>
                            <div className="md:col-span-2">
                                <label className="block text-xs font-bold uppercase mb-1">Blurb / Quote</label>
                                <textarea {...register('eom.blurb')} rows={2} className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50" />
                            </div>
                        </div>
                    </section>

                    {/* Chroma Cares (Local Override) */}
                    <section className="bg-white p-6 rounded-3xl shadow-sm border border-brand-ink/5">
                        <h2 className="font-serif text-2xl font-bold mb-6 text-chroma-green">Chroma Cares</h2>
                        <div className="grid gap-4">
                            <div>
                                <label className="block text-xs font-bold uppercase mb-1">Title</label>
                                <input {...register('chroma_cares.title')} placeholder="Global Default" className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50 font-bold" />
                            </div>
                            <div>
                                <label className="block text-xs font-bold uppercase mb-1">Body</label>
                                <textarea {...register('chroma_cares.body')} rows={2} placeholder="Global Default" className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50" />
                            </div>
                        </div>
                    </section>

                    {/* Announcements */}
                    <section className="bg-white p-6 rounded-3xl shadow-sm border border-brand-ink/5">
                        <div className="flex justify-between items-center mb-6">
                            <h2 className="font-serif text-2xl font-bold text-chroma-yellow">Notices</h2>
                            <button type="button" onClick={() => addAnn({ title: '', body: '', priority: 'normal' })} className="text-xs bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded-full font-bold flex items-center gap-1">
                                <Plus size={14} /> Add
                            </button>
                        </div>
                        <div className="space-y-4">
                            {annFields.map((field, index) => (
                                <div key={field.id} className="p-4 bg-gray-50 rounded-2xl flex gap-4 items-start relative group">
                                    <div className="flex-1 grid gap-2">
                                        <input {...register(`announcements.${index}.title`)} placeholder="Title" className="font-bold bg-transparent border-b border-gray-200 focus:outline-none" />
                                        <textarea {...register(`announcements.${index}.body`)} placeholder="Details..." rows={2} className="text-sm bg-transparent border-b border-gray-200 focus:outline-none resize-none" />
                                        <select {...register(`announcements.${index}.priority`)} className="text-xs bg-white rounded-md p-1 border border-gray-200 w-32">
                                            <option value="normal">Normal</option>
                                            <option value="high">High Priority</option>
                                        </select>
                                    </div>
                                    <button type="button" onClick={() => removeAnn(index)} className="text-red-400 hover:text-red-600">
                                        <Trash2 size={18} />
                                    </button>
                                </div>
                            ))}
                        </div>
                    </section>

                    {/* Today */}
                    <section className="bg-white p-6 rounded-3xl shadow-sm border border-brand-ink/5">
                        <div className="flex justify-between items-center mb-6">
                            <h2 className="font-serif text-2xl font-bold">Today & Celebrations</h2>
                        </div>

                        <div className="grid md:grid-cols-2 gap-8">
                            {/* Today List */}
                            <div>
                                <div className="flex justify-between items-center mb-4">
                                    <h3 className="font-bold">Schedule</h3>
                                    <button type="button" onClick={() => addToday({ time: '', label: '' })} className="text-xs bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded-full font-bold flex items-center gap-1">
                                        <Plus size={14} /> Add
                                    </button>
                                </div>
                                <div className="space-y-2">
                                    {todayFields.map((field, index) => (
                                        <div key={field.id} className="flex gap-2 items-center">
                                            <input {...register(`today.${index}.time`)} placeholder="9:00 AM" className="w-24 p-2 rounded-lg border border-gray-200 bg-gray-50 text-sm font-bold" />
                                            <input {...register(`today.${index}.label`)} placeholder="Event Name" className="flex-1 p-2 rounded-lg border border-gray-200 bg-gray-50 text-sm" />
                                            <button type="button" onClick={() => removeToday(index)} className="text-red-400 hover:text-red-600">
                                                <Trash2 size={16} />
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Celebrations */}
                            <div className="bg-gray-50 p-4 rounded-xl border border-gray-100">
                                <h3 className="font-bold mb-3 text-sm uppercase text-brand-ink/50">Celebrations</h3>
                                <div className="space-y-2">
                                    <input {...register('celebrations.0')} placeholder="e.g. Happy Birthday Sarah! 🎂" className="w-full p-2 rounded-lg border border-gray-200 text-sm" />
                                    <input {...register('celebrations.1')} placeholder="e.g. Pre-K Graduation 🎓" className="w-full p-2 rounded-lg border border-gray-200 text-sm" />
                                    <input {...register('celebrations.2')} placeholder="e.g. Welcome Ms. Johnson!" className="w-full p-2 rounded-lg border border-gray-200 text-sm" />
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* Media & Links */}
                    <section className="bg-white p-6 rounded-3xl shadow-sm border border-brand-ink/5">
                        <h2 className="font-serif text-2xl font-bold mb-6">Visuals</h2>
                        <div className="grid gap-4">
                            <div>
                                <label className="block text-xs font-bold uppercase mb-1">YouTube URL (Overrides Slideshow)</label>
                                <input {...register('youtube')} placeholder="https://youtube.com/..." className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50" />
                            </div>

                            {/* Slideshow */}
                            {[0, 1, 2].map(i => (
                                <div key={i} className="flex gap-4 items-end">
                                    <div className="flex-1">
                                        <label className="block text-xs font-bold uppercase mb-1">Slideshow Image {i + 1} URL</label>
                                        <input {...register(`slideshow.${i}`)} placeholder="https://..." className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50" />
                                    </div>
                                    <ImagePreview url={(watchedSlides || [])[i] || ''} />
                                </div>
                            ))}
                        </div>
                    </section>

                </form>
            </main>
        </div>
    )
}
