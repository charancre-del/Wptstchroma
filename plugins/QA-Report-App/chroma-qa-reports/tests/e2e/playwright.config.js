module.exports = {
    testDir: './specs',
    timeout: 60000,
    expect: {
        timeout: 10000,
    },
    use: {
        baseURL: 'http://localhost:8888/wp-admin', // Standard LocalWP/WPEnergy port, update if needed
        storageState: './auth.json', // Path to auth state (to bypass login)
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
        trace: 'on-first-retry',
    },
    projects: [
        {
            name: 'chromium',
            use: {
                browserName: 'chromium',
                viewport: { width: 1280, height: 720 },
            },
        },
    ],
};
