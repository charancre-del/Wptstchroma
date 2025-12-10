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
    }
}

export default function DashboardPage() {
    const router = useRouter()
    const [loading, setLoading] = useState(true)
    const [saving, setSaving] = useState(false)
    const [school, setSchool] = useState<DashboardData | null>(null)

    const { register, control, handleSubmit, reset } = useForm<DashboardData['content']>()

    // Field Arrays
    const { fields: annFields, append: addAnn, remove: removeAnn } = useFieldArray({ control, name: 'announcements' })
    const { fields: todayFields, append: addToday, remove: removeToday } = useFieldArray({ control, name: 'today' })

    // Slide Images handle manually for now or use field array if complex. 
    // Let's assume slide images is a textarea of new-line separated URLs for v1 simplicity

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
                reset(data.content)
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
                        <a href={`/tv/${school?.slug}`} target="_blank" className="text-sm font-bold text-chroma-blue flex items-center gap-2 hover:underline">
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

                    <div className="flex justify-end">
                        <button disabled={saving} type="submit" className="bg-chroma-blue text-white px-8 py-3 rounded-full font-bold shadow-lg hover:bg-chroma-blueDark transition-colors flex items-center gap-2">
                            <Save size={20} /> {saving ? 'Saving...' : 'Save Changes'}
                        </button>
                    </div>

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
                        <h2 className="font-serif text-2xl font-bold mb-6 text-chroma-blue">Employee of the Month</h2>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <label className="block text-xs font-bold uppercase mb-1">Name</label>
                                <input {...register('eom.name')} className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50 font-bold" />
                            </div>
                            <div>
                                <label className="block text-xs font-bold uppercase mb-1">Photo URL</label>
                                <input {...register('eom.photo_url')} placeholder="https://..." className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50" />
                            </div>
                            <div className="md:col-span-2">
                                <label className="block text-xs font-bold uppercase mb-1">Blurb</label>
                                <textarea {...register('eom.blurb')} rows={2} className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50" />
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
                            <h2 className="font-serif text-2xl font-bold">Today Items</h2>
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
                    </section>

                    {/* Media & Links */}
                    <section className="bg-white p-6 rounded-3xl shadow-sm border border-brand-ink/5">
                        <h2 className="font-serif text-2xl font-bold mb-6">Media & Links</h2>
                        <div className="grid gap-4">
                            <div>
                                <label className="block text-xs font-bold uppercase mb-1">YouTube URL (Overrides Slideshow)</label>
                                <input {...register('youtube')} placeholder="https://youtube.com/..." className="w-full p-3 rounded-xl border border-gray-200 bg-gray-50" />
                            </div>
                        </div>
                    </section>

                </form>
            </main>
        </div>
    )
}
