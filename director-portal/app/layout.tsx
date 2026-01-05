import type { Metadata } from 'next'
import { Outfit, Playfair_Display } from 'next/font/google'
import './globals.css'
import { Providers } from './providers'

const outfit = Outfit({ subsets: ['latin'], variable: '--font-outfit' })
const playfair = Playfair_Display({ subsets: ['latin'], variable: '--font-playfair' })

export const metadata: Metadata = {
    title: 'Chroma Director Portal',
    description: 'Manage your school dashboard',
}

export default function RootLayout({
    children,
}: {
    children: React.ReactNode
}) {
    return (
        <html lang="en">
            <body className={`${outfit.variable} ${playfair.variable} bg-brand-cream text-brand-ink antialiased`}>
                <Providers>
                    {children}
                </Providers>
            </body>
        </html>
    )
}
