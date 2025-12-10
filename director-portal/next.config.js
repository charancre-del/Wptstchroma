const nextConfig = {
    output: 'export',
    basePath: '/portal',
    images: {
        unoptimized: true, // Required for static export
        domains: ['lh3.googleusercontent.com', 'drive.google.com'],
    },
}

module.exports = nextConfig

