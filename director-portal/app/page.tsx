'use client'

import { GoogleLogin } from '@react-oauth/google'
import { useRouter } from 'next/navigation'
import { useState } from 'react'

export default function LoginPage() {
    const router = useRouter()
    const [error, setError] = useState('')

    const handleSuccess = async (credentialResponse: any) => {
        try {
            const res = await fetch(`${process.env.NEXT_PUBLIC_WP_API_URL}/chroma/v1/auth/google`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_token: credentialResponse.credential })
            })

            const data = await res.json()

            if (!res.ok) throw new Error(data.message || 'Login failed')

            // Store token
            localStorage.setItem('chroma_token', data.token)
            localStorage.setItem('chroma_school_id', data.school_id)

            router.push('/dashboard')
        } catch (err: any) {
            setError(err.message)
        }
    }

    return (
        <main className="min-h-screen flex flex-col items-center justify-center p-6 bg-brand-cream">
            <div className="w-full max-w-md bg-white rounded-3xl p-8 shadow-xl border border-chroma-blue/10 text-center">
                <div className="flex justify-center -space-x-2 mb-6">
                    <span className="w-4 h-4 rounded-full bg-chroma-red"></span>
                    <span className="w-4 h-4 rounded-full bg-chroma-yellow"></span>
                    <span className="w-4 h-4 rounded-full bg-chroma-green"></span>
                    <span className="w-4 h-4 rounded-full bg-chroma-blue"></span>
                </div>
                <h1 className="font-serif text-3xl font-bold mb-2">Director Portal</h1>
                <p className="text-brand-ink/60 mb-8">Sign in to manage your school's TV dashboard.</p>

                <div className="flex justify-center">
                    <GoogleLogin
                        onSuccess={handleSuccess}
                        onError={() => setError('Google Login Failed')}
                        useOneTap
                    />
                </div>

                {error && <p className="mt-4 text-red-500 text-sm font-bold">{error}</p>}
            </div>
        </main>
    )
}
