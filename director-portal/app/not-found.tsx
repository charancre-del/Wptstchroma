import Link from 'next/link'

export default function NotFound() {
    return (
        <div className="h-screen flex flex-col items-center justify-center bg-brand-cream p-4 text-center">
            <h2 className="text-3xl font-serif font-bold mb-4">Page Not Found</h2>
            <p className="mb-4">Could not find requested resource</p>
            <Link href="/" className="text-chroma-blue font-bold hover:underline">
                Return Home
            </Link>
        </div>
    )
}
