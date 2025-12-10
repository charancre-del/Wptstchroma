const nextConfig = {
    output: 'export',
    basePath: '/portal',
    images: {
        unoptimized: true, // Required for static export
        domains: ['lh3.googleusercontent.com', 'drive.google.com'],
    },
    // Optional: Ensure trailing slashes for standard web server compatibility
    trailingSlash: true,
}

module.exports = nextConfig

